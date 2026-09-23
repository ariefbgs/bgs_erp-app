<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderDetail;
use App\Models\PoCustomer;
use App\Models\PoCustomerDetail;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class DeliveryOrderController extends Controller
{
    public function index()
    {
        $dos = DeliveryOrder::with('poCustomer.customer')->orderBy('created_at', 'desc')->paginate(10);
        return view('delivery_orders.index', compact('dos'));
    }

    public function create()
    {
        $poCustomers = PoCustomer::with('customer')
            ->where('status', '!=', 'cancelled')
            ->whereHas('invoiceCustomers', function ($query) {
                $query->where('status', '!=', 'cancelled');
            })
            ->whereHas('poSuppliers.goodsReceipts', function ($query) {
                $query->where('status', '!=', 'cancelled')
                    ->whereHas('details', function ($detailQuery) {
                        $detailQuery->where('quantity_received', '>', 0);
                    });
            })
            ->orderBy('po_date', 'desc')
            ->get()
            ->filter(fn (PoCustomer $poCustomer) =>
                collect($this->getDeliverableQuantities($poCustomer->id))->contains(
                    fn ($quantity) => $quantity > 0
                )
            );

        \Log::info('Jumlah PO Customer: ' . $poCustomers->count());
        
        return view('delivery_orders.create', compact('poCustomers'));
    }

    public function getPoCustomerDetails($id)
    {
        $poCustomer = PoCustomer::with(['details.product', 'customer'])->findOrFail($id);

        $hasActiveInvoice = \App\Models\InvoiceCustomer::where('po_customer_id', $poCustomer->id)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if (!$hasActiveInvoice) {
            return response()->json([
                'message' => 'Delivery Order tidak dapat dibuat karena PO Customer belum memiliki Sales Invoice aktif.',
            ], 422);
        }

        $items = [];
        $deliverableQuantities = $this->getDeliverableQuantities($poCustomer->id);

        foreach ($poCustomer->details as $detail) {
            $deliverable = (float) ($deliverableQuantities[$detail->id] ?? 0);
            if ($deliverable <= 0) {
                continue;
            }
            $items[] = [
                'po_customer_detail_id' => $detail->id,
                'product_id' => $detail->product_id,
                'product_name' => $detail->product->name,
                'product_code' => $detail->product->product_code,
                'brand' => $detail->product->brand,
                'unit' => $detail->product->unit,
                'quantity' => $deliverable,
                'po_quantity' => $detail->quantity,
            ];
        }
        return response()->json([
            'po_customer' => $poCustomer,
            'items' => $items,
            'customer' => $poCustomer->customer,
            'shipping_address' => $poCustomer->customer->shipping_address ?? $poCustomer->customer->address ?? '',
            'receiver_name' => $poCustomer->customer->pic_do_name ?? $poCustomer->customer->pic_quotation_name ?? $poCustomer->customer->name ?? '',
            'payment_terms' => $poCustomer->payment_terms,
            'delivery_time' => $poCustomer->delivery_time,
        ]);
    }

    public function store(Request $request)
    {
        \Log::info('Delivery Order Store Request:', $request->all());

        // Validasi â€“ do_number TIDAK wajib diisi karena otomatis digenerate
        $request->validate([ 
            'po_customer_id' => 'required|exists:po_customers,id',
            'delivery_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.po_customer_detail_id' => 'required|exists:po_customer_details,id',
            'items.*.quantity' => 'required|integer|min:0',
            'shipping_address' => 'nullable|string',
            'receiver_name' => 'nullable|string',
            'notes' => 'nullable|string',
            'delivery_by' => 'nullable|string|in:Wisnu,JNE,Courier Online',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        // Proses upload file
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $extension = $file->getClientOriginalExtension();
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $originalName) . '.' . $extension;
            
            $folder = 'delivery_orders';
            if (!Storage::disk('public')->exists($folder)) {
                Storage::disk('public')->makeDirectory($folder);
            }

            try {
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $image = ImageManager::gd()->read($file->getRealPath());
                    $image->resize(800, 800, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    $image->toJpeg(80)->save(storage_path('app/public/' . $folder . '/' . $safeName));
                } else {
                    Storage::disk('public')->putFileAs($folder, $file, $safeName);
                }
                $attachmentPath = $folder . '/' . $safeName;
            } catch (\Exception $e) {
                \Log::warning('Gagal memproses attachment: ' . $e->getMessage());
                // Simpan asli jika gagal kompresi
                Storage::disk('public')->putFileAs($folder, $file, $safeName);
                $attachmentPath = $folder . '/' . $safeName;
            }
        }

        DB::beginTransaction();
        try {
            $poCustomer = PoCustomer::findOrFail($request->po_customer_id);

            $hasActiveInvoice = \App\Models\InvoiceCustomer::where('po_customer_id', $poCustomer->id)
                ->where('status', '!=', 'cancelled')
                ->exists();

            if (!$hasActiveInvoice) {
                throw ValidationException::withMessages([
                    'po_customer_id' => 'Delivery Order tidak dapat dibuat karena PO Customer belum memiliki Sales Invoice aktif.',
                ]);
            }

            $deliverableQuantities = $this->getDeliverableQuantities($poCustomer->id);

            $validatedItems = [];
            $positiveItemCount = 0;

            foreach ($request->items as $item) {
                $poCustomerDetail = PoCustomerDetail::where('id', $item['po_customer_detail_id'])
                    ->where('po_customer_id', $poCustomer->id)
                    ->first();

                if (!$poCustomerDetail) {
                    throw ValidationException::withMessages([
                        'items' => 'Item Delivery Order tidak valid untuk PO Customer yang dipilih.',
                    ]);
                }

                $requestedQuantity = (float) $item['quantity'];

                if ($requestedQuantity <= 0) {
                    continue;
                }

                $positiveItemCount++;

                $availableQuantity = (float) (
                    $deliverableQuantities[$poCustomerDetail->id] ?? 0
                );

                if ($requestedQuantity > $availableQuantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Quantity Delivery Order melebihi quantity yang sudah diterima melalui Goods Receipt.',
                    ]);
                }

                $validatedItems[] = [
                    'po_customer_detail_id' => $poCustomerDetail->id,
                    'product_id' => $poCustomerDetail->product_id,
                    'quantity' => $requestedQuantity,
                ];
            }

            if ($positiveItemCount === 0) {
                throw ValidationException::withMessages([
                    'items' => 'Minimal satu quantity Delivery Order harus lebih dari nol.',
                ]);
            }

            // Generate DO Number
            $doDate = $request->delivery_date;
            $currentYear = date('Y', strtotime($doDate));
            $lastDo = DeliveryOrder::whereYear('delivery_date', $currentYear)
                ->orderBy('id', 'desc')
                ->first();
            if ($lastDo) {
                $lastNumber = (int) explode('/', $lastDo->do_number)[0];
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }
            $romanMonth = $this->romanMonth(date('n', strtotime($doDate)));
            $doNumber = $newNumber . '/DO/BGS/' . $romanMonth . '/' . $currentYear;

            // Cegah duplicate entry dengan loop (fallback)
            $attempt = 0;
            while ($attempt < 5) {
                try {
                    $do = DeliveryOrder::create([
                        'do_number' => $doNumber,
                        'po_customer_id' => $request->po_customer_id,
                        'delivery_date' => $request->delivery_date,
                        'shipping_address' => $request->shipping_address,
                        'receiver_name' => $request->receiver_name,
                        'status' => 'pending',
                        'notes' => $request->notes,
                        'created_by' => optional(auth()->user())->name ?? 'system',
                        'delivery_by' => $request->delivery_by,
                        'attachment' => $attachmentPath,
                    ]);
                    break; // Berhasil, keluar dari loop
                } catch (QueryException $e) {
                    if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'do_number')) {
                        // Duplicate entry, increment nomor
                        $attempt++;
                        $lastNumber = (int) explode('/', $doNumber)[0] + 1;
                        $newNumber = str_pad($lastNumber, 4, '0', STR_PAD_LEFT);
                        $doNumber = $newNumber . '/DO/BGS/' . $romanMonth . '/' . $currentYear;
                        \Log::warning('Duplicate DO number, retrying with: ' . $doNumber);
                    } else {
                        throw $e;
                    }
                }
            }

            foreach ($validatedItems as $item) {
                DeliveryOrderDetail::create([
                    'delivery_order_id' => $do->id,
                    'po_customer_detail_id' => $item['po_customer_detail_id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();

            $action = $request->input('action', 'save');
            if ($action == 'print_do') {
                return redirect()->route('delivery-orders.print', $do->id);
            }

            return redirect()->route('delivery-orders.index')
                ->with('success', 'Delivery Order successfully saved.');
        } catch (QueryException $e) {
            DB::rollBack();
            \Log::error('Database error saat menyimpan DO: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Gagal menyimpan Delivery Order: Terjadi duplikasi nomor atau kesalahan database.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saat menyimpan DO: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Gagal menyimpan Delivery Order: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $do = DeliveryOrder::with(['poCustomer.customer', 'details.product'])->findOrFail($id);
        return view('delivery_orders.show', compact('do'));
    }

    public function edit($id)
    {
        $do = DeliveryOrder::with(['details.product', 'poCustomer.customer'])->findOrFail($id);

        if ($do->status !== 'pending') {
            return redirect()->route('delivery-orders.index')
                ->with('error', 'Only DO with status Pending can be edited.');
        }

        if ($do->details->contains(fn ($detail) => $detail->po_customer_detail_id === null)) {
            return redirect()->route('delivery-orders.index')
                ->with(
                    'error',
                    'Delivery Order ini memiliki legacy detail tanpa canonical PO Customer Detail lineage dan tidak dapat diedit sampai lineage diselesaikan.'
                );
        }

        $deliverableQuantities = $this->getDeliverableQuantities(
            $do->po_customer_id,
            $do->id
        );

        return view(
            'delivery_orders.edit',
            compact('do', 'deliverableQuantities')
        );
    }

    public function update(Request $request, $id)
    {
        $do = DeliveryOrder::findOrFail($id);
        
        if ($do->status !== 'pending') {
            return back()->with('error', 'Only DO with status Pending can be updated.');
        }

        if ($do->details()->whereNull('po_customer_detail_id')->exists()) {
            return back()->with(
                'error',
                'Delivery Order ini memiliki legacy detail tanpa canonical PO Customer Detail lineage dan tidak dapat di-update sampai lineage diselesaikan.'
            );
        }

        $request->validate([
            'do_number' => 'required|string|unique:delivery_orders,do_number,' . $do->id,
            'delivery_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.po_customer_detail_id' => 'required|exists:po_customer_details,id',
            'items.*.quantity' => 'required|integer|min:0',
            'shipping_address' => 'nullable|string',
            'receiver_name' => 'nullable|string',
            'notes' => 'nullable|string',
            'delivery_by' => 'nullable|string|in:Wisnu,JNE,Courier Online',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        DB::beginTransaction();
        try {
            $deliverableQuantities = $this->getDeliverableQuantities(
                $do->po_customer_id,
                $do->id
            );

            $validatedItems = [];
            $positiveItemCount = 0;

            foreach ($request->items as $item) {
                $poCustomerDetail = PoCustomerDetail::where(
                    'id',
                    $item['po_customer_detail_id']
                )
                    ->where('po_customer_id', $do->po_customer_id)
                    ->first();

                if (!$poCustomerDetail) {
                    throw ValidationException::withMessages([
                        'items' => 'Item Delivery Order tidak valid untuk PO Customer Delivery Order ini.',
                    ]);
                }

                $requestedQuantity = (float) $item['quantity'];

                if ($requestedQuantity <= 0) {
                    continue;
                }

                $positiveItemCount++;

                $availableQuantity = (float) (
                    $deliverableQuantities[$poCustomerDetail->id] ?? 0
                );

                if ($requestedQuantity > $availableQuantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Quantity Delivery Order melebihi quantity yang tersedia berdasarkan Goods Receipt dan reservasi Delivery Order lainnya.',
                    ]);
                }

                $validatedItems[] = [
                    'po_customer_detail_id' => $poCustomerDetail->id,
                    'product_id' => $poCustomerDetail->product_id,
                    'quantity' => $requestedQuantity,
                ];
            }

            if ($positiveItemCount === 0) {
                throw ValidationException::withMessages([
                    'items' => 'Minimal satu quantity Delivery Order harus lebih dari nol.',
                ]);
            }

            $attachmentPath = $do->attachment;
            if ($request->hasFile('attachment')) {
                if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                    Storage::disk('public')->delete($attachmentPath);
                }

                $file = $request->file('attachment');
                $extension = $file->getClientOriginalExtension();
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $originalName) . '.' . $extension;

                $folder = 'delivery_orders';
                if (!Storage::disk('public')->exists($folder)) {
                    Storage::disk('public')->makeDirectory($folder);
                }

                try {
                    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                        $image = ImageManager::gd()->read($file->getRealPath());
                        $image->resize(800, 800, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                        $image->toJpeg(80)->save(storage_path('app/public/' . $folder . '/' . $safeName));
                    } else {
                        Storage::disk('public')->putFileAs($folder, $file, $safeName);
                    }
                    $attachmentPath = $folder . '/' . $safeName;
                } catch (\Exception $e) {
                    \Log::warning('Gagal memproses attachment: ' . $e->getMessage());
                    Storage::disk('public')->putFileAs($folder, $file, $safeName);
                    $attachmentPath = $folder . '/' . $safeName;
                }
            }

            $do->update([
                'do_number' => $request->do_number,
                'delivery_date' => $request->delivery_date,
                'shipping_address' => $request->shipping_address,
                'receiver_name' => $request->receiver_name,
                'notes' => $request->notes,
                'delivery_by' => $request->delivery_by,
                'attachment' => $attachmentPath,
            ]);

            $do->details()->delete();

            foreach ($validatedItems as $item) {
                DeliveryOrderDetail::create([
                    'delivery_order_id' => $do->id,
                    'po_customer_detail_id' => $item['po_customer_detail_id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();
            return redirect()->route('delivery-orders.index')
                ->with('success', 'Delivery Order successfully updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saat update DO: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Failed to update Delivery Order: ' . $e->getMessage());
        }
    }

    public function markDelivered($id)
    {
        $user = auth()->user();

        if (
            !$user ||
            !$user->hasPermission('delivery_order_mark_delivered')
        ) {
            abort(
                403,
                'You do not have permission to mark Delivery Order as Delivered.'
            );
        }

        $do = DeliveryOrder::findOrFail($id);

        if ($do->status !== 'pending') {
            return back()->with(
                'error',
                'Only Delivery Order with status Pending can be marked as Delivered.'
            );
        }

        $do->status = 'delivered';
        $do->save();

        return back()->with(
            'success',
            'Delivery Order successfully marked as Delivered.'
        );
    }

    public function reopen($id)
    {
        $user = auth()->user();

        if (
            !$user ||
            !$user->hasPermission('delivery_order_reopen')
        ) {
            abort(
                403,
                'You do not have permission to reopen Delivery Order.'
            );
        }

        $do = DeliveryOrder::findOrFail($id);

        if ($do->status !== 'delivered') {
            return back()->with(
                'error',
                'Only Delivered Delivery Order can be reopened.'
            );
        }

        $do->status = 'pending';
        $do->save();

        return back()->with(
            'success',
            'Delivery Order successfully reopened to Pending.'
        );
    }

    public function destroy($id)
    {
        $do = DeliveryOrder::findOrFail($id);
        if ($do->status != 'pending') {
            return back()->with('error', 'Only DO with status Pending can be deleted.');
        }
        try {
            $do->details()->delete();
            $do->delete();
            if ($do->attachment && Storage::disk('public')->exists($do->attachment)) {
                Storage::disk('public')->delete($do->attachment);
            }
            return redirect()->route('delivery-orders.index')->with('success', 'Delivery Order successfully deleted.');
        } catch (\Exception $e) {
            \Log::error('Error saat delete DO: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete Delivery Order: ' . $e->getMessage());
        }
    }

    public function getInvoicesByPo($po_customer_id)
    {
        // Hanya ambil invoice yang statusnya bukan cancelled dan belum digunakan di DO
        $invoices = \App\Models\InvoiceCustomer::where('po_customer_id', $po_customer_id)
            ->where('status', '!=', 'cancelled')
            ->whereNotIn('invoice_number', function ($query) {
                $query->select('invoice_number')->from('delivery_orders')->whereNotNull('invoice_number');
            })
            ->select('invoice_number', 'type', 'tax_invoice_number')
            ->get();

        foreach ($invoices as $invoice) {
            if (!isset($invoice->tax_invoice_number)) {
                $invoice->tax_invoice_number = ''; 
            }
        }

        return response()->json($invoices);
    }

    public function print($id)
    {
        try {
            $do = DeliveryOrder::with(['poCustomer.customer', 'details.product'])->findOrFail($id);
            $company = Company::where('is_active', true)->first();
            $pdf = Pdf::loadView('delivery_orders.print', compact('do', 'company'));
            $pdf->setPaper('a4', 'portrait');
            
            $safeNumber = str_replace('/', '_', $do->do_number);
            $filename = 'DeliveryOrder_' . $safeNumber . '.pdf';
            
            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Log::error('Error saat print DO: ' . $e->getMessage());
            return back()->with('error', 'Failed to print Delivery Order: ' . $e->getMessage());
        }
    }

    public function printReceipt($id)
    {
        try {
            $do = DeliveryOrder::with(['poCustomer.customer'])->findOrFail($id);
            $company = Company::where('is_active', true)->first();
            $pdf = Pdf::loadView('delivery_orders.receipt_print', compact('do', 'company'));
            $pdf->setPaper('a4', 'portrait');
            
            $safeNumber = str_replace('/', '_', $do->do_number);
            $filename = 'TandaTerima_' . $safeNumber . '.pdf';
            
            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Log::error('Error saat print receipt: ' . $e->getMessage());
            return back()->with('error', 'Failed to print Receipt: ' . $e->getMessage());
        }
    }

    // =====================================================
    // PRIVATE HELPER: ROMAN MONTH
    // =====================================================
    private function romanMonth($monthNum)
    {
        $month = (int) $monthNum;
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $romans[$month] ?? 'I';
    }

    /**
     * Quantity yang boleh dikirim dihitung per canonical PO Customer Detail:
     * active Goods Receipt quantity dikurangi seluruh quantity DO yang sudah
     * mereservasi PO Customer Detail yang sama.
     *
     * Saat Edit, DO yang sedang diedit dapat dikecualikan agar quantity miliknya
     * sendiri kembali tersedia untuk validasi.
     */
    private function getDeliverableQuantities(
        int $poCustomerId,
        ?int $excludeDeliveryOrderId = null
    ): array {
        $received = DB::table('goods_receipt_details as grd')
            ->join('goods_receipts as gr', 'gr.id', '=', 'grd.goods_receipt_id')
            ->join('po_supplier_details as psd', 'psd.id', '=', 'grd.po_supplier_detail_id')
            ->join('po_customer_details as pcd', 'pcd.id', '=', 'psd.po_customer_detail_id')
            ->join('po_suppliers as ps', 'ps.id', '=', 'psd.po_supplier_id')
            ->where('pcd.po_customer_id', $poCustomerId)
            ->where('ps.po_customer_id', $poCustomerId)
            ->where('ps.status', '!=', 'cancelled')
            ->where('gr.status', '!=', 'cancelled')
            ->groupBy('psd.po_customer_detail_id')
            ->selectRaw(
                'psd.po_customer_detail_id, SUM(grd.quantity_received) as received_quantity'
            )
            ->pluck('received_quantity', 'psd.po_customer_detail_id');

        $reservedQuery = DB::table('delivery_order_details as dod')
            ->join('delivery_orders as do', 'do.id', '=', 'dod.delivery_order_id')
            ->where('do.po_customer_id', $poCustomerId)
            ->whereNotNull('dod.po_customer_detail_id');

        if ($excludeDeliveryOrderId !== null) {
            $reservedQuery->where('do.id', '!=', $excludeDeliveryOrderId);
        }

        $reserved = $reservedQuery
            ->groupBy('dod.po_customer_detail_id')
            ->selectRaw(
                'dod.po_customer_detail_id, SUM(dod.quantity) as reserved_quantity'
            )
            ->pluck('reserved_quantity', 'dod.po_customer_detail_id');

        return $received->mapWithKeys(
            function ($quantity, $poCustomerDetailId) use ($reserved) {
                return [
                    (int) $poCustomerDetailId => max(
                        0,
                        (float) $quantity
                        - (float) ($reserved[$poCustomerDetailId] ?? 0)
                    ),
                ];
            }
        )->all();
    }
}
