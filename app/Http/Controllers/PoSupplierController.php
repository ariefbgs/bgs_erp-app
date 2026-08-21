<?php

namespace App\Http\Controllers;

use App\Models\PoSupplier; 
use App\Models\PoSupplierDetail;
use App\Models\PoCustomer;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class PoSupplierController extends Controller
{
    public function index()
    {
        $poSuppliers = PoSupplier::with(['customer', 'supplier'])->orderBy('created_at', 'desc')->paginate(10);
        return view('po_suppliers.index', compact('poSuppliers'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        return view('po_suppliers.create', compact('customers', 'suppliers', 'products'));
    }

    public function getPoCustomerDetails($id)
    {
        $poCustomer = PoCustomer::with(['customer', 'details.product'])->findOrFail($id);
        
        $items = [];
        foreach ($poCustomer->details as $detail) {
            $items[] = [
                'product_id' => $detail->product_id,
                'product_name' => $detail->product->name,
                'product_code' => $detail->product->product_code,
                'brand' => $detail->product->brand,
                'unit' => $detail->product->unit,
                'quantity' => $detail->quantity,
                'purchase_price' => $detail->product->purchase_price,
                'subtotal' => $detail->subtotal
            ];
        }
        
        return response()->json([
            'po_customer' => $poCustomer,
            'items' => $items,
            'customer' => $poCustomer->customer
        ]);
    }

    // ============================================================
    // STORE – dengan try-catch dan notifikasi error
    // ============================================================
    public function store(Request $request)
    {
        \Log::info('PO Supplier Store Request:', $request->all());

        try {
            $request->validate([
                'po_customer_id' => 'required|exists:po_customers,id',
                'supplier_id' => 'required|exists:suppliers,id',
                'po_date' => 'required|date',
                'notes' => 'nullable'
            ], [
                'po_customer_id.required' => 'PO Customer wajib dipilih.',
                'supplier_id.required' => 'Supplier wajib dipilih.',
                'po_date.required' => 'Tanggal PO wajib diisi.',
            ]);

            DB::beginTransaction();
            
            $poCustomer = PoCustomer::findOrFail($request->po_customer_id);
            
            $poDate = $request->po_date;
            $currentYear = date('Y', strtotime($poDate));
            $currentMonth = date('n', strtotime($poDate)); 
            
            $lastPoSupplier = PoSupplier::whereYear('po_date', $currentYear)
                ->orderBy('id', 'desc')
                ->first();

            if ($lastPoSupplier) {
                $lastNumber = (int) explode('/', $lastPoSupplier->po_supplier_number)[0];
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }

            $romanMonthStr = $this->romanMonth($currentMonth);
            $poSupplierNumber = $newNumber . '/PO/BGS/' . $romanMonthStr . '/' . $currentYear;
            
            $subtotal = 0;
            $items = $request->items;
            $processedItems = [];
            
            foreach ($items as $key => $item) {
                $quantity = (int) str_replace('.', '', $item['quantity']);
                $price = (float) str_replace('.', '', $item['purchase_price']);
                $subtotal += $quantity * $price;
                
                $processedItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'purchase_price' => $price,
                    'subtotal' => $quantity * $price
                ];
            }
            
            $taxPercent = (float) $request->tax_percent ?? 0;
            $taxAmount = $subtotal * ($taxPercent / 100);
            $total = $subtotal + $taxAmount;
            
            $poSupplier = PoSupplier::create([
                'po_supplier_number' => $poSupplierNumber,
                'po_customer_id' => $request->po_customer_id,
                'customer_id' => $poCustomer->customer_id,
                'supplier_id' => $request->supplier_id,
                'po_date' => $request->po_date,
                'status' => 'draft',
                'notes' => $request->notes,
                'subtotal' => $subtotal,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'total' => $total
            ]);
            
            foreach ($processedItems as $item) {
                PoSupplierDetail::create([
                    'po_supplier_id' => $poSupplier->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'subtotal' => $item['subtotal']
                ]);
            }
            
            DB::commit();
            
            $poCustomer = PoCustomer::find($request->po_customer_id);
            if ($poCustomer && $poCustomer->status == 'received') {
                $poCustomer->update(['status' => 'processed']);
            }

            return redirect()->route('po-suppliers.index')
                ->with('success', 'PO Supplier berhasil dibuat!');
                
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->validator);
        } catch (QueryException $e) {
            DB::rollBack();
            $errorMessage = 'Gagal menyimpan PO Supplier. ';
            if ($e->getCode() == 23000) {
                $errorMessage .= 'Data duplikat ditemukan. Pastikan nomor PO unik.';
            } else {
                $errorMessage .= $e->getMessage();
            }
            return back()->withInput()->with('error', $errorMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan PO Supplier: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $poSupplier = PoSupplier::with(['customer', 'supplier', 'poCustomer', 'details.product'])->findOrFail($id);
        return view('po_suppliers.show', compact('poSupplier'));
    }

    // ============================================================
    // EDIT – dengan pengecekan apakah PO Supplier sudah digunakan
    // ============================================================
    public function edit($id)
    {
        $poSupplier = PoSupplier::with(['details.product'])->findOrFail($id);
        
        // Cek status apakah boleh diedit
        if (!in_array($poSupplier->status, ['draft', 'confirmed'])) {
            return redirect()->route('po-suppliers.index')
                ->with('error', 'PO Supplier dengan status ' . ucfirst($poSupplier->status) . ' tidak dapat diedit!');
        }

        // Cek apakah sudah ada Goods Receipt
        if ($this->isPoSupplierUsed($poSupplier)) {
            return redirect()->route('po-suppliers.index')
                ->with('error', 'PO Supplier tidak dapat diedit karena sudah memiliki penerimaan barang (Goods Receipt).');
        }
        
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        
        return view('po_suppliers.edit', compact('poSupplier', 'suppliers', 'products'));
    }

    // ============================================================
    // UPDATE – dengan try-catch dan notifikasi error
    // ============================================================
    public function update(Request $request, $id)
    {
        $poSupplier = PoSupplier::with('details')->findOrFail($id);

        // Cek status apakah boleh diedit
        if (!in_array($poSupplier->status, ['draft', 'confirmed'])) {
            return back()->with('error', 'PO Supplier dengan status ' . ucfirst($poSupplier->status) . ' tidak dapat diedit!');
        }

        // Cek apakah sudah ada Goods Receipt
        if ($this->isPoSupplierUsed($poSupplier)) {
            return back()->with('error', 'PO Supplier sudah memiliki penerimaan barang, tidak dapat diubah!');
        }

        try {
            $request->validate([
                'po_supplier_number' => 'required|string|max:50|unique:po_suppliers,po_supplier_number,' . $poSupplier->id,
                'supplier_id' => 'required|exists:suppliers,id',
                'po_date' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.purchase_price' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'tax_percent' => 'nullable|numeric|min:0|max:100',
                'status' => 'nullable|in:draft,confirmed,cancelled',
            ], [
                'po_supplier_number.unique' => 'Nomor PO Supplier ":input" sudah terdaftar. Silakan gunakan nomor lain.',
                'po_supplier_number.required' => 'Nomor PO Supplier wajib diisi.',
                'supplier_id.required' => 'Supplier wajib dipilih.',
                'items.required' => 'Minimal harus ada 1 item produk.',
                'items.*.quantity.min' => 'Quantity minimal 1.',
                'items.*.purchase_price.min' => 'Harga beli tidak boleh negatif.',
            ]);

            DB::beginTransaction();

            // Hitung ulang
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['purchase_price'];
            }

            $taxPercent = $request->tax_percent ?? 0;
            $taxAmount = $subtotal * ($taxPercent / 100);
            $total = $subtotal + $taxAmount;

            // Update header
            $poSupplier->update([
                'po_supplier_number' => $request->po_supplier_number,
                'supplier_id' => $request->supplier_id,
                'po_date' => $request->po_date,
                'notes' => $request->notes,
                'subtotal' => $subtotal,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'status' => $request->status ?? $poSupplier->status,
            ]);

            // Hapus detail lama
            $poSupplier->details()->delete();

            // Simpan detail baru
            foreach ($request->items as $item) {
                PoSupplierDetail::create([
                    'po_supplier_id' => $poSupplier->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'subtotal' => $item['quantity'] * $item['purchase_price']
                ]);
            }

            DB::commit();

            return redirect()->route('po-suppliers.index')
                ->with('success', 'PO Supplier berhasil diperbarui!');

        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->validator);
        } catch (QueryException $e) {
            DB::rollBack();
            $errorMessage = 'Gagal memperbarui PO Supplier. ';
            if ($e->getCode() == 23000) {
                $errorMessage .= 'Data duplikat ditemukan. Pastikan nomor PO unik.';
            } else {
                $errorMessage .= $e->getMessage();
            }
            return back()->withInput()->with('error', $errorMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui PO Supplier: ' . $e->getMessage());
        }
    }
    
    public function updateStatus(Request $request, $id)
    {
        $poSupplier = PoSupplier::findOrFail($id);
        $newStatus = $request->status;
        $allowedStatus = ['confirmed', 'received', 'cancelled'];
        
        if (in_array($newStatus, $allowedStatus)) {
            $poSupplier->update(['status' => $newStatus]);
            return redirect()->route('po-suppliers.show', $id)
                ->with('success', 'Status PO Supplier berhasil diperbarui!');
        }
        
        return back()->with('error', 'Status tidak valid!');
    }
    
    // ============================================================
    // DESTROY – dengan pengecekan relasi dan try-catch
    // ============================================================
    public function destroy($id)
    {
        $poSupplier = PoSupplier::findOrFail($id);
        
        // Cek status
        if ($poSupplier->status == 'received') {
            return back()->with('error', 'PO Supplier dengan status Received tidak dapat dihapus!');
        }
        
        if (!in_array($poSupplier->status, ['draft', 'confirmed'])) {
            return back()->with('error', 'PO Supplier dengan status ' . ucfirst($poSupplier->status) . ' tidak dapat dihapus!');
        }

        // Cek apakah sudah ada Goods Receipt
        if ($this->isPoSupplierUsed($poSupplier)) {
            return back()->with('error', 'PO Supplier tidak dapat dihapus karena sudah memiliki penerimaan barang (Goods Receipt).');
        }
        
        $poCustomerId = $poSupplier->po_customer_id;
        
        DB::beginTransaction();
        try {
            $poSupplier->details()->delete();
            $poSupplier->delete();

            $remainingCount = PoSupplier::where('po_customer_id', $poCustomerId)->count();
            if ($remainingCount == 0) {
                $poCustomer = PoCustomer::find($poCustomerId);
                if ($poCustomer && $poCustomer->status == 'processed') {
                    $poCustomer->update(['status' => 'received']);
                }
            }
            
            DB::commit();
            return redirect()->route('po-suppliers.index')
                ->with('success', 'Supplier PO berhasil dihapus!');
        } catch (QueryException $e) {
            DB::rollBack();
            return back()->with('error', 'PO Supplier tidak dapat dihapus karena terkait dengan data lain.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus PO Supplier: ' . $e->getMessage());
        }
    }

    public function getPoCustomerList(Request $request)
    {
        $customerId = $request->customer_id;
        if (!$customerId) {
            return response()->json([]);
        }
        
        $poCustomers = PoCustomer::where('customer_id', $customerId)
            ->whereNotIn('status', ['cancelled'])
            ->whereDoesntHave('poSuppliers')
            ->orderBy('po_date', 'desc')
            ->get(['id', 'po_number', 'po_date', 'total']);
        
        return response()->json($poCustomers);
    }

    public function print($id)
    {
        try {
            $poSupplier = PoSupplier::with(['customer', 'supplier', 'poCustomer', 'details.product'])->findOrFail($id);
            $company = Company::where('is_active', true)->first();
            
            $pdf = Pdf::loadView('po_suppliers.print', compact('poSupplier', 'company'));
            $pdf->setPaper('a4', 'portrait');

            $rawName = $poSupplier->po_supplier_number;
            $cleanName = preg_replace('/[^A-Za-z0-9_-]/', '_', $rawName);
            $filename = 'PO_Supplier_' . $cleanName . '.pdf';

            return $pdf->stream($filename, array('Attachment' => 0));
                
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mencetak PO Supplier: ' . $e->getMessage());
        }
    }

    // ============================================================
    // PRIVATE HELPER: CEK APAKAH PO SUPPLIER SUDAH DIGUNAKAN
    // ============================================================
    private function isPoSupplierUsed(PoSupplier $poSupplier)
    {
        // Cek apakah ada Goods Receipt yang terkait dengan detail PO Supplier
        foreach ($poSupplier->details as $detail) {
            if ($detail->goodsReceiptDetails()->exists()) {
                return true;
            }
        }
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