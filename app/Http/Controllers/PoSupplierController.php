<?php

namespace App\Http\Controllers;

use App\Models\PoSupplier; 
use App\Models\PoSupplierDetail;
use App\Models\PoCustomer;
use App\Models\PoCustomerDetail;
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
    public function index(Request $request)
    {
        $query = PoSupplier::with('supplier');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('po_supplier_number', 'like', '%' . $search . '%')
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('receive_status')) {
            $query->where(
                'receipt_status',
                $request->input('receive_status')
            );
        }

        $poSuppliers = $query
            ->withCount([
                'goodsReceipts as goods_receipts_count' => function ($q) {
                    $q->where('status', '!=', 'cancelled');
                }
            ])
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return view('po_suppliers.index', compact('poSuppliers'));
    }

    public function create()
    {
        /*
         * Create PO Supplier hanya menampilkan Customer yang masih
         * mempunyai minimal satu PO Customer dengan remaining qty.
         */
        $customers = Customer::query()
            ->whereHas('poCustomers', function ($poQuery) {
                $poQuery
                    ->where('status', '!=', 'cancelled')
                    ->whereHas('details', function ($detailQuery) {
                        $detailQuery->whereRaw(
                            'po_customer_details.quantity > (
                                SELECT COALESCE(SUM(psd.quantity), 0)
                                FROM po_supplier_details psd
                                INNER JOIN po_suppliers ps
                                    ON ps.id = psd.po_supplier_id
                                WHERE psd.po_customer_detail_id = po_customer_details.id
                                  AND ps.status != ?
                            )',
                            ['cancelled']
                        );
                    });
            })
            ->orderBy('name')
            ->get();
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        return view('po_suppliers.create', compact('customers', 'suppliers', 'products'));
    }

    public function getPoCustomerDetails($id)
    {
        $poCustomer = PoCustomer::with([
            'customer',
            'details.product'
        ])->findOrFail($id);

        if ($poCustomer->status === 'cancelled') {
            abort(422, 'PO Customer sudah dibatalkan.');
        }

        $items = [];

        foreach ($poCustomer->details as $detail) {

            $allocatedQuantity = PoSupplierDetail::query()
                ->join(
                    'po_suppliers',
                    'po_suppliers.id',
                    '=',
                    'po_supplier_details.po_supplier_id'
                )
                ->where(
                    'po_supplier_details.po_customer_detail_id',
                    $detail->id
                )
                ->where(
                    'po_suppliers.status',
                    '!=',
                    'cancelled'
                )
                ->sum('po_supplier_details.quantity');

            $remainingQuantity =
                max(0, (float) $detail->quantity - (float) $allocatedQuantity);

            if ($remainingQuantity <= 0) {
                continue;
            }

            $items[] = [
                'po_customer_detail_id' => $detail->id,
                'product_id' => $detail->product_id,
                'product_name' => $detail->product->name,
                'product_code' => $detail->product->product_code,
                'brand' => $detail->product->brand,
                'unit' => $detail->product->unit,
                'ordered_quantity' => $detail->quantity,
                'allocated_quantity' => $allocatedQuantity,
                'remaining_quantity' => $remainingQuantity,
                'quantity' => $remainingQuantity,
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
    // STORE ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ dengan try-catch dan notifikasi error
    // ============================================================
    public function store(Request $request)
    {
        try {

            $request->validate([
                'po_customer_id' => 'required|exists:po_customers,id',
                'supplier_id' => 'required|exists:suppliers,id',
                'po_date' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.po_customer_detail_id' =>
                    'required|exists:po_customer_details,id',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required',
                'items.*.purchase_price' => 'required',
                'items.*.master_price_decision' => 'nullable|in:update,keep',
                'notes' => 'nullable'
            ]);

            DB::beginTransaction();

            $poCustomer = PoCustomer::where(
                'id',
                $request->po_customer_id
            )->lockForUpdate()->firstOrFail();

            if ($poCustomer->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'po_customer_id' =>
                        'PO Customer sudah dibatalkan.'
                ]);
            }

            $processedItems = [];
            $subtotal = 0;

            foreach ($request->items as $key => $item) {

                $detail = PoCustomerDetail::where(
                    'id',
                    $item['po_customer_detail_id']
                )
                    ->where(
                        'po_customer_id',
                        $poCustomer->id
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$detail) {
                    throw ValidationException::withMessages([
                        "items.$key.po_customer_detail_id" =>
                            'Item tidak berasal dari PO Customer yang dipilih.'
                    ]);
                }

                if ((int) $detail->product_id !== (int) $item['product_id']) {
                    throw ValidationException::withMessages([
                        "items.$key.product_id" =>
                            'Produk tidak sesuai dengan detail PO Customer.'
                    ]);
                }

                $quantity =
                    (float) str_replace('.', '', $item['quantity']);

                $price =
                    (float) str_replace('.', '', $item['purchase_price']);

                /*
                 * Server-authoritative purchase price decision.
                 *
                 * Lock Product Master so comparison and optional
                 * overwrite occur against the current committed value.
                 */
                $productMaster = Product::where(
                    'id',
                    $detail->product_id
                )
                    ->lockForUpdate()
                    ->firstOrFail();

                $masterPurchasePrice =
                    round(
                        (float) $productMaster->purchase_price,
                        2
                    );

                $transactionPurchasePrice =
                    round(
                        (float) $price,
                        2
                    );

                $masterPriceDifferent =
                    $masterPurchasePrice !==
                    $transactionPurchasePrice;

                $masterPriceDecision =
                    $item['master_price_decision'] ?? null;

                if (
                    $masterPriceDifferent &&
                    !in_array(
                        $masterPriceDecision,
                        ['update', 'keep'],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        "items.$key.master_price_decision" =>
                            'Purchase price berbeda dari Product Master. ' .
                            'Pilih Update Master atau Keep Existing.'
                    ]);
                }

                /*
                 * Jika harga sama, keputusan master tidak diperlukan
                 * dan tidak boleh menyebabkan update yang tidak perlu.
                 */
                if (!$masterPriceDifferent) {
                    $masterPriceDecision = null;
                }

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        "items.$key.quantity" =>
                            'Quantity PO Supplier harus lebih besar dari 0.'
                    ]);
                }

                $allocatedQuantity = PoSupplierDetail::query()
                    ->join(
                        'po_suppliers',
                        'po_suppliers.id',
                        '=',
                        'po_supplier_details.po_supplier_id'
                    )
                    ->where(
                        'po_supplier_details.po_customer_detail_id',
                        $detail->id
                    )
                    ->where(
                        'po_suppliers.status',
                        '!=',
                        'cancelled'
                    )
                    ->sum('po_supplier_details.quantity');

                $remainingQuantity =
                    (float) $detail->quantity -
                    (float) $allocatedQuantity;

                if ($quantity > $remainingQuantity) {
                    throw ValidationException::withMessages([
                        "items.$key.quantity" =>
                            'Quantity melebihi sisa quantity PO Customer. Sisa: ' .
                            $remainingQuantity
                    ]);
                }

                $lineSubtotal = $quantity * $price;
                $subtotal += $lineSubtotal;

                $processedItems[] = [
                    'po_customer_detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'quantity' => $quantity,
                    'purchase_price' => $price,
                    'master_price_decision' => $masterPriceDecision,
                    'subtotal' => $lineSubtotal
                ];
            }

            $poDate = $request->po_date;
            $currentYear = date('Y', strtotime($poDate));
            $currentMonth = date('n', strtotime($poDate));

            $lastPoSupplier = PoSupplier::whereYear(
                'po_date',
                $currentYear
            )
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastPoSupplier) {
                $lastNumber = (int) explode(
                    '/',
                    $lastPoSupplier->po_supplier_number
                )[0];

                $newNumber = str_pad(
                    $lastNumber + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            } else {
                $newNumber = '0001';
            }

            $poSupplierNumber =
                $newNumber .
                '/PO/BGS/' .
                $this->romanMonth($currentMonth) .
                '/' .
                $currentYear;

            $taxPercent = (float) ($request->tax_percent ?? 0);
            $taxAmount = $subtotal * ($taxPercent / 100);
            $total = $subtotal + $taxAmount;

            $poSupplier = PoSupplier::create([
                'po_supplier_number' => $poSupplierNumber,
                'po_customer_id' => $poCustomer->id,
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

                /*
                 * Explicit user decision only.
                 *
                 * "keep":
                 *   Product Master remains unchanged.
                 *
                 * "update":
                 *   Product Master purchase_price becomes the
                 *   transaction purchase price.
                 *
                 * PO Supplier transaction price is always preserved.
                 */
                if (
                    ($item['master_price_decision'] ?? null) ===
                    'update'
                ) {
                    Product::where(
                        'id',
                        $item['product_id']
                    )->update([
                        'purchase_price' =>
                            $item['purchase_price']
                    ]);
                }

                PoSupplierDetail::create([
                    'po_supplier_id' => $poSupplier->id,
                    'po_customer_detail_id' =>
                        $item['po_customer_detail_id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'subtotal' => $item['subtotal']
                ]);
            }

            $this->refreshProcurementStatus($poCustomer->id);

            DB::commit();

            return redirect()
                ->route('po-suppliers.index')
                ->with(
                    'success',
                    'PO Supplier berhasil dibuat!'
                );

        } catch (ValidationException $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors($e->errors());

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menyimpan PO Supplier: ' .
                    $e->getMessage()
                );
        }
    }
    public function show($id)
    {
        $poSupplier = PoSupplier::with(['customer', 'supplier', 'poCustomer', 'details.product'])->findOrFail($id);
        return view('po_suppliers.show', compact('poSupplier'));
    }

    // ============================================================
    // EDIT ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ dengan pengecekan apakah PO Supplier sudah digunakan
    // ============================================================
    public function edit($id)
    {
        $poSupplier = PoSupplier::with([
            'customer',
            'supplier',
            'poCustomer.customer',
            'details.product',
        ])->findOrFail($id);

        if (
            !in_array(
                $poSupplier->status,
                ['draft', 'confirmed'],
                true
            )
        ) {
            return redirect()
                ->route(
                    'po-suppliers.show',
                    $poSupplier->id
                )
                ->with(
                    'error',
                    'PO Supplier dengan status ' .
                    ucfirst($poSupplier->status) .
                    ' tidak dapat diedit.'
                );
        }

        if ($this->isPoSupplierUsed($poSupplier)) {
            return redirect()
                ->route(
                    'po-suppliers.show',
                    $poSupplier->id
                )
                ->with(
                    'error',
                    'PO Supplier sudah memiliki penerimaan barang dan tidak dapat diedit.'
                );
        }

        /*
         * Allocation PO Supplier LAIN untuk PO Customer yang sama.
         *
         * Current PO Supplier dikecualikan supaya existing item dapat
         * dinaikkan / diturunkan berdasarkan available quantity aktual.
         */
        $allocatedByOthers =
            PoSupplierDetail::query()
                ->join(
                    'po_suppliers',
                    'po_suppliers.id',
                    '=',
                    'po_supplier_details.po_supplier_id'
                )
                ->where(
                    'po_suppliers.po_customer_id',
                    $poSupplier->po_customer_id
                )
                ->where(
                    'po_supplier_details.po_supplier_id',
                    '!=',
                    $poSupplier->id
                )
                ->where(
                    'po_suppliers.status',
                    '!=',
                    'cancelled'
                )
                ->groupBy(
                    'po_supplier_details.po_customer_detail_id'
                )
                ->selectRaw(
                    'po_supplier_details.po_customer_detail_id, ' .
                    'SUM(po_supplier_details.quantity) as allocated_qty'
                )
                ->pluck(
                    'allocated_qty',
                    'po_supplier_details.po_customer_detail_id'
                );

        $currentDetailIds =
            $poSupplier->details
                ->pluck('po_customer_detail_id')
                ->filter()
                ->map(
                    fn ($id) => (int) $id
                )
                ->values();

        /*
         * Add Item hanya berasal dari SAME PO Customer.
         * Existing item tidak ditawarkan lagi sebagai Add Item.
         */
        $eligiblePoCustomerDetails =
            PoCustomerDetail::with('product')
                ->where(
                    'po_customer_id',
                    $poSupplier->po_customer_id
                )
                ->get()
                ->filter(
                    function ($detail) use (
                        $allocatedByOthers,
                        $currentDetailIds
                    ) {
                        if (
                            $currentDetailIds->contains(
                                (int) $detail->id
                            )
                        ) {
                            return false;
                        }

                        $allocated =
                            (float) (
                                $allocatedByOthers[
                                    $detail->id
                                ] ?? 0
                            );

                        $remaining =
                            (float) $detail->quantity -
                            $allocated;

                        return $remaining > 0;
                    }
                )
                ->map(
                    function ($detail) use (
                        $allocatedByOthers
                    ) {
                        $allocated =
                            (float) (
                                $allocatedByOthers[
                                    $detail->id
                                ] ?? 0
                            );

                        $detail->remaining_quantity =
                            max(
                                0,
                                (float) $detail->quantity -
                                $allocated
                            );

                        return $detail;
                    }
                )
                ->values();

        return view(
            'po_suppliers.edit',
            compact(
                'poSupplier',
                'eligiblePoCustomerDetails'
            )
        );
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'items' =>
                'required|array|min:1',

            'items.*.po_customer_detail_id' =>
                'required|exists:po_customer_details,id',

            'items.*.product_id' =>
                'required|exists:products,id',

            'items.*.quantity' =>
                'required|numeric|min:0.0001',

            'items.*.purchase_price' =>
                'required|numeric|min:0',

            'items.*.master_price_decision' =>
                'nullable|in:update,keep',

            'discount_percent' =>
                'nullable|numeric|min:0|max:100',

            'notes' =>
                'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            /*
             * Lock transaction header first.
             *
             * Header / lifecycle fields remain authoritative from DB.
             */
            $poSupplier =
                PoSupplier::where(
                    'id',
                    $id
                )
                    ->lockForUpdate()
                    ->firstOrFail();

            if (
                !in_array(
                    $poSupplier->status,
                    ['draft', 'confirmed'],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'po_supplier' =>
                        'PO Supplier dengan status ' .
                        ucfirst($poSupplier->status) .
                        ' tidak dapat diedit.'
                ]);
            }

            if ($this->isPoSupplierUsed($poSupplier)) {
                throw ValidationException::withMessages([
                    'po_supplier' =>
                        'PO Supplier sudah memiliki penerimaan barang dan tidak dapat diedit.'
                ]);
            }

            $poCustomer =
                PoCustomer::where(
                    'id',
                    $poSupplier->po_customer_id
                )
                    ->lockForUpdate()
                    ->firstOrFail();

            if ($poCustomer->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'po_customer_id' =>
                        'PO Customer sudah dibatalkan.'
                ]);
            }

            $processedItems = [];
            $requestedByDetail = [];
            $subtotal = 0;

            foreach (
                $request->items as $key => $item
            ) {

                $detail =
                    PoCustomerDetail::where(
                        'id',
                        $item[
                            'po_customer_detail_id'
                        ]
                    )
                        ->where(
                            'po_customer_id',
                            $poCustomer->id
                        )
                        ->lockForUpdate()
                        ->first();

                if (!$detail) {
                    throw ValidationException::withMessages([
                        "items.$key.po_customer_detail_id" =>
                            'Item tidak berasal dari PO Customer terkait.'
                    ]);
                }

                if (
                    (int) $detail->product_id !==
                    (int) $item['product_id']
                ) {
                    throw ValidationException::withMessages([
                        "items.$key.product_id" =>
                            'Produk tidak sesuai dengan detail PO Customer.'
                    ]);
                }

                $quantity =
                    (float) $item['quantity'];

                $price =
                    (float) $item['purchase_price'];

                /*
                 * Purchase Price Difference.
                 *
                 * Product Master is locked so comparison and optional
                 * overwrite use the latest committed master value.
                 *
                 * PO Supplier transaction price always remains $price.
                 */
                $productMaster =
                    Product::where(
                        'id',
                        $detail->product_id
                    )
                        ->lockForUpdate()
                        ->firstOrFail();

                $masterPurchasePrice =
                    round(
                        (float) $productMaster->purchase_price,
                        2
                    );

                $transactionPurchasePrice =
                    round(
                        (float) $price,
                        2
                    );

                $masterPriceDifferent =
                    $masterPurchasePrice !==
                    $transactionPurchasePrice;

                $masterPriceDecision =
                    $item['master_price_decision'] ?? null;

                if (
                    $masterPriceDifferent &&
                    !in_array(
                        $masterPriceDecision,
                        ['update', 'keep'],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        "items.$key.master_price_decision" =>
                            'Purchase price berbeda dari Product Master. ' .
                            'Pilih Update Master atau Keep Existing.'
                    ]);
                }

                /*
                 * Same price does not require a decision.
                 * It must never trigger an unnecessary master update.
                 */
                if (!$masterPriceDifferent) {
                    $masterPriceDecision = null;
                }

                /*
                 * Allocation PO Supplier lain.
                 *
                 * Current PO Supplier excluded.
                 */
                $allocatedByOthers =
                    (float) PoSupplierDetail::query()
                        ->join(
                            'po_suppliers',
                            'po_suppliers.id',
                            '=',
                            'po_supplier_details.po_supplier_id'
                        )
                        ->where(
                            'po_supplier_details.po_customer_detail_id',
                            $detail->id
                        )
                        ->where(
                            'po_supplier_details.po_supplier_id',
                            '!=',
                            $poSupplier->id
                        )
                        ->where(
                            'po_suppliers.status',
                            '!=',
                            'cancelled'
                        )
                        ->sum(
                            'po_supplier_details.quantity'
                        );

                $availableQuantity =
                    (float) $detail->quantity -
                    $allocatedByOthers;

                /*
                 * Protect duplicate same-line submissions.
                 *
                 * Cumulative quantity inside this request must also
                 * stay within available quantity.
                 */
                $requestedByDetail[
                    $detail->id
                ] =
                    (
                        $requestedByDetail[
                            $detail->id
                        ] ?? 0
                    )
                    + $quantity;

                if (
                    $requestedByDetail[
                        $detail->id
                    ] >
                    $availableQuantity
                ) {
                    throw ValidationException::withMessages([
                        "items.$key.quantity" =>
                            'Quantity melebihi remaining procurement quantity. ' .
                            'Maksimal untuk item ini: ' .
                            $availableQuantity
                    ]);
                }

                $lineSubtotal =
                    $quantity * $price;

                $subtotal +=
                    $lineSubtotal;

                $processedItems[] = [
                    'po_customer_detail_id' =>
                        $detail->id,

                    'product_id' =>
                        $detail->product_id,

                    'quantity' =>
                        $quantity,

                    'purchase_price' =>
                        $price,

                    'master_price_decision' =>
                        $masterPriceDecision,

                    'subtotal' =>
                        $lineSubtotal,
                ];
            }

            /*
             * Restricted Edit financial rule.
             *
             * Discount editable.
             * VAT configuration locked to existing PO Supplier.
             */
            $discountPercent =
                (float) (
                    $request->discount_percent ?? 0
                );

            $discountAmount =
                $subtotal *
                ($discountPercent / 100);

            $afterDiscount =
                $subtotal -
                $discountAmount;

            $taxPercent =
                (float) (
                    $poSupplier->tax_percent ?? 0
                );

            $taxAmount =
                $afterDiscount *
                ($taxPercent / 100);

            $total =
                $afterDiscount +
                $taxAmount;

            /*
             * Replace transaction detail atomically.
             */
            $poSupplier->details()->delete();

            foreach ($processedItems as $item) {

                /*
                 * Explicit user decision only.
                 *
                 * keep:
                 *   Product Master remains unchanged.
                 *
                 * update:
                 *   Product Master purchase_price becomes the
                 *   transaction purchase price.
                 *
                 * PO Supplier Detail always keeps transaction price.
                 */
                if (
                    ($item['master_price_decision'] ?? null) === 'update'
                ) {
                    $productMaster =
                        Product::where(
                            'id',
                            $item['product_id']
                        )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $productMaster->update([
                        'purchase_price' =>
                            $item['purchase_price']
                    ]);
                }

                PoSupplierDetail::create([
                    'po_supplier_id' =>
                        $poSupplier->id,

                    'po_customer_detail_id' =>
                        $item[
                            'po_customer_detail_id'
                        ],

                    'product_id' =>
                        $item['product_id'],

                    'quantity' =>
                        $item['quantity'],

                    'purchase_price' =>
                        $item['purchase_price'],

                    'subtotal' =>
                        $item['subtotal'],
                ]);
            }

            /*
             * ONLY allowed header mutations:
             * Discount, Remarks and recalculated amounts.
             *
             * PO number, supplier, date, status, receipt status,
             * PO Customer and VAT configuration are untouched.
             */
            $poSupplier->update([
                'notes' =>
                    $request->notes,

                'subtotal' =>
                    $subtotal,

                'discount_percent' =>
                    $discountPercent,

                'discount_amount' =>
                    $discountAmount,

                'tax_amount' =>
                    $taxAmount,

                'total' =>
                    $total,
            ]);

            $this->refreshProcurementStatus(
                $poCustomer->id
            );

            DB::commit();

            return redirect()
                ->route(
                    'po-suppliers.show',
                    $poSupplier->id
                )
                ->with(
                    'success',
                    'PO Supplier berhasil diperbarui.'
                );

        } catch (ValidationException $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors(
                    $e->errors()
                );

        } catch (QueryException $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal memperbarui PO Supplier: ' .
                    $e->getMessage()
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal memperbarui PO Supplier: ' .
                    $e->getMessage()
                );
        }
    }


    public function updateStatus(Request $request, $id)
    {
        $newStatus = $request->status;

        $allowedStatus = [
            'confirmed',
            'received',
            'cancelled'
        ];

        if (!in_array($newStatus, $allowedStatus)) {
            return back()->with(
                'error',
                'Status tidak valid!'
            );
        }

        DB::beginTransaction();

        try {

            $poSupplier = PoSupplier::where(
                'id',
                $id
            )
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Jangan izinkan pembatalan setelah ada Goods Receipt.
             */
            if (
                $newStatus === 'cancelled' &&
                $this->isPoSupplierUsed($poSupplier)
            ) {
                DB::rollBack();

                return back()->with(
                    'error',
                    'PO Supplier tidak dapat dibatalkan karena sudah memiliki penerimaan barang (Goods Receipt).'
                );
            }

            $poCustomerId =
                $poSupplier->po_customer_id;

            $poSupplier->update([
                'status' => $newStatus
            ]);

            $this->refreshProcurementStatus(
                $poCustomerId
            );

            DB::commit();

            return redirect()
                ->route(
                    'po-suppliers.show',
                    $id
                )
                ->with(
                    'success',
                    'Status PO Supplier berhasil diperbarui!'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with(
                'error',
                'Gagal memperbarui status PO Supplier: ' .
                $e->getMessage()
            );
        }
    }
    // ============================================================
    // DESTROY ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ dengan pengecekan relasi dan try-catch
    // ============================================================
    public function destroy($id)
    {
        $poSupplier = PoSupplier::with('details')
            ->findOrFail($id);

        if ($poSupplier->status === 'received') {
            return back()->with(
                'error',
                'PO Supplier dengan status Received tidak dapat dihapus!'
            );
        }

        if (
            !in_array(
                $poSupplier->status,
                ['draft', 'confirmed']
            )
        ) {
            return back()->with(
                'error',
                'PO Supplier dengan status ' .
                ucfirst($poSupplier->status) .
                ' tidak dapat dihapus!'
            );
        }

        if ($this->isPoSupplierUsed($poSupplier)) {
            return back()->with(
                'error',
                'PO Supplier tidak dapat dihapus karena sudah memiliki penerimaan barang (Goods Receipt).'
            );
        }

        DB::beginTransaction();

        try {

            $poSupplier = PoSupplier::where(
                'id',
                $id
            )
                ->lockForUpdate()
                ->firstOrFail();

            $poCustomerId =
                $poSupplier->po_customer_id;

            $poSupplier->details()->delete();

            $poSupplier->delete();

            /*
             * Tidak lagi menggunakan jumlah PO Supplier sebagai
             * penentu status PO Customer.
             *
             * Status procurement selalu berasal dari cumulative
             * active allocation.
             */
            $this->refreshProcurementStatus(
                $poCustomerId
            );

            DB::commit();

            return redirect()
                ->route('po-suppliers.index')
                ->with(
                    'success',
                    'Supplier PO berhasil dihapus!'
                );

        } catch (QueryException $e) {

            DB::rollBack();

            return back()->with(
                'error',
                'PO Supplier tidak dapat dihapus karena terkait dengan data lain.'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with(
                'error',
                'Gagal menghapus PO Supplier: ' .
                $e->getMessage()
            );
        }
    }
    public function getPoCustomerList(Request $request)
    {
        $customerId = $request->customer_id;

        if (!$customerId) {
            return response()->json([]);
        }

        $poCustomers = PoCustomer::query()
            ->where('customer_id', $customerId)
            ->where('status', '!=', 'cancelled')
            ->whereHas('details', function ($query) {
                $query->whereRaw(
                    'po_customer_details.quantity > (
                        SELECT COALESCE(SUM(psd.quantity), 0)
                        FROM po_supplier_details psd
                        INNER JOIN po_suppliers ps
                            ON ps.id = psd.po_supplier_id
                        WHERE psd.po_customer_detail_id = po_customer_details.id
                          AND ps.status != ?
                    )',
                    ['cancelled']
                );
            })
            ->orderBy('po_date', 'desc')
            ->get([
                'id',
                'po_number',
                'po_date',
                'total',
                'procurement_status'
            ]);

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
    private function refreshProcurementStatus($poCustomerId)
    {
        $poCustomer = PoCustomer::with('details')
            ->findOrFail($poCustomerId);

        $ordered = 0;
        $allocated = 0;

        foreach ($poCustomer->details as $detail) {

            $ordered += (float) $detail->quantity;

            $allocated += (float) PoSupplierDetail::query()
                ->join(
                    'po_suppliers',
                    'po_suppliers.id',
                    '=',
                    'po_supplier_details.po_supplier_id'
                )
                ->where(
                    'po_supplier_details.po_customer_detail_id',
                    $detail->id
                )
                ->where(
                    'po_suppliers.status',
                    '!=',
                    'cancelled'
                )
                ->sum('po_supplier_details.quantity');
        }

        if ($allocated <= 0) {
            $procurementStatus = 'pending';
        } elseif ($allocated >= $ordered) {
            $procurementStatus = 'fully_procured';
        } else {
            $procurementStatus = 'partial';
        }

        $updates = [
            'procurement_status' => $procurementStatus
        ];

        /*
         * PO Customer lifecycle mengikuti active procurement allocation.
         *
         * - Tidak ada active allocation:
         *      proceed -> received
         *
         * - Ada active allocation:
         *      received/proceed -> proceed
         *
         * - delivered / cancelled tidak boleh didowngrade otomatis.
         */
        if (
            $allocated <= 0 &&
            $poCustomer->status === 'proceed'
        ) {
            $updates['status'] = 'received';

        } elseif (
            $allocated > 0 &&
            in_array(
                $poCustomer->status,
                ['received', 'proceed'],
                true
            )
        ) {
            $updates['status'] = 'proceed';
        }

        $poCustomer->update($updates);
    }

    private function romanMonth($monthNum)
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        return $romans[(int)$monthNum] ?? 'I';
    }
}