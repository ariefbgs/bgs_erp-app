<?php

namespace App\Http\Controllers;
 
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptDetail;
use App\Models\PoSupplier;
use App\Models\PoSupplierDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class GoodsReceiptController extends Controller
{
    public function index()
    {
        $receipts = GoodsReceipt::with(['poSupplier.supplier'])->orderBy('created_at', 'desc')->paginate(10);
        return view('goods_receipts.index', compact('receipts'));
    } 

    public function create()
    {
        $poSuppliers = PoSupplier::where('status', '!=', 'cancelled')
            ->where('receipt_status', '!=', 'completed')
            ->whereHas('details', function ($detailQuery) {
                $detailQuery->whereRaw(
                    'po_supplier_details.quantity > (
                        SELECT COALESCE(SUM(grd.quantity_received), 0)
                        FROM goods_receipt_details grd
                        INNER JOIN goods_receipts gr ON gr.id = grd.goods_receipt_id
                        WHERE grd.po_supplier_detail_id = po_supplier_details.id
                          AND gr.status != ?
                    )',
                    ['cancelled']
                );
            })
            ->with('supplier')
            ->orderBy('po_date', 'desc')
            ->get();

        return view('goods_receipts.create', compact('poSuppliers'));
    }

    public function getPoSupplierDetails($id)
    {
        $poSupplier = PoSupplier::with(['supplier'])->findOrFail($id);
        $details = PoSupplierDetail::with('product')->where('po_supplier_id', $id)->get();

        $items = [];
        foreach ($details as $detail) {
            $totalReceived = GoodsReceiptDetail::where('po_supplier_detail_id', $detail->id)
                ->whereHas('goodsReceipt', function ($q) {
                    $q->where('status', '!=', 'cancelled');
                })
                ->sum('quantity_received');

            $remaining = $detail->quantity - $totalReceived;

            if ($remaining > 0) {
                $items[] = [
                    'po_supplier_detail_id' => $detail->id,
                    'product_id'            => $detail->product_id,
                    'product_name'          => $detail->product->name,
                    'product_code'          => $detail->product->product_code,
                    'brand'                 => $detail->product->brand,
                    'unit'                  => $detail->product->unit,
                    'po_quantity'           => $detail->quantity,
                    'received_quantity'     => $totalReceived,
                    'remaining_quantity'    => $remaining,
                    'purchase_price'        => $detail->purchase_price
                ];
            }
        }

        return response()->json([
            'po_supplier' => $poSupplier,
            'items'       => $items
        ]);
    }

    // ============================================================
    // STORE – dengan try-catch dan notifikasi error
    // ============================================================
    public function store(Request $request)
    {
        \Log::info('Goods Receipt Store Request:', $request->all());

        try {
            $request->validate([
                'po_supplier_id' => 'required|exists:po_suppliers,id',
                'receipt_date'   => 'required|date',
                'notes'          => 'nullable|string',
                'items'          => 'required|array|min:1',
                'items.*.po_supplier_detail_id' => 'required|exists:po_supplier_details,id',
                'items.*.quantity_received'     => 'required|integer|min:0'
            ], [
                'po_supplier_id.required' => 'PO Supplier wajib dipilih.',
                'receipt_date.required' => 'Tanggal penerimaan wajib diisi.',
                'items.required' => 'Minimal harus ada 1 item yang diterima.',
                'items.*.quantity_received.min' => 'Jumlah diterima tidak boleh negatif.',
            ]);

            DB::beginTransaction();

            $poSupplier = PoSupplier::whereKey($request->po_supplier_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($poSupplier->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'po_supplier_id' => 'PO Supplier sudah dibatalkan.',
                ]);
            }

            // Generate nomor receipt
            $receipt_date = $request->receipt_date;
            $currentYear = date('Y', strtotime($receipt_date));
            $currentMonth = date('n', strtotime($receipt_date));
            
            $lastReceipt = GoodsReceipt::whereYear('receipt_date', $currentYear)
                ->orderBy('id', 'desc')
                ->first();
                
            if ($lastReceipt) {
                $lastNumber = (int) explode('/', $lastReceipt->receipt_number)[0];
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }
            
            $romanMonthStr = $this->romanMonth($currentMonth);
            $receiptNumber = $newNumber . '/GR/BGS/' . $romanMonthStr . '/' . $currentYear;

            // Simpan header receipt
            $receipt = GoodsReceipt::create([
                'receipt_number' => $receiptNumber,
                'po_supplier_id' => $request->po_supplier_id,
                'receipt_date'   => $request->receipt_date,
                'status'         => 'partial',
                'notes'          => $request->notes,
                'received_by'    => auth()->user()->name ?? 'System'
            ]);

            // Simpan detail receipt & update stok
            foreach ($request->items as $item) {
                if ($item['quantity_received'] > 0) {
                    $poDetail = PoSupplierDetail::whereKey($item['po_supplier_detail_id'])
                        ->where('po_supplier_id', $poSupplier->id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $receivedQty = $item['quantity_received'];

                    $alreadyReceived = GoodsReceiptDetail::where(
                        'po_supplier_detail_id',
                        $poDetail->id
                    )->whereHas('goodsReceipt', function ($query) {
                        $query->where('status', '!=', 'cancelled');
                    })->sum('quantity_received');

                    if ($receivedQty > ((float) $poDetail->quantity - (float) $alreadyReceived)) {
                        throw ValidationException::withMessages([
                            'items' => 'Quantity Goods Receipt melebihi sisa quantity PO Supplier.',
                        ]);
                    }

                    GoodsReceiptDetail::create([
                        'goods_receipt_id'       => $receipt->id,
                        'po_supplier_detail_id'  => $item['po_supplier_detail_id'],
                        'product_id'             => $poDetail->product_id,
                        'quantity_received'      => $receivedQty,
                        'notes'                  => $item['notes'] ?? null
                    ]);

                    $product = Product::find($poDetail->product_id);
                    $product->stock += $receivedQty;
                    $product->save();
                }
            }

            // Hitung sisa PO Supplier
            $sisa = $this->getPoSupplierRemainingQty($request->po_supplier_id);
            $receiptStatus = ($sisa == 0) ? 'completed' : 'partial';
            $receipt->update(['status' => $receiptStatus]);

            // Update receipt_status PO Supplier
            $this->updatePoSupplierReceiptStatus($poSupplier);

            DB::commit();

            return redirect()->route('goods-receipts.index')
                ->with('success', 'Goods Receipt berhasil dibuat!');

        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->validator);
        } catch (QueryException $e) {
            DB::rollBack();
            $errorMessage = 'Gagal menyimpan Goods Receipt. ';
            if ($e->getCode() == 23000) {
                $errorMessage .= 'Data duplikat ditemukan. Pastikan nomor receipt unik.';
            } else {
                $errorMessage .= $e->getMessage();
            }
            return back()->withInput()->with('error', $errorMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan Goods Receipt: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $receipt = GoodsReceipt::with(['poSupplier.supplier', 'details.product', 'details.poSupplierDetail'])->findOrFail($id);
        return view('goods_receipts.show', compact('receipt'));
    }

    // ============================================================
    // EDIT – dengan pengecekan apakah receipt sudah digunakan
    // ============================================================
    public function edit($id)
    {
        $receipt = GoodsReceipt::with(['details.poSupplierDetail.product', 'poSupplier.supplier'])->findOrFail($id);

        //if ($receipt->status != 'partial') {
        //    return redirect()->route('goods-receipts.index')
        //        ->with('error', 'Hanya receipt dengan status partial yang dapat diedit!');
        //}

        // Cek apakah receipt sudah digunakan di transaksi lain (misal retur pembelian)
        if ($this->isGoodsReceiptUsed($receipt)) {
            return redirect()->route('goods-receipts.index')
                ->with('error', 'Receipt tidak dapat diedit karena sudah digunakan di transaksi lain.');
        }
        
        $poSupplier = $receipt->poSupplier;
        $poDetails = $poSupplier->details;
        $availableProducts = [];
        $itemsWithRemaining = [];

        foreach ($poDetails as $poDetail) {
            $totalReceivedOther = $poDetail->goodsReceiptDetails()
                ->where('goods_receipt_id', '!=', $receipt->id)
                ->whereHas('goodsReceipt', function($q) {
                    $q->where('status', '!=', 'cancelled');
                })
                ->sum('quantity_received');

            $remaining = $poDetail->quantity - $totalReceivedOther;

            $existingDetail = $receipt->details->firstWhere('po_supplier_detail_id', $poDetail->id);
            
            if ($existingDetail) {
                $itemsWithRemaining[] = [
                    'detail' => $existingDetail,
                    'remaining_other' => $remaining,
                    'max_qty' => $remaining + $existingDetail->quantity_received,
                ];
            } elseif ($remaining > 0) {
                $availableProducts[] = [
                    'po_supplier_detail_id' => $poDetail->id,
                    'product_id'            => $poDetail->product_id,
                    'product_name'          => $poDetail->product->name,
                    'product_code'          => $poDetail->product->product_code,
                    'brand'                 => $poDetail->product->brand,
                    'unit'                  => $poDetail->product->unit,
                    'po_quantity'           => $poDetail->quantity,
                    'remaining_quantity'    => $remaining,
                ];
            }
        }

        return view('goods_receipts.edit', compact('receipt', 'itemsWithRemaining', 'availableProducts'));
    }

    // ============================================================
    // UPDATE – dengan try-catch dan notifikasi error
    // ============================================================
    public function update(Request $request, $id)
    {
        $receipt = GoodsReceipt::findOrFail($id);

        if ($receipt->status != 'partial') {
            return back()->with('error', 'Hanya receipt dengan status partial yang dapat diedit!');
        }

        if ($this->isGoodsReceiptUsed($receipt)) {
            return back()->with('error', 'Receipt tidak dapat diupdate karena sudah digunakan di transaksi lain.');
        }

        try {
            $request->validate([
                'receipt_date' => 'required|date',
                'notes'        => 'nullable|string',
                'items'        => 'required|array',
                'items.*.po_supplier_detail_id' => 'required|exists:po_supplier_details,id',
                'items.*.quantity_received'     => 'required|integer|min:0'
            ], [
                'receipt_date.required' => 'Tanggal penerimaan wajib diisi.',
                'items.required' => 'Minimal harus ada 1 item yang diterima.',
            ]);

            DB::beginTransaction();

            // Reverse stok dari detail lama
            foreach ($receipt->details as $detail) {
                $product = Product::find($detail->product_id);
                $product->stock -= $detail->quantity_received;
                $product->save();
            }

            // Hapus detail lama
            $receipt->details()->delete();

            // Simpan detail baru
            foreach ($request->items as $item) {
                if ($item['quantity_received'] > 0) {
                    $poDetail = PoSupplierDetail::find($item['po_supplier_detail_id']);
                    $receivedQty = $item['quantity_received'];

                    GoodsReceiptDetail::create([
                        'goods_receipt_id'       => $receipt->id,
                        'po_supplier_detail_id'  => $item['po_supplier_detail_id'],
                        'product_id'             => $poDetail->product_id,
                        'quantity_received'      => $receivedQty,
                        'notes'                  => $item['notes'] ?? null
                    ]);

                    $product = Product::find($poDetail->product_id);
                    $product->stock += $receivedQty;
                    $product->save();
                }
            }

            // Hitung sisa PO Supplier setelah update
            $sisa = $this->getPoSupplierRemainingQty($receipt->po_supplier_id);
            $newReceiptStatus = ($sisa == 0) ? 'completed' : 'partial';
            $receipt->update([
                'receipt_date' => $request->receipt_date,
                'notes'        => $request->notes,
                'status'       => $newReceiptStatus
            ]);

            // Update receipt_status PO Supplier
            $this->updatePoSupplierReceiptStatus($receipt->poSupplier);

            DB::commit();
            return redirect()->route('goods-receipts.index')
                ->with('success', 'Goods Receipt berhasil diupdate!');

        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->validator);
        } catch (QueryException $e) {
            DB::rollBack();
            $errorMessage = 'Gagal memperbarui Goods Receipt. ';
            if ($e->getCode() == 23000) {
                $errorMessage .= 'Data duplikat ditemukan.';
            } else {
                $errorMessage .= $e->getMessage();
            }
            return back()->withInput()->with('error', $errorMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui Goods Receipt: ' . $e->getMessage());
        }
    }

    // ============================================================
    // DESTROY – dengan pengecekan relasi dan try-catch
    // ============================================================
    public function destroy($id)
    {
        $receipt = GoodsReceipt::findOrFail($id);

        if ($receipt->poSupplier->receipt_status == 'completed') {
            return back()->with('error', 'PO Supplier sudah complete, receipt tidak dapat dihapus!');
        }

        if ($this->isGoodsReceiptUsed($receipt)) {
            return back()->with('error', 'Receipt tidak dapat dihapus karena sudah digunakan di transaksi lain.');
        }

        DB::beginTransaction();
        try {
            // Kurangi stok (reverse)
            foreach ($receipt->details as $detail) {
                $product = Product::find($detail->product_id);
                $product->stock -= $detail->quantity_received;
                $product->save();
            }

            $receipt->details()->delete();
            $receipt->delete();

            // Update status PO Supplier
            $poSupplier = $receipt->poSupplier;
            $otherReceipts = GoodsReceipt::where('po_supplier_id', $poSupplier->id)
                ->where('status', '!=', 'cancelled')
                ->count();

            if ($otherReceipts == 0) {
                $poSupplier->update(['receipt_status' => 'pending']);
            } else {
                $this->updatePoSupplierReceiptStatus($poSupplier);
            }

            DB::commit();
            return redirect()->route('goods-receipts.index')
                ->with('success', 'Goods Receipt berhasil dihapus!');

        } catch (QueryException $e) {
            DB::rollBack();
            return back()->with('error', 'Receipt tidak dapat dihapus karena terkait dengan data lain.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus Goods Receipt: ' . $e->getMessage());
        }
    }

    // ============================================================
    // CANCEL – dengan pengecekan relasi
    // ============================================================
    public function cancel($id)
    {
        $receipt = GoodsReceipt::findOrFail($id);

        if ($receipt->status == 'cancelled') {
            return back()->with('error', 'Receipt sudah dibatalkan sebelumnya.');
        }

        if ($this->isGoodsReceiptUsed($receipt)) {
            return back()->with('error', 'Receipt tidak dapat dibatalkan karena sudah digunakan di transaksi lain.');
        }

        DB::beginTransaction();
        try {
            // Kurangi stok (reverse)
            foreach ($receipt->details as $detail) {
                $product = Product::find($detail->product_id);
                $product->stock -= $detail->quantity_received;
                $product->save();
            }

            $receipt->update(['status' => 'cancelled']);

            // Update receipt_status PO Supplier
            $this->updatePoSupplierReceiptStatus($receipt->poSupplier);

            DB::commit();
            return redirect()->route('goods-receipts.show', $id)
                ->with('success', 'Goods Receipt berhasil dibatalkan.');

        } catch (QueryException $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan receipt: ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan: ' . $e->getMessage());
        }
    }

    // ============================================================
    // PRIVATE HELPERS
    // ============================================================

    /**
     * Hitung total sisa quantity PO Supplier
     */
    private function getPoSupplierRemainingQty($poSupplierId)
    {
        $details = PoSupplierDetail::where('po_supplier_id', $poSupplierId)->get();
        $remaining = 0;
        foreach ($details as $detail) {
            $totalReceived = GoodsReceiptDetail::where('po_supplier_detail_id', $detail->id)
                ->whereHas('goodsReceipt', function ($q) {
                    $q->where('status', '!=', 'cancelled');
                })
                ->sum('quantity_received');
            $remaining += ($detail->quantity - $totalReceived);
        }
        return $remaining;
    }

    /**
     * Update receipt_status di PO Supplier berdasarkan sisa barang
     */
    private function updatePoSupplierReceiptStatus($poSupplier)
    {
        $sisa = $this->getPoSupplierRemainingQty($poSupplier->id);
        if ($sisa == 0) {
            $status = 'completed';
        } else {
            $anyReceived = GoodsReceiptDetail::whereHas('goodsReceipt', function ($q) use ($poSupplier) {
                $q->where('po_supplier_id', $poSupplier->id)
                  ->where('status', '!=', 'cancelled');
            })->exists();
            $status = $anyReceived ? 'partial' : 'pending';
        }
        \DB::table('po_suppliers')->where('id', $poSupplier->id)->update(['receipt_status' => $status]);
        $poSupplier->refresh();
    }

    /**
     * CEK APAKAH GOODS RECEIPT SUDAH DIGUNAKAN DI TRANSAKSI LAIN
     * Misal: retur pembelian, invoice supplier, dll.
     */
    private function isGoodsReceiptUsed(GoodsReceipt $receipt)
    {
        // Contoh: jika ada model PurchaseReturnDetail atau SupplierInvoiceDetail
        // yang memiliki foreign key goods_receipt_id atau goods_receipt_detail_id
        // Sesuaikan dengan struktur database Anda

        // Cek apakah ada retur pembelian yang mengacu ke receipt ini
        // if ($receipt->purchaseReturns()->exists()) {
        //     return true;
        // }

        // Cek apakah ada invoice supplier yang mengacu ke receipt ini
        // if ($receipt->supplierInvoices()->exists()) {
        //     return true;
        // }

        // Jika tidak ada relasi, return false
        return false;
    }

    /**
     * Helper: konversi bulan ke angka Romawi
     */
    private function romanMonth($monthNum)
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        return $romans[(int)$monthNum] ?? 'I';
    }
}
