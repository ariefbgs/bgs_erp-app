<?php

namespace App\Http\Controllers;

use App\Models\DocumentReceipt;
use App\Models\DeliveryOrder;
use App\Models\InvoiceCustomer;
use App\Models\Company;
use App\Models\PoCustomer;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentReceipt::with([
            'poCustomer.customer',
            'deliveryOrders',
        ]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('poCustomer.customer', function ($cq) use ($search) {
                        $cq->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Filter berdasarkan status receipt (draft, sent, delivered)
        if ($request->filled('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan status Delivery Order (opsional)
        if ($request->filled('do_status') && $request->do_status != '') {
            $query->whereHas('deliveryOrders', function ($q) use ($request) {
                $q->where('status', $request->do_status);
            });
        }

        $receipts = $query->orderBy('created_at', 'desc')->paginate(10);
        $receipts->appends($request->only(['search', 'status', 'do_status']));

        return view('document-receipts.index', compact('receipts'));
    }

    public function create()
    {
        /*
         * PO Customer is the business context for Document Receipt.
         * Only PO Customers having at least one DO and one active
         * Sales Invoice are eligible for Create DR.
         */
        $poCustomers = PoCustomer::with('customer')
            ->where('status', '!=', 'cancelled')
            ->whereHas('deliveryOrders')
            ->whereHas('invoiceCustomers', function ($query) {
                $query->where('status', '!=', 'cancelled');
            })
            ->orderByDesc('po_date')
            ->orderByDesc('id')
            ->get();

        return view(
            'document-receipts.create',
            compact('poCustomers')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'po_customer_id' => 'required|exists:po_customers,id',

            'delivery_order_ids' => 'required|array|min:1',
            'delivery_order_ids.*' =>
                'required|integer|distinct|exists:delivery_orders,id',

            'invoice_customer_ids' => 'required|array|min:1',
            'invoice_customer_ids.*' =>
                'required|integer|distinct|exists:invoice_customers,id',

            'receipt_date' => 'required|date',
            'attachment' =>
                'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,pdf',
            'notes' => 'nullable|string',
        ]);

        $poCustomerId = (int) $validated['po_customer_id'];

        /*
         * Every selected DO must belong to the DR PO Customer.
         */
        $deliveryOrders = DeliveryOrder::query()
            ->whereIn('id', $validated['delivery_order_ids'])
            ->get();

        if (
            $deliveryOrders->count()
            !== count($validated['delivery_order_ids'])
        ) {
            throw ValidationException::withMessages([
                'delivery_order_ids' =>
                    'One or more Delivery Orders are invalid.',
            ]);
        }

        $crossPoDo = $deliveryOrders->first(
            fn ($do) =>
                (int) $do->po_customer_id !== $poCustomerId
        );

        if ($crossPoDo) {
            throw ValidationException::withMessages([
                'delivery_order_ids' =>
                    'All Delivery Orders must belong to the selected PO Customer.',
            ]);
        }

        /*
         * Every selected Sales Invoice must belong to the same
         * PO Customer. Cancelled invoices are not allowed.
         */
        $invoices = InvoiceCustomer::query()
            ->whereIn('id', $validated['invoice_customer_ids'])
            ->get();

        if (
            $invoices->count()
            !== count($validated['invoice_customer_ids'])
        ) {
            throw ValidationException::withMessages([
                'invoice_customer_ids' =>
                    'One or more Sales Invoices are invalid.',
            ]);
        }

        $crossPoInvoice = $invoices->first(
            fn ($invoice) =>
                (int) $invoice->po_customer_id !== $poCustomerId
        );

        if ($crossPoInvoice) {
            throw ValidationException::withMessages([
                'invoice_customer_ids' =>
                    'All Sales Invoices must belong to the selected PO Customer.',
            ]);
        }

        $cancelledInvoice = $invoices->first(
            fn ($invoice) =>
                $invoice->status === 'cancelled'
                || $invoice->payment_status === 'cancelled'
        );

        if ($cancelledInvoice) {
            throw ValidationException::withMessages([
                'invoice_customer_ids' =>
                    'Cancelled Sales Invoice cannot be used in Document Receipt.',
            ]);
        }

        /*
         * FP is system-derived from selected Sales Invoices.
         * No tax invoice number is accepted from the DR request.
         */
        $invoiceNumbers = $invoices
            ->pluck('invoice_number')
            ->filter()
            ->values()
            ->implode(', ');

        $taxInvoiceNumbers = $invoices
            ->pluck('tax_invoice_number')
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        /*
         * Generate DR number.
         */
        $receiptDate = $validated['receipt_date'];
        $currentYear = date('Y', strtotime($receiptDate));
        $currentMonth = date('n', strtotime($receiptDate));

        $lastReceipt = DocumentReceipt::whereYear(
            'receipt_date',
            $currentYear
        )
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReceipt) {
            $lastNumber =
                (int) explode('/', $lastReceipt->receipt_number)[0];

            $newNumber = str_pad(
                $lastNumber + 1,
                4,
                '0',
                STR_PAD_LEFT
            );
        } else {
            $newNumber = '0001';
        }

        $receiptNumber =
            $newNumber .
            '/DR/BGS/' .
            $this->romanMonth($currentMonth) .
            '/' .
            $currentYear;

        /*
         * Attachment.
         */
        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = app(\App\Services\AttachmentImageUploadService::class)->store(
                $request->file('attachment'), 'document-receipts'
            );
        }

        DB::transaction(function () use (
            $validated,
            $poCustomerId,
            $deliveryOrders,
            $invoices,
            $receiptNumber,
            $invoiceNumbers,
            $taxInvoiceNumbers,
            $attachmentPath
        ) {
            /*
             * Legacy header columns remain populated temporarily
             * so current Show/Edit/Print stay backward compatible
             * until they are migrated in the next stages.
             *
             * delivery_order_id is NOT the new source of truth.
             */
            $receipt = DocumentReceipt::create([
                'receipt_number' => $receiptNumber,
                'po_customer_id' => $poCustomerId,




                'receipt_date' => $validated['receipt_date'],
                'attachment' => $attachmentPath,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
            ]);

            $receipt->deliveryOrders()->sync(
                $deliveryOrders->pluck('id')->all()
            );

            $receipt->invoices()->sync(
                $invoices->pluck('id')->all()
            );
        });

        return redirect()
            ->route('document-receipts.index')
            ->with(
                'success',
                'Document Receipt created successfully.'
            );
    }

    public function show($id)
    {
        $receipt = DocumentReceipt::with([
            'poCustomer.customer',
            'deliveryOrders',
            'invoices',
        ])->findOrFail($id);

        return view(
            'document-receipts.show',
            compact('receipt')
        );
    }

    public function edit($id)
    {
        $receipt = DocumentReceipt::with([
            'poCustomer.customer',
            'deliveryOrders',
            'invoices',
        ])->findOrFail($id);

        $poCustomers = PoCustomer::with('customer')
            ->where('status', '!=', 'cancelled')
            ->whereHas('deliveryOrders')
            ->whereHas('invoiceCustomers', function ($query) {
                $query->where('status', '!=', 'cancelled');
            })
            ->orderByDesc('po_date')
            ->orderByDesc('id')
            ->get();

        return view(
            'document-receipts.edit',
            compact('receipt', 'poCustomers')
        );
    }

    public function update(Request $request, $id)
    {
        $receipt = DocumentReceipt::with([
            'deliveryOrders',
            'invoices',
        ])->findOrFail($id);

        $validated = $request->validate([
            'po_customer_id' => 'required|exists:po_customers,id',
            'delivery_order_ids' => 'required|array|min:1',
            'delivery_order_ids.*' => 'required|distinct|exists:delivery_orders,id',
            'invoice_customer_ids' => 'required|array|min:1',
            'invoice_customer_ids.*' => 'required|distinct|exists:invoice_customers,id',
            'receipt_date' => 'required|date',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,delivered',
        ]);

        $poCustomerId = (int) $validated['po_customer_id'];

        $poCustomer = PoCustomer::query()
            ->where('status', '!=', 'cancelled')
            ->findOrFail($poCustomerId);

        $deliveryOrders = DeliveryOrder::query()
            ->whereIn('id', $validated['delivery_order_ids'])
            ->get();

        if (
            $deliveryOrders->count()
            !== count($validated['delivery_order_ids'])
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'delivery_order_ids' =>
                        'One or more Delivery Orders are invalid.',
                ]);
        }

        $crossPoDo = $deliveryOrders->first(
            function ($deliveryOrder) use ($poCustomerId) {
                return (int) $deliveryOrder->po_customer_id
                    !== $poCustomerId;
            }
        );

        if ($crossPoDo) {
            return back()
                ->withInput()
                ->withErrors([
                    'delivery_order_ids' =>
                        'All Delivery Orders must belong to the selected PO Customer.',
                ]);
        }

        $invoices = InvoiceCustomer::query()
            ->whereIn('id', $validated['invoice_customer_ids'])
            ->get();

        if (
            $invoices->count()
            !== count($validated['invoice_customer_ids'])
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'invoice_customer_ids' =>
                        'One or more Sales Invoices are invalid.',
                ]);
        }

        $crossPoInvoice = $invoices->first(
            function ($invoice) use ($poCustomerId) {
                return (int) $invoice->po_customer_id
                    !== $poCustomerId;
            }
        );

        if ($crossPoInvoice) {
            return back()
                ->withInput()
                ->withErrors([
                    'invoice_customer_ids' =>
                        'All Sales Invoices must belong to the selected PO Customer.',
                ]);
        }

        $cancelledInvoice = $invoices->first(
            function ($invoice) {
                return $invoice->status === 'cancelled';
            }
        );

        if ($cancelledInvoice) {
            return back()
                ->withInput()
                ->withErrors([
                    'invoice_customer_ids' =>
                        'Cancelled Sales Invoice cannot be included.',
                ]);
        }

        $invoiceNumbers = $invoices
            ->sortBy('id')
            ->pluck('invoice_number')
            ->filter()
            ->implode(', ');

        $taxInvoiceNumbers = $invoices
            ->sortBy('id')
            ->pluck('tax_invoice_number')
            ->filter()
            ->implode(', ');

        $attachmentPath = $receipt->attachment;

        DB::transaction(function () use (
            $receipt,
            $validated,
            $poCustomer,
            $deliveryOrders,
            $invoices,
            $invoiceNumbers,
            $taxInvoiceNumbers,
            &$attachmentPath,
            $request
        ) {
            if ($request->hasFile('attachment')) {
                $attachmentPath = app(\App\Services\AttachmentImageUploadService::class)->store(
                    $request->file('attachment'), 'document-receipts'
                );
            }

            $receipt->update([
                'po_customer_id' => $poCustomer->id,
                'receipt_date' => $validated['receipt_date'],
                'attachment' => $attachmentPath,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);

            $receipt->deliveryOrders()->sync(
                $deliveryOrders->pluck('id')->all()
            );

            $receipt->invoices()->sync(
                $invoices->pluck('id')->all()
            );
        });

        return redirect()
            ->route(
                'document-receipts.show',
                $receipt->id
            )
            ->with(
                'success',
                'Document Receipt updated successfully.'
            );
    }

    public function destroy($id)
    {
        $receipt = DocumentReceipt::findOrFail($id);
        if ($receipt->attachment && Storage::disk('public')->exists($receipt->attachment)) {
            Storage::disk('public')->delete($receipt->attachment);
        }
        $receipt->delete();
        return redirect()->route('document-receipts.index')->with('success', 'Document Receipt deleted successfully.');
    }

    public function getDoDetails($id)
    {
        $do = DeliveryOrder::with('poCustomer.customer')->findOrFail($id);
        return response()->json([
            'po_customer_id' => $do->po_customer_id,
            'po_number' => $do->poCustomer->po_number ?? '',
            'customer_name' => $do->poCustomer->customer->name ?? '',
            'customer_address' => $do->poCustomer->customer->address ?? '',
            'shipping_address' => $do->shipping_address ?? '',
            'receiver_name' => $do->receiver_name ?? '',
            'do_number' => $do->do_number,
        ]);
    }

    public function getCreateOptionsByPo($poCustomerId)
    {
        $poCustomer = PoCustomer::with('customer')
            ->where('status', '!=', 'cancelled')
            ->findOrFail($poCustomerId);

        $deliveryOrders = DeliveryOrder::query()
            ->where('po_customer_id', $poCustomer->id)
            ->orderBy('delivery_date')
            ->orderBy('id')
            ->get([
                'id',
                'do_number',
                'delivery_date',
                'status',
                'shipping_address',
                'receiver_name',
            ]);

        $invoices = InvoiceCustomer::query()
            ->where('po_customer_id', $poCustomer->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get([
                'id',
                'invoice_number',
                'type',
                'invoice_date',
                'tax_invoice_number',
                'status',
                'payment_status',
            ]);

        return response()->json([
            'po_customer_id' => $poCustomer->id,
            'po_number' => $poCustomer->po_number,
            'po_date' => optional($poCustomer->po_date)
                ->format('Y-m-d'),
            'po_status' => $poCustomer->status,
            'customer_name' =>
                $poCustomer->customer->name ?? '',
            'customer_address' =>
                $poCustomer->customer->address ?? '',
            'delivery_orders' => $deliveryOrders,
            'invoices' => $invoices,
        ]);
    }

    public function getCreateOptionsByDo($id)
    {
        $selectedDo = DeliveryOrder::with('poCustomer.customer')
            ->findOrFail($id);

        $poCustomerId = (int) $selectedDo->po_customer_id;

        $deliveryOrders = DeliveryOrder::query()
            ->where('po_customer_id', $poCustomerId)
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->get([
                'id',
                'do_number',
                'delivery_date',
                'status',
                'shipping_address',
                'receiver_name',
            ]);

        $invoices = InvoiceCustomer::query()
            ->where('po_customer_id', $poCustomerId)
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'cancelled');
            })
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get([
                'id',
                'invoice_number',
                'type',
                'invoice_date',
                'tax_invoice_number',
                'payment_status',
            ]);

        return response()->json([
            'po_customer_id' => $poCustomerId,
            'po_number' =>
                $selectedDo->poCustomer->po_number ?? '',
            'customer_name' =>
                $selectedDo->poCustomer->customer->name ?? '',
            'customer_address' =>
                $selectedDo->poCustomer->customer->address ?? '',
            'selected_do_id' => $selectedDo->id,
            'delivery_orders' => $deliveryOrders,
            'invoices' => $invoices,
        ]);
    }

    public function getInvoicesByPo($po_customer_id)
    {
        $invoices = InvoiceCustomer::where('po_customer_id', $po_customer_id)
            ->select('id', 'invoice_number', 'type', 'tax_invoice_number')
            ->get();
        return response()->json($invoices);
    }

    public function print($id)
    {
        $receipt = DocumentReceipt::with([
            'poCustomer.customer',
            'deliveryOrders',
            'invoices',
        ])->findOrFail($id);
        $company = Company::where('is_active', true)->first();
        $pdf = Pdf::loadView('document-receipts.print', compact('receipt', 'company'));
        $pdf->setPaper('a4', 'portrait');
        $filename = 'DocumentReceipt_' . $receipt->id . '.pdf';
        return $pdf->stream($filename);
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