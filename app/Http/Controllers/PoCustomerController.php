<?php

namespace App\Http\Controllers;

use App\Models\PoCustomer;
use App\Models\PoCustomerDetail;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\InvoiceCustomer;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class PoCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = PoCustomer::with('customer');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('invoice_status')) {
            $query->where('invoice_status', $request->invoice_status);
        }

        $poCustomers = $query->orderBy('id', 'desc')->paginate(8);
        
        foreach ($poCustomers as $po) {
            $totalInvoiced = $po->total_invoiced;
            $po->total_invoiced = $totalInvoiced;
            $po->remaining_total = $po->total - $totalInvoiced;
        }

        $poCustomers->appends(
    $request->only(['search', 'status', 'invoice_status'])
);
        return view('po_customers.index', compact('poCustomers'));
    }

    public function create()
    {
        $customers = Customer::all();
        $products = Product::all();
        $quotations = Quotation::whereIn('status', ['sent', 'approved'])
                       ->orderBy('id', 'desc')
                       ->get();
        $existingDeliveryTimes = Quotation::whereNotNull('delivery_time')
                                        ->where('delivery_time', '!=', '')
                                        ->pluck('delivery_time')
                                        ->unique()
                                        ->toArray();
        $existingPaymentTerms = Quotation::whereNotNull('payment_terms')
                                        ->where('payment_terms', '!=', '')
                                        ->pluck('payment_terms')
                                        ->unique()
                                        ->toArray();
        return view('po_customers.create', compact('customers', 'products', 'quotations', 'existingDeliveryTimes', 'existingPaymentTerms'));
    }

    // ============================================================
    // STORE ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ dengan try-catch dan notifikasi error
    // ============================================================
    public function store(Request $request)
    {
        \Log::info('PO Store Request Data:', $request->all());

        try {
            $request->validate([
                'po_number' => 'required|string|max:50|unique:po_customers,po_number',
                'customer_id' => $request->source_type == 'manual' ? 'required|exists:customers,id' : 'nullable',
                'quotation_id' => $request->source_type == 'quotation' ? 'required|exists:quotations,id' : 'nullable',
                'po_date' => 'required|date',
                'delivery_date' => 'nullable|date|after:po_date',
                'shipping_address' => 'required|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'discount_percent' => 'nullable|numeric|min:0|max:100',
                'discount_amount' => 'nullable|numeric|min:0',
                'tax_percent' => 'nullable|numeric|min:0|max:100',
                'tax_amount' => 'nullable|numeric|min:0',
                'pph_percent' => 'nullable|numeric|min:0|max:100',
                'pph_amount' => 'nullable|numeric|min:0',
                'payment_terms' => 'nullable|string|max:150',
                'delivery_time' => 'nullable|string|max:150',
                'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
            ], [
                'po_number.unique' => 'Nomor PO ":input" sudah terdaftar. Silakan gunakan nomor lain.',
                'delivery_date.after' => 'Tanggal pengiriman harus lebih besar dari tanggal PO.',
                'shipping_address.required' => 'Alamat pengiriman wajib diisi.',
            ]);

            $customerId = $request->customer_id;
            if ($request->source_type == 'quotation' && $request->quotation_id) {
                $quotationData = Quotation::find($request->quotation_id);
                $customerId = $quotationData ? $quotationData->customer_id : $customerId;
            }

            // Hitung subtotal
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $discountPercent = $request->discount_percent ?? 0;
            $discountAmount = $request->discount_amount ?? 0;
            $dppAmount = $subtotal - $discountAmount;
            $taxPercent = $request->tax_percent ?? 11;
            $taxAmount = $request->tax_amount ?? ($dppAmount * ($taxPercent / 100));
            $pphPercent = $request->pph_percent ?? 0;
            $pphAmount = $request->pph_amount ?? ($dppAmount * ($pphPercent / 100));
            $total = $dppAmount + $taxAmount - $pphAmount;

            // Upload attachment
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $extension = strtolower($file->getClientOriginalExtension());
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $originalName) . '.' . $extension;
                
                $folder = 'po_customers';
                if (!Storage::disk('public')->exists($folder)) {
                    Storage::disk('public')->makeDirectory($folder);
                }

                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    // PERBAIKAN: Menggunakan ImageManager versi 3
                    try {
                        $manager = ImageManager::gd(); // atau ->imagick() jika tersedia
                        $image = $manager->read($file->getRealPath());
                        $image->resize(800, 800); // lebar 800, tinggi menyesuaikan
                        $image->toJpeg(80)->save(storage_path('app/public/' . $folder . '/' . $safeName));
                    } catch (\Exception $e) {
                        // Fallback: simpan asli jika gagal kompres
                        Storage::disk('public')->putFileAs($folder, $file, $safeName);
                    }
                } else {
                    Storage::disk('public')->putFileAs($folder, $file, $safeName);
                }
                $attachmentPath = $folder . '/' . $safeName;
            }

            // Simpan PO
            $poCustomer = PoCustomer::create([
                'po_number' => $request->po_number,
                'customer_id' => $customerId,
                'quotation_id' => $request->source_type == 'quotation' ? $request->quotation_id : null,
                'payment_terms' => $request->payment_terms,
                'delivery_time' => $request->delivery_time,
                'po_date' => $request->po_date,
                'delivery_date' => $request->delivery_date,
                'shipping_address' => $request->shipping_address,
                'status' => 'received',
                'subtotal' => $subtotal,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'pph_percent' => $pphPercent,
                'pph_amount' => $pphAmount,
                'total' => $total,
                'remaining_amount' => $total,
                'notes' => $request->notes,
                'attachment' => $attachmentPath,
            ]);

            // Simpan detail
            foreach ($request->items as $item) {
                PoCustomerDetail::create([
                    'po_customer_id' => $poCustomer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price']
                ]);
            }

            // Update customer shipping address
            if ($request->has('update_customer_master_address') && $customerId) {
                Customer::where('id', $customerId)->update([
                    'shipping_address' => $request->shipping_address 
                ]);
            }

            // Update quotation status
            if ($request->source_type == 'quotation' && $request->quotation_id) {
                $quotation = Quotation::find($request->quotation_id);
                if ($quotation) {
                    $updateData = ['status' => 'rec_po'];
                    if ($request->has('update_quotation_master_terms')) {
                        $updateData['payment_terms'] = $request->payment_terms;
                        $updateData['delivery_time'] = $request->delivery_time;
                    }
                    $quotation->update($updateData);
                }
            }

            return redirect()->route('po-customers.index')
                ->with('success', 'Customer Purchase Order berhasil dibuat.');

        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->validator);
        } catch (QueryException $e) {
            $errorMessage = 'Gagal menyimpan PO. ';
            if ($e->getCode() == 23000) {
                $errorMessage .= 'Data duplikat ditemukan. Pastikan nomor PO unik.';
            } else {
                $errorMessage .= $e->getMessage();
            }
            return back()->withInput()->with('error', $errorMessage);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan PO: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $poCustomer = PoCustomer::with('customer', 'quotation', 'details.product')->findOrFail($id);
        return view('po_customers.show', compact('poCustomer'));
    }

    // ============================================================
    // EDIT ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ dengan pengecekan apakah PO sudah digunakan
    // ============================================================
    public function edit($id) 
    {
        $poCustomer = PoCustomer::with('details.product', 'customer')->findOrFail($id);

        // Cek apakah PO sudah memiliki Invoice atau Delivery Order
        if ($this->isPoCustomerEditLocked($poCustomer)) {
            return redirect()->route('po-customers.index')
                ->with('error', 'PO tidak dapat diedit karena Invoice Customer terkait sudah Paid.');
        }

        $products = Product::orderBy('name')->get();
        $customers = Customer::all();
        $existingDeliveryTimes = Quotation::whereNotNull('delivery_time')
                                        ->where('delivery_time', '!=', '')
                                        ->pluck('delivery_time')
                                        ->unique()
                                        ->toArray();
        $existingPaymentTerms = Quotation::whereNotNull('payment_terms')
                                        ->where('payment_terms', '!=', '')
                                        ->pluck('payment_terms')
                                        ->unique()
                                        ->toArray();
        return view('po_customers.edit', compact('poCustomer', 'customers', 'products', 'existingDeliveryTimes', 'existingPaymentTerms'));
    }

    // ============================================================
    // UPDATE ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ dengan try-catch dan notifikasi error
    // ============================================================
    public function update(Request $request, $id)
    {
        $poCustomer = PoCustomer::with('details.product')
            ->findOrFail($id);

        if ($this->isPoCustomerEditLocked($poCustomer)) {
            return back()->with(
                'error',
                'PO tidak dapat diupdate karena Invoice Customer terkait sudah Paid.'
            );
        }

        try {
            $request->validate([
                'po_number' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique(
                        'po_customers',
                        'po_number'
                    )->ignore($poCustomer->id),
                ],
                'po_date' =>
                    'required|date',
                'delivery_date' =>
                    'nullable|date|after:po_date',
                'items' =>
                    'required|array|min:1',
                'items.*.detail_id' =>
                    'nullable|integer|exists:po_customer_details,id',
                'items.*.product_id' =>
                    'required|exists:products,id',
                'items.*.quantity' =>
                    'required|integer|min:1',
                'items.*.unit_price' =>
                    'required|numeric|min:0',
                'payment_terms' =>
                    'nullable|string|max:100',
                'delivery_time' =>
                    'nullable|string|max:150',
                'attachment' =>
                    'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
            ], [
                'po_number.unique' =>
                    'Nomor PO ":input" sudah terdaftar. Silakan gunakan nomor lain.',
                'delivery_date.after' =>
                    'Tanggal pengiriman harus lebih besar dari tanggal PO.',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Validate detail lineage before changing anything
            |--------------------------------------------------------------------------
            */

            $existingDetails = $poCustomer
                ->details
                ->keyBy('id');

            $submittedIds = collect($request->items)
                ->pluck('detail_id')
                ->filter(function ($value) {
                    return $value !== null &&
                           $value !== '';
                })
                ->map(function ($value) {
                    return (int) $value;
                });

            if ($submittedIds->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' =>
                        'Detail PO Customer yang sama tidak boleh dikirim lebih dari satu kali.',
                ]);
            }

            foreach ($request->items as $index => $item) {

                if (empty($item['detail_id'])) {
                    continue;
                }

                $detailId = (int) $item['detail_id'];

                if (!$existingDetails->has($detailId)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.detail_id" =>
                            'Detail PO Customer tidak valid atau bukan milik PO ini.',
                    ]);
                }

                $detail = $existingDetails->get($detailId);

                $allocatedQuantity =
                    (float) DB::table(
                        'po_supplier_details as psd'
                    )
                    ->join(
                        'po_suppliers as ps',
                        'ps.id',
                        '=',
                        'psd.po_supplier_id'
                    )
                    ->where(
                        'psd.po_customer_detail_id',
                        $detailId
                    )
                    ->where(
                        'ps.status',
                        '!=',
                        'cancelled'
                    )
                    ->sum('psd.quantity');

                if (
                    $allocatedQuantity > 0 &&
                    (int) $detail->product_id !==
                    (int) $item['product_id']
                ) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" =>
                            'Product tidak dapat diganti karena item ini sudah digunakan pada PO Supplier.',
                    ]);
                }

                if (
                    (float) $item['quantity'] <
                    $allocatedQuantity
                ) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" =>
                            'Quantity tidak boleh lebih kecil dari quantity yang sudah dialokasikan ke PO Supplier (' .
                            $allocatedQuantity .
                            ').',
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Existing allocated rows may not disappear from request
            |--------------------------------------------------------------------------
            */

            foreach ($existingDetails as $detailId => $detail) {

                if ($submittedIds->contains((int) $detailId)) {
                    continue;
                }

                $allocatedQuantity =
                    (float) DB::table(
                        'po_supplier_details as psd'
                    )
                    ->join(
                        'po_suppliers as ps',
                        'ps.id',
                        '=',
                        'psd.po_supplier_id'
                    )
                    ->where(
                        'psd.po_customer_detail_id',
                        $detailId
                    )
                    ->where(
                        'ps.status',
                        '!=',
                        'cancelled'
                    )
                    ->sum('psd.quantity');

                if ($allocatedQuantity > 0) {
                    throw ValidationException::withMessages([
                        'items' =>
                            'Item "' .
                            (
                                $detail->product->name2 ??
                                $detail->product->name ??
                                ('Product ID ' . $detail->product_id)
                            ) .
                            '" tidak dapat dihapus karena sudah digunakan pada PO Supplier.',
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate totals
            |--------------------------------------------------------------------------
            */

            $subtotal = 0;

            foreach ($request->items as $item) {
                $subtotal +=
                    ((float) $item['quantity']) *
                    ((float) $item['unit_price']);
            }

            $discountPercent =
                (float) ($request->discount_percent ?? 0);

            $discountAmount =
                (float) ($request->discount_amount ?? 0);

            $dppAmount =
                $subtotal - $discountAmount;

            $taxPercent =
                (float) ($request->tax_percent ?? 11);

            $taxAmount =
                $request->tax_amount !== null
                    ? (float) $request->tax_amount
                    : ($dppAmount * ($taxPercent / 100));

            $pphPercent =
                (float) ($request->pph_percent ?? 0);

            $pphAmount =
                $request->pph_amount !== null
                    ? (float) $request->pph_amount
                    : ($dppAmount * ($pphPercent / 100));

            $total =
                $dppAmount +
                $taxAmount -
                $pphAmount;

            /*
            |--------------------------------------------------------------------------
            | Attachment
            |--------------------------------------------------------------------------
            */

            $attachmentPath =
                $poCustomer->attachment;

            if (
                $request->input('delete_attachment') == '1' ||
                $request->hasFile('attachment')
            ) {
                if (
                    $attachmentPath &&
                    Storage::disk('public')
                        ->exists($attachmentPath)
                ) {
                    Storage::disk('public')
                        ->delete($attachmentPath);
                }

                if (
                    $request->input('delete_attachment') == '1'
                ) {
                    $attachmentPath = null;
                }
            }

            if ($request->hasFile('attachment')) {

                $file =
                    $request->file('attachment');

                $extension =
                    strtolower(
                        $file->getClientOriginalExtension()
                    );

                $originalName =
                    pathinfo(
                        $file->getClientOriginalName(),
                        PATHINFO_FILENAME
                    );

                $safeName =
                    time() .
                    '_' .
                    preg_replace(
                        '/[^A-Za-z0-9_-]/',
                        '_',
                        $originalName
                    ) .
                    '.' .
                    $extension;

                $folder = 'po_customers';

                if (
                    !Storage::disk('public')
                        ->exists($folder)
                ) {
                    Storage::disk('public')
                        ->makeDirectory($folder);
                }

                if (
                    in_array(
                        $extension,
                        ['jpg', 'jpeg', 'png', 'gif', 'webp']
                    )
                ) {
                    try {
                        $manager =
                            ImageManager::gd();

                        $image =
                            $manager->read(
                                $file->getRealPath()
                            );

                        $image->resize(800, 800);

                        $image->toJpeg(80)->save(
                            storage_path(
                                'app/public/' .
                                $folder .
                                '/' .
                                $safeName
                            )
                        );

                    } catch (\Exception $e) {

                        Storage::disk('public')
                            ->putFileAs(
                                $folder,
                                $file,
                                $safeName
                            );
                    }

                } else {

                    Storage::disk('public')
                        ->putFileAs(
                            $folder,
                            $file,
                            $safeName
                        );
                }

                $attachmentPath =
                    $folder . '/' . $safeName;
            }

            /*
            |--------------------------------------------------------------------------
            | Atomic database update
            |--------------------------------------------------------------------------
            */

            DB::transaction(function () use (
                $poCustomer,
                $request,
                $subtotal,
                $taxPercent,
                $taxAmount,
                $discountPercent,
                $discountAmount,
                $pphPercent,
                $pphAmount,
                $total,
                $attachmentPath
            ) {

                $lockedPo =
                    PoCustomer::where(
                        'id',
                        $poCustomer->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedDetails =
                    $lockedPo
                        ->details()
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                /*
                | Revalidate downstream allocation while locked.
                */

                foreach ($request->items as $index => $item) {

                    if (empty($item['detail_id'])) {
                        continue;
                    }

                    $detailId =
                        (int) $item['detail_id'];

                    if (!$lockedDetails->has($detailId)) {
                        throw ValidationException::withMessages([
                            "items.{$index}.detail_id" =>
                                'Detail PO Customer berubah selama proses update. Silakan muat ulang halaman.',
                        ]);
                    }

                    $detail =
                        $lockedDetails->get($detailId);

                    $allocatedQuantity =
                        (float) DB::table(
                            'po_supplier_details as psd'
                        )
                        ->join(
                            'po_suppliers as ps',
                            'ps.id',
                            '=',
                            'psd.po_supplier_id'
                        )
                        ->where(
                            'psd.po_customer_detail_id',
                            $detailId
                        )
                        ->where(
                            'ps.status',
                            '!=',
                            'cancelled'
                        )
                        ->sum('psd.quantity');

                    if (
                        $allocatedQuantity > 0 &&
                        (int) $detail->product_id !==
                        (int) $item['product_id']
                    ) {
                        throw ValidationException::withMessages([
                            "items.{$index}.product_id" =>
                                'Product tidak dapat diganti karena item sudah digunakan pada PO Supplier.',
                        ]);
                    }

                    if (
                        (float) $item['quantity'] <
                        $allocatedQuantity
                    ) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity" =>
                                'Quantity PO Customer tidak boleh lebih kecil dari quantity yang sudah dialokasikan ke PO Supplier.',
                        ]);
                    }
                }

                /*
                | Update header.
                */

                $lockedPo->update([
                    'po_number' =>
                        $request->po_number,
                    'po_date' =>
                        $request->po_date,
                    'delivery_date' =>
                        $request->delivery_date,
                    'subtotal' =>
                        $subtotal,
                    'tax_percent' =>
                        $taxPercent,
                    'tax_amount' =>
                        $taxAmount,
                    'discount_percent' =>
                        $discountPercent,
                    'discount_amount' =>
                        $discountAmount,
                    'pph_percent' =>
                        $pphPercent,
                    'pph_amount' =>
                        $pphAmount,
                    'total' =>
                        $total,
                    'remaining_amount' => max(
                        0,
                        $total - (float) $lockedPo->total_invoiced
                    ),
                    'notes' =>
                        $request->notes,
                    'attachment' =>
                        $attachmentPath,
                    'payment_terms' =>
                        $request->payment_terms,
                    'delivery_time' =>
                        $request->delivery_time,
                ]);

                $keptIds = [];

                /*
                | Update existing / create new details.
                */

                foreach ($request->items as $item) {

                    $quantity =
                        (float) $item['quantity'];

                    $unitPrice =
                        (float) $item['unit_price'];

                    $detailSubtotal =
                        $quantity * $unitPrice;

                    if (!empty($item['detail_id'])) {

                        $detailId =
                            (int) $item['detail_id'];

                        $detail =
                            $lockedDetails->get($detailId);

                        $detail->update([
                            'product_id' =>
                                $item['product_id'],
                            'quantity' =>
                                $quantity,
                            'unit_price' =>
                                $unitPrice,
                            'subtotal' =>
                                $detailSubtotal,
                        ]);

                        $keptIds[] =
                            $detailId;

                    } else {

                        $newDetail =
                            PoCustomerDetail::create([
                                'po_customer_id' =>
                                    $lockedPo->id,
                                'product_id' =>
                                    $item['product_id'],
                                'quantity' =>
                                    $quantity,
                                'unit_price' =>
                                    $unitPrice,
                                'subtotal' =>
                                    $detailSubtotal,
                            ]);

                        $keptIds[] =
                            $newDetail->id;
                    }
                }

                /*
                | Delete only details removed from form AND unused downstream.
                */

                foreach ($lockedDetails as $detailId => $detail) {

                    if (
                        in_array(
                            (int) $detailId,
                            $keptIds,
                            true
                        )
                    ) {
                        continue;
                    }

                    $hasAllocation =
                        DB::table(
                            'po_supplier_details as psd'
                        )
                        ->join(
                            'po_suppliers as ps',
                            'ps.id',
                            '=',
                            'psd.po_supplier_id'
                        )
                        ->where(
                            'psd.po_customer_detail_id',
                            $detailId
                        )
                        ->where(
                            'ps.status',
                            '!=',
                            'cancelled'
                        )
                        ->exists();

                    if ($hasAllocation) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'Detail PO Customer yang sudah digunakan pada PO Supplier tidak dapat dihapus.',
                        ]);
                    }

                    $detail->delete();
                }

                /*
                |--------------------------------------------------------------------------
                | Recalculate procurement status
                |--------------------------------------------------------------------------
                */

                $currentDetails =
                    $lockedPo
                        ->details()
                        ->get();

                $hasAllocation =
                    false;

                $fullyProcured =
                    !$currentDetails->isEmpty();

                foreach ($currentDetails as $detail) {

                    $allocated =
                        (float) DB::table(
                            'po_supplier_details as psd'
                        )
                        ->join(
                            'po_suppliers as ps',
                            'ps.id',
                            '=',
                            'psd.po_supplier_id'
                        )
                        ->where(
                            'psd.po_customer_detail_id',
                            $detail->id
                        )
                        ->where(
                            'ps.status',
                            '!=',
                            'cancelled'
                        )
                        ->sum('psd.quantity');

                    if ($allocated > 0) {
                        $hasAllocation = true;
                    }

                    if (
                        $allocated <
                        (float) $detail->quantity
                    ) {
                        $fullyProcured = false;
                    }
                }

                if (!$hasAllocation) {

                    $procurementStatus =
                        'pending';

                } elseif ($fullyProcured) {

                    $procurementStatus =
                        'fully_procured';

                } else {

                    $procurementStatus =
                        'partial';
                }

                /*
                | Business status:
                | no procurement = received
                | any procurement = proceed
                */

                $businessStatus =
                    $hasAllocation
                        ? 'proceed'
                        : 'received';

                $lockedPo->update([
                    'status' =>
                        $businessStatus,
                    'procurement_status' =>
                        $procurementStatus,
                ]);
            });

            return redirect()
                ->route('po-customers.index')
                ->with(
                    'success',
                    'Customer PO berhasil diperbarui.'
                );

        } catch (ValidationException $e) {

            return back()
                ->withInput()
                ->withErrors($e->validator);

        } catch (QueryException $e) {

            $driverCode =
                $e->errorInfo[1] ?? null;

            if ((int) $driverCode === 1062) {

                $message =
                    'Nomor PO sudah digunakan oleh PO Customer lain.';

            } elseif ((int) $driverCode === 1451) {

                $message =
                    'Detail PO tidak dapat dihapus karena sudah digunakan oleh transaksi downstream.';

            } else {

                $message =
                    'Terjadi database integrity error. Perubahan tidak disimpan.';
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal memperbarui PO. ' .
                    $message
                );

        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal memperbarui PO: ' .
                    $e->getMessage()
                );
        }
    }
    // ============================================================
    public function destroy($id)
    {
        $poCustomer = PoCustomer::findOrFail($id);

        // Cek apakah PO sudah memiliki Invoice atau Delivery Order
        if ($this->isPoCustomerUsed($poCustomer)) {
            return back()->with('error', 'PO tidak dapat dihapus karena sudah memiliki Invoice atau Delivery Order.');
        }

        try {
            // Hapus attachment
            if ($poCustomer->attachment && Storage::disk('public')->exists($poCustomer->attachment)) {
                Storage::disk('public')->delete($poCustomer->attachment);
            }
            
            // Update quotation status jika ada
            if ($poCustomer->quotation_id) {
                $quotation = Quotation::find($poCustomer->quotation_id);
                if ($quotation && $quotation->status == 'rec_po') {
                    $quotation->update(['status' => 'sent']);
                }
            }
            
            $poCustomer->details()->delete();
            $poCustomer->delete();
            
            return redirect()->route('po-customers.index')
                ->with('success', 'Customer PO berhasil dihapus.');

        } catch (QueryException $e) {
            return back()->with('error', 'PO tidak dapat dihapus karena terkait dengan data lain.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus PO: ' . $e->getMessage());
        }
    }

    public function getQuotation($id)
    {
        $quotation = Quotation::with('customer', 'details.product')->findOrFail($id);
        
        return response()->json([
            'customer_id'      => $quotation->customer_id,
            'customer_name'    => $quotation->customer->name,
            'shipping_address' => $quotation->customer->shipping_address,
            'tax_percent' => $quotation->tax_percent ?? 0,
            'tax_amount' => $quotation->tax_amount ?? 0,
            'pph23_percent' => $quotation->pph23_percent ?? 0, 
            'pph23_amount' => $quotation->pph23_amount ?? 0,
            'discount_percent' => $quotation->discount_percent ?? 0,
            'discount_amount' => $quotation->discount_amount ?? 0,
            'payment_terms'    => $quotation->payment_terms,
            'delivery_time'    => $quotation->delivery_time,
            'subtotal' => $quotation->subtotal,
            'total' => $quotation->total,
            'remaining_amount' => $quotation->total,
            'items' => $quotation->details->map(function($item) {
                return [
                    'product_id'    => $item->product_id,
                    'product_code'  => $item->product->product_code,
                    'product_name'  => $item->product->name,
                    'product_code2' => $item->product->product_code2,
                    'name2'         => $item->product->name2,
                    'brand'         => $item->product->brand ?? '-',
                    'unit'          => $item->product->unit,
                    'quantity'      => $item->quantity,
                    'unit_price'    => $item->unit_price,
                    'subtotal'      => $item->subtotal,
                ];
            })
        ]);
    }

    public function viewImage($id)
    {
        $poCustomer = PoCustomer::findOrFail($id);

        if (!$poCustomer->attachment) {
            abort(404, 'File attachment data record not found.');
        }

        if (!Storage::disk('public')->exists($poCustomer->attachment)) {
            abort(404, 'Physical document file not found on storage server.');
        }

        $file = Storage::disk('public')->get($poCustomer->attachment);
        $type = Storage::disk('public')->mimeType($poCustomer->attachment);

        return response($file, 200)->header('Content-Type', $type);
    }

    // ============================================================
    // PRIVATE HELPER: CEK APAKAH PO SUDAH DIGUNAKAN
    // ============================================================
    /**
     * PO Customer may be edited while downstream transactions remain open.
     * A paid active Invoice Customer establishes financial finality.
     */
    private function isPoCustomerEditLocked(PoCustomer $poCustomer)
    {
        return InvoiceCustomer::where('po_customer_id', $poCustomer->id)
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->exists();
    }
    private function isPoCustomerUsed(PoCustomer $poCustomer)
    {
        // A PO Supplier is a direct downstream dependency of PO Customer.
        // Once procurement has started, the source PO Customer must be
        // protected from unsafe edit/delete operations.
        if ($poCustomer->poSuppliers()->exists()) {
            return true;
        }

        // Cek apakah PO sudah memiliki Invoice atau Delivery Order
        if ($poCustomer->invoiceCustomers()->exists() ||
            $poCustomer->deliveryOrders()->exists()) {
            return true;
        }

        return false;
    }
}

