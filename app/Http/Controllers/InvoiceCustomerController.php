<?php

namespace App\Http\Controllers;

use App\Models\InvoiceCustomer;
use App\Models\InvoiceCustomerDetail;
use App\Models\PoCustomer;
use App\Models\PoCustomerDetail;
use App\Models\Product;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\InvoiceAllocationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InvoiceCustomerController extends Controller
{
    // =====================================================
    // INDEX
    // =====================================================
    public function index(Request $request)
    {
        $query = InvoiceCustomer::with('poCustomer.customer');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('poCustomer.customer', function ($cq) use ($search) {
                        $cq->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $invoices = $query->orderBy('id', 'desc')->paginate(10);
        $invoices->appends($request->only(['search', 'status', 'type', 'payment_status']));

        return view('invoice_customers.index', compact('invoices'));
    }

    // =====================================================
    // CREATE
    // =====================================================
    public function create()
    {
        $poCustomers = PoCustomer::with('customer')
            ->whereIn('status', ['received', 'proceed'])
            ->whereIn('invoice_status', ['issue yet', 'partial'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $poCustomers->each(function ($po) {
            $po->remaining_subtotal_before_tax = $this->remainingPreTaxAmount($po);
            $po->remaining_total = $po->remaining_subtotal_before_tax;
        });

        return view('invoice_customers.create', compact('poCustomers'));
    }

    // =====================================================
    // AJAX GET PO DETAIL
    // =====================================================
    public function getPoCustomerDetails(
        $id,
        InvoiceAllocationService $allocationService
    )
    {
        $poCustomer = PoCustomer::with([
            'details.product',
            'customer'
        ])->findOrFail($id);

        $rootInvoice = $this->rootInvoiceForPo($poCustomer->id);

        $snapshot = $allocationService->snapshot($poCustomer);

        $lineBalances = collect($snapshot['lines'] ?? [])
            ->keyBy('po_customer_detail_id');

        $items = $poCustomer->details
            ->sortBy('id')
            ->values()
            ->map(function ($detail) use ($lineBalances) {
                $balance = $lineBalances->get($detail->id, []);

                $orderedQuantity = (float) (
                    $balance['ordered_quantity']
                    ?? $detail->quantity
                );

                $invoicedQuantity = (float) (
                    $balance['invoiced_quantity']
                    ?? 0
                );

                $remainingQuantity = (float) (
                    $balance['remaining_quantity']
                    ?? max(
                        0,
                        $orderedQuantity - $invoicedQuantity
                    )
                );

                return [
                    'po_customer_detail_id' => $detail->id,

                    /*
                     * Display metadata only.
                     * Store does not trust browser product/price authority.
                     */
                    'product_id' =>
                        $detail->product_id,

                    'product_name' =>
                        $detail->product->name ?? '-',

                    'product_code' =>
                        $detail->product->product_code ?? '-',

                    'brand' =>
                        $detail->product->brand ?? '-',

                    'unit' =>
                        $detail->product->unit ?? '-',

                    /*
                     * Authoritative allocation quantities.
                     */
                    'po_quantity' =>
                        $orderedQuantity,

                    'ordered_quantity' =>
                        $orderedQuantity,

                    'invoiced_quantity' =>
                        $invoicedQuantity,

                    'remaining_quantity' =>
                        $remainingQuantity,

                    'line_state' =>
                        $balance['state'] ?? (
                            $remainingQuantity <= 0
                                ? 'FULLY_INVOICED'
                                : (
                                    $invoicedQuantity > 0
                                        ? 'PARTIAL'
                                        : 'UNINVOICED'
                                )
                        ),

                    /*
                     * Commercial display values.
                     * Backend Store derives canonical values again.
                     */
                    'quantity' =>
                        $remainingQuantity,

                    'unit_price' =>
                        (float) $detail->unit_price,

                    'subtotal' =>
                        $remainingQuantity
                        * (float) $detail->unit_price,
                ];
            })
            ->all();

        $remainingPreTax = (float) (
            $snapshot['amount']['remaining']
            ?? $this->remainingPreTaxAmount($poCustomer)
        );

        return response()->json([
            'po_customer' => $poCustomer,
            'customer' => $poCustomer->customer,
            'items' => $items,

            'payment_terms' =>
                $rootInvoice?->payment_terms
                ?? $poCustomer->payment_terms,

            'delivery_time' =>
                $rootInvoice?->delivery_time
                ?? $poCustomer->delivery_time,

            'terms_source' =>
                $rootInvoice ? 'invoice' : 'po_customer',

            'terms_source_label' =>
                $rootInvoice
                    ? 'Invoice ' . $rootInvoice->invoice_number
                    : 'PO Customer ' . $poCustomer->po_number,

            'remaining_subtotal_before_tax' =>
                $remainingPreTax,

            'has_previous_invoice' =>
                $rootInvoice !== null,

            'root_invoice_number' =>
                $rootInvoice?->invoice_number,

            /*
             * Existing UI percentage behavior retained.
             * Amount allocation remains independent from qty/item.
             */
            'recommended_percent' =>
                $rootInvoice
                    ? (
                        $this->remainingPreTaxAmount($poCustomer) > 0
                            ? min(
                                100,
                                max(
                                    0,
                                    (
                                        $remainingPreTax
                                        / max(
                                            1,
                                            (float) $poCustomer->subtotal
                                        )
                                    ) * 100
                                )
                            )
                            : 0
                    )
                    : null,

            /*
             * Preserve existing Create UI response contract.
             * These remain commercial/financial display defaults.
             */
            'discount_percent' =>
                (float) ($poCustomer->discount_percent ?? 0),

            'tax_percent' =>
                (float) ($poCustomer->tax_percent ?? 0),

            'pph23_percent' =>
                $this->poPphPercent($poCustomer),
        ]);
    }
    // =====================================================
    // STORE
    // =====================================================
    public function store(Request $request)
    {
        \Log::info('STORE REQUEST', $request->all());

        $validated = $request->validate([
            'po_customer_id' => 'required|exists:po_customers,id',
            'invoice_date' => 'required|date',
            'type' => 'required|in:proforma,sales',
            'allocation_mode' => 'required|in:PERCENTAGE,ITEM_QTY',

            'dp_percent' => 'nullable|numeric|min:0|max:100',

            'details' => 'required_if:allocation_mode,ITEM_QTY|array|min:1',
            'details.*.po_customer_detail_id' => 'required_if:allocation_mode,ITEM_QTY|integer',
            'details.*.quantity' => 'required_if:allocation_mode,ITEM_QTY|numeric|gt:0',


            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'pph23_percent' => 'nullable|numeric|min:0|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'delivery_time' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'tax_invoice_number' => 'nullable|string|max:100',
            'tax_invoice_attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf',
            'notes' => 'nullable|string',
        ]);

        $allocationMode = $validated['allocation_mode'];

        $taxAttachmentPath = null;
        $newTaxAttachmentPath = null;

        try {
            $poCustomer = PoCustomer::with('details')
                ->findOrFail($validated['po_customer_id']);

            if ($poCustomer->details->isEmpty()) {
                throw ValidationException::withMessages([
                    'po_customer_id' =>
                        'PO Customer tidak memiliki detail item.',
                ]);
            }

            /*
             * Existing invoice-number contract.
             */
            $invoiceDate = $validated['invoice_date'];
            $year = date('Y', strtotime($invoiceDate));
            $month = date('n', strtotime($invoiceDate));

            $lastInvoice = InvoiceCustomer::whereYear(
                'invoice_date',
                $year
            )
                ->orderBy('id', 'desc')
                ->first();

            if ($lastInvoice) {
                $lastNumber = (int) explode(
                    '/',
                    $lastInvoice->invoice_number
                )[0];

                $newNumber = str_pad(
                    $lastNumber + 1,
                    3,
                    '0',
                    STR_PAD_LEFT
                );
            } else {
                $newNumber = '001';
            }

            $invoiceNumber =
                $newNumber .
                '/INV/BGS/' .
                $this->romanMonth($month) .
                '/' .
                $year;

            if ($request->hasFile('tax_invoice_attachment')) {
                $newTaxAttachmentPath = app(\App\Services\AttachmentImageUploadService::class)->store($request->file('tax_invoice_attachment'), 'tax_invoice');

                $taxAttachmentPath = $newTaxAttachmentPath;
            }

            DB::beginTransaction();

            /*
             * Lock PO header first, then canonical allocation rows.
             */
            $poCustomer = PoCustomer::whereKey($poCustomer->id)
                ->lockForUpdate()
                ->firstOrFail();

            $allocationService =
                app(InvoiceAllocationService::class);

            $allocationService->lockForAllocation($poCustomer);

            $poCustomer = PoCustomer::with('details')
                ->findOrFail($poCustomer->id);

            $rootInvoice =
                $this->rootInvoiceForPo($poCustomer->id);

            $requestedLines = [];

            if ($allocationMode === 'ITEM_QTY') {
                /*
                 * ITEM_QTY:
                 * browser supplies PCD identity + requested quantity only.
                 * Product, unit price and subtotal remain server-authoritative.
                 */
                $requestedLines =
                    $allocationService->resolveRequestedLines(
                        $poCustomer,
                        $validated['details']
                    );

                $subtotal = (float) collect($requestedLines)
                    ->sum('subtotal');

                /*
                 * ITEM_QTY:
                 * selected canonical item/quantity is always invoiced at
                 * 100% of its canonical commercial value.
                 * Browser dp_percent is not authoritative.
                 */
                $dpPercent = 100.0;
                $dpAmount = round($subtotal, 2);
                $baseAmount = $dpAmount;
            } elseif ($allocationMode === 'PERCENTAGE') {
                /*
                 * PERCENTAGE:
                 * percentage is based on ORIGINAL PO commercial amount.
                 * It consumes financial balance only and creates no
                 * fabricated item/quantity allocation.
                 */
                $subtotal = 0.0;
                $dpPercent =
                    (float) ($validated['dp_percent'] ?? 0);

                $dpAmount =
                    $allocationService->percentageAllocationAmount(
                        $poCustomer,
                        $dpPercent
                    );

                $baseAmount = $dpAmount;
            } else {
                throw ValidationException::withMessages([
                    'allocation_mode' =>
                        'Unsupported invoice allocation mode.',
                ]);
            }

            /*
             * Authoritative amount guard after locks.
             */
            $allocationService->assertAmountAvailable(
                $poCustomer,
                $baseAmount
            );

            $discountPercent =
                (float) ($validated['discount_percent'] ?? 0);

            $discountAmount = round(
                $baseAmount * $discountPercent / 100,
                2
            );

            $afterDiscount =
                $baseAmount - $discountAmount;

            $taxPercent =
                (float) ($validated['tax_percent'] ?? 0);

            $taxAmount = round(
                $afterDiscount * $taxPercent / 100,
                2
            );

            $pph23Percent =
                (float) ($validated['pph23_percent'] ?? 0);

            $pph23Amount = round(
                $afterDiscount * $pph23Percent / 100,
                2
            );

            $total = round(
                $afterDiscount +
                $taxAmount -
                $pph23Amount,
                2
            );

            /*
             * Amount balance is independent from quantity/item balance.
             */
            $amountBalanceBefore =
                $allocationService->amountBalance($poCustomer);

            $remainingAfterInvoice = max(
                0,
                round(
                    (float) $amountBalanceBefore['remaining_amount']
                    - $baseAmount,
                    2
                )
            );

            $data = [
                'invoice_number' => $invoiceNumber,
                'tax_invoice_number' =>
                    $validated['tax_invoice_number'] ?? null,
                'tax_invoice_attachment' =>
                    $taxAttachmentPath,

                'type' => $validated['type'],
                'allocation_mode' => $allocationMode,
                'po_customer_id' => $poCustomer->id,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,

                'subtotal' => $subtotal,
                'dp_percent' => $dpPercent,
                'dp_amount' => $dpAmount,

                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,

                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,

                'pph23_percent' => $pph23Percent,
                'pph23_amount' => $pph23Amount,

                'total' => $total,
                'remaining_amount' => $remainingAfterInvoice,

                /*
                 * Invoice status remains temporarily amount-oriented.
                 * Composite PO completion is a separate remediation.
                 */
                'status' =>
                    $remainingAfterInvoice > 0
                        ? 'partial'
                        : 'completed',

                'payment_status' => 'sent',
                'payment_terms' =>
                    $validated['payment_terms'] ?? null,
                'delivery_time' =>
                    $validated['delivery_time'] ?? null,
                'notes' =>
                    $validated['notes'] ?? null,
                'parent_invoice_id' =>
                    $rootInvoice?->id,
            ];

            $invoice = InvoiceCustomer::create($data);

            /*
             * ITEM_QTY persists canonical selected lines.
             * AMOUNT_ONLY intentionally persists zero detail rows.
             */
            foreach ($requestedLines as $line) {
                InvoiceCustomerDetail::create([
                    'invoice_customer_id' => $invoice->id,
                    'po_customer_detail_id' =>
                        $line['po_customer_detail_id'],
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            $poCustomer->updateInvoiceStatus();

            DB::commit();

            return redirect()
                ->route('invoice-customers.index')
                ->with(
                    'success',
                    'Invoice successfully created.'
                );

        } catch (ValidationException $e) {
            DB::rollBack();

            if (
                !empty($newTaxAttachmentPath) &&
                Storage::disk('public')
                    ->exists($newTaxAttachmentPath)
            ) {
                Storage::disk('public')
                    ->delete($newTaxAttachmentPath);
            }

            return back()
                ->withInput()
                ->withErrors($e->validator);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            if (
                !empty($newTaxAttachmentPath) &&
                Storage::disk('public')
                    ->exists($newTaxAttachmentPath)
            ) {
                Storage::disk('public')
                    ->delete($newTaxAttachmentPath);
            }

            if (
                $e->getCode() == 23000 &&
                str_contains(
                    $e->getMessage(),
                    'invoice_number'
                )
            ) {
                \Log::warning(
                    'Duplicate invoice number detected: ' .
                    $e->getMessage()
                );

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Nomor invoice sudah terpakai. Silakan periksa tanggal invoice atau hubungi admin.'
                    );
            }

            \Log::error(
                'STORE ERROR: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]
            );

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menyimpan invoice: ' .
                    $e->getMessage()
                );

        } catch (\Exception $e) {
            DB::rollBack();

            if (
                !empty($newTaxAttachmentPath) &&
                Storage::disk('public')
                    ->exists($newTaxAttachmentPath)
            ) {
                Storage::disk('public')
                    ->delete($newTaxAttachmentPath);
            }

            \Log::error(
                'STORE ERROR: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]
            );

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menyimpan invoice: ' .
                    $e->getMessage()
                );
        }
    }

    // =====================================================
    // EDIT
    // =====================================================
    public function edit($id)
    {
        $invoice = InvoiceCustomer::with([
            'poCustomer.customer',
            'details.product',
            'details.poCustomerDetail',
            'parentInvoice'
        ])->findOrFail($id);

        $hasLegacyDetails = $invoice->details
            ->contains(fn ($detail) => empty($detail->po_customer_detail_id));

        $hasLinkedDetails = $invoice->details
            ->contains(fn ($detail) => !empty($detail->po_customer_detail_id));

        $isLegacyInvoice = $hasLegacyDetails && !$hasLinkedDetails;
        $isMixedLineageInvoice = $hasLegacyDetails && $hasLinkedDetails;

        if ($invoice->payment_status == 'paid') {
            return redirect()->route('invoice-customers.index')
                ->with('error', 'Invoice already paid and cannot be edited.');
        }

        $normalAllocationEdit = in_array($invoice->allocation_mode, ['PERCENTAGE', 'ITEM_QTY'], true);
        $allocationSnapshot = null;
        if ($normalAllocationEdit) {
            $invoice->poCustomer->load('details.product');
            $allocationSnapshot = app(InvoiceAllocationService::class)->snapshot($invoice->poCustomer, $invoice->id);
        }

        $products = Product::orderBy('name')->get();
        // Ambil daftar invoice yang bisa dijadikan parent (partial, bukan dirinya sendiri)
        $parentInvoices = InvoiceCustomer::where('status', 'partial')
            ->where('payment_status', '!=', 'paid')
            ->where('id', '!=', $id)
            ->with('poCustomer.customer')
            ->orderBy('invoice_number')
            ->get();

        return view('invoice_customers.edit', compact(
            'invoice',
            'products',
            'parentInvoices',
            'isLegacyInvoice',
            'isMixedLineageInvoice',
            'normalAllocationEdit',
            'allocationSnapshot'
        ));
    }

    // =====================================================
    // UPDATE invoice_number TIDAK bisa diubah
    // =====================================================
    /** Update normal allocations using the same authority as Create. */
    private function updateNormalAllocation(Request $request, InvoiceCustomer $invoice)
    {
        $data = $request->validate([
            'type' => 'required|in:proforma,sales',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'allocation_mode' => 'required|in:PERCENTAGE,ITEM_QTY',
            'dp_percent' => 'nullable|numeric|gt:0|max:100',
            'details' => 'nullable|array',
            'details.*.po_customer_detail_id' => 'required|integer',
            'details.*.quantity' => 'required|integer|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'pph23_percent' => 'nullable|numeric|min:0|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'delivery_time' => 'nullable|string|max:255',
            'tax_invoice_number' => 'nullable|string|max:100',
            'tax_invoice_attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf',
            'delete_attachment' => 'nullable|in:0,1',
            'notes' => 'nullable|string',
            'payment_status' => 'required|in:sent',
        ]);
        $newAttachment = null;
        $oldAttachment = null;
        try {
            DB::transaction(function () use ($request, $invoice, $data, &$newAttachment, &$oldAttachment) {
                $service = app(InvoiceAllocationService::class);
                $po = $service->lockForAllocation(PoCustomer::findOrFail($invoice->po_customer_id));
                $po->load('details');
                $lockedInvoice = InvoiceCustomer::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
                if ((int) $lockedInvoice->po_customer_id !== (int) $po->id ||
                    $lockedInvoice->payment_status === 'paid' ||
                    strtolower((string) $lockedInvoice->status) === 'cancelled' ||
                    strtolower((string) $lockedInvoice->invoice_status) === 'cancelled') {
                    throw new \RuntimeException('Invoice paid/cancelled atau berubah dan tidak dapat diedit.');
                }
                $mode = $data['allocation_mode'];
                $service->assertStrategyAvailable($po, $mode, $lockedInvoice->id);
                $balance = $service->amountBalance($po, $lockedInvoice->id);
                $remaining = (float) $balance['remaining_amount'];
                $poAmount = (float) $balance['po_amount'];
                if ($remaining <= 0 || $poAmount <= 0) {
                    throw new \RuntimeException('Tidak ada kapasitas PO untuk invoice ini.');
                }
                $lines = [];
                if ($mode === 'PERCENTAGE') {
                    $dpPercent = (float) ($data['dp_percent'] ?? 0);
                    $base = $service->percentageAllocationAmount($po, $dpPercent, $lockedInvoice->id);
                    $subtotal = 0.0;
                } else {
                    $requested = array_values(array_filter($data['details'] ?? [], fn ($line) => (int) $line['quantity'] > 0));
                    $lines = $service->resolveRequestedLines($po, $requested, $lockedInvoice->id);
                    $subtotal = round((float) collect($lines)->sum('subtotal'), 2);
                    $base = $subtotal;
                    $dpPercent = 100.0;
                }
                $service->assertAmountAvailable($po, $base, $lockedInvoice->id);
                $discountPercent = (float) ($data['discount_percent'] ?? 0);
                $taxPercent = (float) ($data['tax_percent'] ?? 0);
                $pphPercent = (float) ($data['pph23_percent'] ?? 0);
                $discount = round($base * $discountPercent / 100, 2);
                $dpp = $base - $discount;
                $tax = round($dpp * $taxPercent / 100, 2);
                $pph = round($dpp * $pphPercent / 100, 2);
                $oldAttachment = $lockedInvoice->tax_invoice_attachment;
                $attachment = $oldAttachment;
                if ($request->input('delete_attachment') === '1') $attachment = null;
                if ($request->hasFile('tax_invoice_attachment')) {
                    $newAttachment = app(\App\Services\AttachmentImageUploadService::class)->store($request->file('tax_invoice_attachment'), 'tax_invoice');
                    $attachment = $newAttachment;
                }
                $remainingAfter = max(0, round($remaining - $base, 2));
                $lockedInvoice->update([
                    'type' => $data['type'], 'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'payment_terms' => $data['payment_terms'] ?? null,
                    'delivery_time' => $data['delivery_time'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'tax_invoice_number' => $data['tax_invoice_number'] ?? null,
                    'tax_invoice_attachment' => $attachment,
                    'allocation_mode' => $mode,
                    'subtotal' => $subtotal, 'dp_percent' => $dpPercent, 'dp_amount' => $base,
                    'discount_percent' => $discountPercent, 'discount_amount' => $discount,
                    'tax_percent' => $taxPercent, 'tax_amount' => $tax,
                    'pph23_percent' => $pphPercent, 'pph23_amount' => $pph,
                    'total' => round($dpp + $tax - $pph, 2),
                    'remaining_amount' => $remainingAfter,
                    'status' => $remainingAfter > 0 ? 'partial' : 'completed',
                ]);
                $lockedInvoice->details()->delete();
                foreach ($lines as $line) {
                    InvoiceCustomerDetail::create(array_merge($line, ['invoice_customer_id' => $lockedInvoice->id]));
                }
                $po->updateInvoiceStatus();
            });
        } catch (\Throwable $e) {
            if ($newAttachment && Storage::disk('public')->exists($newAttachment)) {
                Storage::disk('public')->delete($newAttachment);
            }
            report($e);
            return back()->withInput()->with('error', $e instanceof \RuntimeException
                ? $e->getMessage() : 'Invoice gagal diperbarui. Periksa log aplikasi.');
        }
        if ($oldAttachment && ($request->input('delete_attachment') === '1' || $newAttachment)) {
            try { Storage::disk('public')->delete($oldAttachment); }
            catch (\Throwable $e) { report($e); }
        }
        return redirect()->route('invoice-customers.index')->with('success', 'Invoice successfully updated.');
    }

    public function update(Request $request, $id)
    {
        $invoice = InvoiceCustomer::with('details')->findOrFail($id);

        $hasLegacyDetails = $invoice->details
            ->contains(fn ($detail) => empty($detail->po_customer_detail_id));

        $hasLinkedDetails = $invoice->details
            ->contains(fn ($detail) => !empty($detail->po_customer_detail_id));

        $isLegacyInvoice = $hasLegacyDetails && !$hasLinkedDetails;
        $isMixedLineageInvoice = $hasLegacyDetails && $hasLinkedDetails;

        if ($isMixedLineageInvoice) {
            return back()->with(
                'error',
                'Invoice memiliki mixed lineage dan tidak dapat diedit.'
            );
        }

        if ($invoice->payment_status == 'paid') {
            return back()->with('error', 'Invoice already paid and cannot be edited.');
        }

        if (in_array($invoice->allocation_mode, ['PERCENTAGE', 'ITEM_QTY'], true)) {
            return $this->updateNormalAllocation($request, $invoice);
        }
        if (!$isLegacyInvoice || $invoice->allocation_mode !== null) {
            return back()->withInput()->with('error', 'Invoice tanpa strategi normal atau histori legacy yang jelas memerlukan pemeriksaan manual.');
        }

        $validated = $request->validate([
            'type' => 'required|in:proforma,sales',
            'invoice_date' => 'required|date',
            'items' => $isLegacyInvoice
                ? 'required|array|min:1'
                : 'nullable|array',
            'items.*.po_customer_detail_id' => 'nullable',
            'items.*.product_id' => $isLegacyInvoice
                ? 'required|exists:products,id'
                : 'nullable',
            'items.*.quantity' => $isLegacyInvoice
                ? 'required|numeric|min:1'
                : 'nullable',
            'items.*.unit_price_numeric' => $isLegacyInvoice
                ? 'required|numeric|min:0'
                : 'nullable',
            'dp_percent' => 'nullable|numeric|min:0|max:100',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric',
            'payment_amount' => 'nullable|numeric',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'pph23_percent' => 'nullable|numeric|min:0|max:100',
            'payment_terms' => 'nullable|string|max:255',
            'delivery_time'  => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'tax_invoice_number' => 'nullable|string|max:100',
            'tax_invoice_attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf',
            'delete_attachment' => 'nullable|string',
            'notes' => 'nullable|string',
            'payment_status' => 'required|in:sent',
        ]);

        try {
            if ($isLegacyInvoice) {
                $existingDetails = $invoice->details
                    ->sortBy('id')
                    ->values();

                $requestItems = collect($request->items)->values();

                if ($existingDetails->count() !== $requestItems->count()) {
                    throw ValidationException::withMessages([
                        'items' => 'Detail item legacy invoice tidak dapat ditambah atau dihapus.',
                    ]);
                }

                foreach ($existingDetails as $index => $existingDetail) {
                    $requestItem = $requestItems[$index] ?? null;

                    if (
                        !$requestItem ||
                        (int) $requestItem['product_id']
                            !== (int) $existingDetail->product_id ||
                        (float) $requestItem['quantity']
                            !== (float) $existingDetail->quantity ||
                        round(
                            (float) $requestItem['unit_price_numeric'],
                            4
                        ) !== round(
                            (float) $existingDetail->unit_price,
                            4
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'Product, Qty, dan Price pada legacy invoice tidak dapat diubah.',
                        ]);
                    }
                }
            }

            // RM-004O-B2:
            // Item financial authority is NOT the browser.
            //
            // Legacy invoice:
            //   preserve the exact historical invoice detail snapshot.
            //
            // Linked RM-004O invoice:
            //   use the full canonical PO Customer detail set.
            if ($isLegacyInvoice) {
                $subtotal = (float) $invoice->details->sum('subtotal');
            } else {
                $canonicalPoDetails = PoCustomerDetail::query()
                    ->where('po_customer_id', $invoice->po_customer_id)
                    ->orderBy('id')
                    ->get();

                if ($canonicalPoDetails->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => 'PO Customer tidak memiliki detail item.',
                    ]);
                }

                $subtotal = (float) $canonicalPoDetails->sum(
                    fn ($detail) =>
                        (float) $detail->quantity
                        * (float) $detail->unit_price
                );
            }

            $dpPercent = (float) ($request->dp_percent ?? 0);
            $dpAmount = $subtotal * $dpPercent / 100;
            $baseAmount = ($dpAmount > 0) ? $dpAmount : $subtotal;
            $discountPercent = (float) ($request->discount_percent ?? 0);
            $discountAmount = $baseAmount * $discountPercent / 100;
            $afterDiscount = $baseAmount - $discountAmount;
            $taxPercent = (float) ($request->tax_percent ?? 0);
            $taxAmount = $afterDiscount * $taxPercent / 100;
            $pph23Percent = (float) ($request->pph23_percent ?? 0);
            $pph23Amount = $afterDiscount * $pph23Percent / 100;
            $total = $afterDiscount + $taxAmount - $pph23Amount;
            
            // ===== VALIDASI SISA PO =====
            $poCustomer = PoCustomer::findOrFail($invoice->po_customer_id);
            $remainingPreTax = $this->remainingPreTaxAmount($poCustomer, $invoice->id);
            $remainingAfterInvoice = max(0, $remainingPreTax - $baseAmount);
            if (round($baseAmount) > round($remainingPreTax)) {
                return back()->withInput()->with('error',
                    'Payment Amount baru Rp ' . number_format($baseAmount, 0, ',', '.') .
                    ' melebihi sisa PO Amount (Rp ' . number_format($remainingPreTax, 0, ',', '.') . ').'
                );
            }

            // Attachment
            $oldTaxAttachmentPath = $invoice->tax_invoice_attachment;
            $newTaxAttachmentPath = null;
            $taxAttachmentPath = $oldTaxAttachmentPath;

            if ($request->input('delete_attachment') == '1') {
                $taxAttachmentPath = null;
            }

            if ($request->hasFile('tax_invoice_attachment')) {
                $newTaxAttachmentPath = app(\App\Services\AttachmentImageUploadService::class)->store($request->file('tax_invoice_attachment'), 'tax_invoice');
                $taxAttachmentPath = $newTaxAttachmentPath;
            }

            //  UPDATE termasuk remaining_amount
            DB::beginTransaction();

            $poCustomer = PoCustomer::whereKey($poCustomer->id)
                ->lockForUpdate()
                ->firstOrFail();
            $remainingPreTax = $this->remainingPreTaxAmount($poCustomer, $invoice->id);
            if (round($baseAmount) > round($remainingPreTax)) {
                throw ValidationException::withMessages([
                    'items' => 'Payment Amount melebihi sisa PO Amount.',
                ]);
            }
            $lockedCanonicalPoDetails = collect();

            if (!$isLegacyInvoice) {
                $lockedCanonicalPoDetails = PoCustomerDetail::query()
                    ->where('po_customer_id', $poCustomer->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($lockedCanonicalPoDetails->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => 'PO Customer tidak memiliki detail item.',
                    ]);
                }

                // Recalculate from locked canonical rows.
                $subtotal = (float) $lockedCanonicalPoDetails->sum(
                    fn ($detail) =>
                        (float) $detail->quantity
                        * (float) $detail->unit_price
                );

                $dpAmount = $subtotal * $dpPercent / 100;
                $baseAmount = ($dpAmount > 0)
                    ? $dpAmount
                    : $subtotal;

                $discountAmount =
                    $baseAmount * $discountPercent / 100;

                $afterDiscount =
                    $baseAmount - $discountAmount;

                $taxAmount =
                    $afterDiscount * $taxPercent / 100;

                $pph23Amount =
                    $afterDiscount * $pph23Percent / 100;

                $total =
                    $afterDiscount
                    + $taxAmount
                    - $pph23Amount;

                // Re-check cumulative payment capacity using the
                // locked canonical financial snapshot.
                if (round($baseAmount) > round($remainingPreTax)) {
                    throw ValidationException::withMessages([
                        'dp_percent' =>
                            'Payment Amount melebihi sisa PO Amount.',
                    ]);
                }
            }

            $remainingAfterInvoice = max(
                0,
                $remainingPreTax - $baseAmount
            );

            $invoice->update([
                'type' => $request->type,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'tax_invoice_number' => $request->tax_invoice_number,
                'tax_invoice_attachment' => $taxAttachmentPath,
                'subtotal' => $subtotal,
                'dp_percent' => $dpPercent,
                'dp_amount' => $dpAmount,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'pph23_percent' => $pph23Percent,
                'pph23_amount' => $pph23Amount,
                'total' => $total,
                'remaining_amount' => $remainingAfterInvoice, //  PASTIKAN INI ADA!
                'status' => ($remainingAfterInvoice > 0) ? 'partial' : 'completed',
                'payment_terms' => $request->payment_terms,
                'delivery_time' => $request->delivery_time,
                'notes' => $request->notes,
            ]);

            //  DEBUG: Log update
            \Log::info('Update data:', [
                'remaining_amount' => $remainingAfterInvoice,
                'status' => ($remainingAfterInvoice > 0) ? 'partial' : 'completed'
            ]);

            // RM-004O-B1/B2:
            //
            // Legacy:
            //   never delete/recreate historical detail rows.
            //
            // Linked RM-004O:
            //   rebuild exclusively from locked canonical PO Customer
            //   details. Browser item values are ignored.
            if (!$isLegacyInvoice) {
                $invoice->details()->delete();

                foreach ($lockedCanonicalPoDetails as $poDetail) {
                    InvoiceCustomerDetail::create([
                        'invoice_customer_id' =>
                            $invoice->id,
                        'po_customer_detail_id' =>
                            $poDetail->id,
                        'product_id' =>
                            $poDetail->product_id,
                        'quantity' =>
                            (float) $poDetail->quantity,
                        'unit_price' =>
                            (float) $poDetail->unit_price,
                        'subtotal' =>
                            (float) $poDetail->quantity
                            * (float) $poDetail->unit_price,
                    ]);
                }
            }

            $poCustomer->updateInvoiceStatus();
            DB::commit();

            if (
                $oldTaxAttachmentPath &&
                $oldTaxAttachmentPath !== $taxAttachmentPath &&
                Storage::disk('public')->exists($oldTaxAttachmentPath)
            ) {
                Storage::disk('public')->delete($oldTaxAttachmentPath);
            }

            return redirect()->route('invoice-customers.index')
                ->with('success', 'Invoice successfully updated.');

        } catch (ValidationException $e) {
            DB::rollBack();

            if (!empty($newTaxAttachmentPath) && Storage::disk('public')->exists($newTaxAttachmentPath)) {
                Storage::disk('public')->delete($newTaxAttachmentPath);
            }

            return back()->withInput()->withErrors($e->validator);
        } catch (\Exception $e) {
            DB::rollBack();

            if (!empty($newTaxAttachmentPath) && Storage::disk('public')->exists($newTaxAttachmentPath)) {
                Storage::disk('public')->delete($newTaxAttachmentPath);
            }

            \Log::error('UPDATE ERROR: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Gagal update invoice: ' . $e->getMessage());
        }
    }

    // =====================================================
    // DELETE
    // =====================================================
    public function destroy($id)
    {
        $invoice = InvoiceCustomer::findOrFail($id);

        if ($invoice->payment_status == 'paid') {
            return back()->with('error', 'Invoice already paid and cannot be deleted.');
        }

        $poCustomer = PoCustomer::findOrFail($invoice->po_customer_id);
        $attachmentPath = $invoice->tax_invoice_attachment;

        DB::beginTransaction();

        try {
            $invoice->details()->delete();
            $invoice->delete();

            $poCustomer->updateInvoiceStatus();

            DB::commit();

            if (
                $attachmentPath &&
                Storage::disk('public')->exists($attachmentPath)
            ) {
                Storage::disk('public')->delete($attachmentPath);
            }

            return redirect()->route('invoice-customers.index')
                ->with('success', 'Invoice deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error(
                'DELETE INVOICE ERROR: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]
            );

            return back()->with(
                'error',
                'Gagal menghapus invoice: ' . $e->getMessage()
            );
        }
    }

    // =====================================================
    // CANCEL
    // =====================================================
    public function cancel($id)
    {
        $invoice = InvoiceCustomer::findOrFail($id);

        if ($invoice->payment_status == 'paid') {
            return back()->with('error', 'Invoice already paid and cannot be cancelled.');
        }

        $poCustomer = PoCustomer::findOrFail($invoice->po_customer_id);

        DB::beginTransaction();

        try {
            $invoice->update(['status' => 'cancelled']);

            $poCustomer->updateInvoiceStatus();

            DB::commit();

            return redirect()->route('invoice-customers.index')
                ->with('success', 'Invoice cancelled successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error(
                'CANCEL INVOICE ERROR: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]
            );

            return back()->with(
                'error',
                'Gagal membatalkan invoice: ' . $e->getMessage()
            );
        }
    }

    // =====================================================
    // SHOW, PRINT, PRINT_INVOICE
    // =====================================================
    public function show($id)
    {
        $invoice = InvoiceCustomer::with(['poCustomer.customer', 'details.product', 'parentInvoice'])->findOrFail($id);
        return view('invoice_customers.show', compact('invoice'));
    }

    public function print($id)
    {
        $invoice = InvoiceCustomer::with(['poCustomer.customer', 'details.product', 'parentInvoice'])->findOrFail($id);
        $company = Company::where('is_active', true)->first();
        $printAssets = $this->invoicePrintAssets($company, $invoice);
        $pdf = Pdf::loadView('invoice_customers.print', compact('invoice', 'company', 'printAssets'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('Invoice_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }

    public function printInvoice($id)
    {
        $invoice = InvoiceCustomer::with(['poCustomer.customer', 'details.product', 'parentInvoice'])->findOrFail($id);
        $company = Company::where('is_active', true)->first();
        $printAssets = $this->invoicePrintAssets($company, $invoice);
        $pdf = Pdf::loadView('invoice_customers.print_invoice', compact('invoice', 'company', 'printAssets'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('Invoice_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }

    // =====================================================
    // PRIVATE HELPERS
    // =====================================================

    private function invoicePrintAssets(?Company $company, InvoiceCustomer $invoice): array
    {
        $signatureField = (float) $invoice->total > 5000000 ? 'ttd_inv2' : 'ttd_inv';

        return [
            'logo' => $this->publicImageDataUri([
                $company?->logo,
                'uploads/companies/logo/LogoBGS.png',
                'uploads/ttd/logo baru BGS-FInal.png',
            ]),
            'signature' => $this->publicImageDataUri([
                $company?->{$signatureField},
                'uploads/ttd/' . $signatureField . '.jpg',
            ]),
        ];
    }

    private function publicImageDataUri(array $candidates): ?string
    {
        $publicRoot = realpath(public_path());
        if ($publicRoot === false) {
            return null;
        }

        foreach (array_filter($candidates) as $relativePath) {
            $resolved = realpath(public_path(ltrim(str_replace('\\', '/', $relativePath), '/')));
            if (
                $resolved === false ||
                !str_starts_with($resolved, $publicRoot . DIRECTORY_SEPARATOR) ||
                !is_file($resolved)
            ) {
                continue;
            }

            $mime = mime_content_type($resolved);
            if (!is_string($mime) || !str_starts_with($mime, 'image/')) {
                continue;
            }

            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($resolved));
        }

        return null;
    }

    private function parseRupiahToNumber($value)
    {
        if (!$value) {
            return 0;
        }
        return (float) preg_replace('/[^0-9]/', '', $value);
    }

    private function romanMonth($monthNum)
    {
        $month = (int) $monthNum;
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $romans[$month] ?? 'I';
    }

    private function rootInvoiceForPo(int $poCustomerId): ?InvoiceCustomer
    {
        return InvoiceCustomer::where('po_customer_id', $poCustomerId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->first();
    }

    private function remainingPreTaxAmount(PoCustomer $poCustomer, ?int $excludeInvoiceId = null): float
    {
        $poPreTax = max(0, (float) $poCustomer->subtotal);
        if ($poPreTax <= 0) {
            $poPreTax = max(
                0,
                (float) $poCustomer->total - (float) $poCustomer->tax_amount
            );
        }

        $query = InvoiceCustomer::where('po_customer_id', $poCustomer->id)
            ->where('status', '!=', 'cancelled');

        if ($excludeInvoiceId !== null) {
            $query->where('id', '!=', $excludeInvoiceId);
        }

        $invoicedPreTax = (float) $query->selectRaw(
            'COALESCE(SUM(CASE WHEN dp_amount > 0 THEN dp_amount ELSE subtotal END), 0) as amount'
        )->value('amount');

        return max(0, $poPreTax - $invoicedPreTax);
    }

    private function poPphPercent(PoCustomer $poCustomer): float
    {
        $poPreTax = max(
            0,
            (float) $poCustomer->subtotal - (float) $poCustomer->discount_amount
        );
        if ($poPreTax <= 0) {
            $poPreTax = max(
                0,
                (float) $poCustomer->total - (float) $poCustomer->tax_amount
            );
        }
        if ($poPreTax <= 0) {
            return 0;
        }

        // Legacy PO tidak memiliki kolom PPh, tetapi totalnya sudah net of PPh.
        $derivedPphAmount = max(
            0,
            $poPreTax + (float) $poCustomer->tax_amount - (float) $poCustomer->total
        );

        // Nilai total PO tersimpan dalam rupiah bulat sehingga hasil pembagian
        // dapat menghasilkan artefak floating point (contoh 2.500000668...).
        return round(($derivedPphAmount / $poPreTax) * 100, 4);
    }

    /**
     * Cek apakah user saat ini adalah admin (arief@gmail.com)
     */
    private function isAdmin()
    {
        return auth()->check() && auth()->user()->canUseAdminFeatures();
    }
}
