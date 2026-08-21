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
use Intervention\Image\ImageManager;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentReceipt::with('deliveryOrder.poCustomer.customer');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('deliveryOrder.poCustomer.customer', function ($cq) use ($search) {
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
            $query->whereHas('deliveryOrder', function ($q) use ($request) {
                $q->where('status', $request->do_status);
            });
        }

        $receipts = $query->orderBy('created_at', 'desc')->paginate(10);
        $receipts->appends($request->only(['search', 'status', 'do_status']));

        return view('document-receipts.index', compact('receipts'));
    }

    public function create()
    {
        // ✅ Hanya tampilkan DO dengan status pending atau partial
        $deliveryOrders = DeliveryOrder::with('poCustomer.customer')
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('document-receipts.create', compact('deliveryOrders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'delivery_order_id' => 'required|exists:delivery_orders,id',
            'receipt_date' => 'required|date',
            'invoice_number' => 'required|array|min:1',
            'invoice_number.*' => 'exists:invoice_customers,invoice_number',
            'tax_invoice_number' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
            'notes' => 'nullable|string',
        ]);

        // Gabungkan invoice_number menjadi string dengan koma
        $invoiceNumbers = implode(', ', $request->invoice_number);

        // Ambil tax_invoice_number dari invoice pertama yang dipilih
         $taxInvoiceNumbers = [];
        if (!empty($request->invoice_number)) {
            $invoices = InvoiceCustomer::whereIn('invoice_number', $request->invoice_number)->get();
            foreach ($invoices as $inv) {
                if (!empty($inv->tax_invoice_number)) {
                    $taxInvoiceNumbers[] = $inv->tax_invoice_number;
                }
            }
        }
        $taxInvoiceNumber = !empty($taxInvoiceNumbers) ? implode(', ', $taxInvoiceNumbers) : null;
        
        // Generate receipt number
        $receiptDate = $request->receipt_date;
        $currentYear = date('Y', strtotime($receiptDate));
        $currentMonth = date('n', strtotime($receiptDate));

        $lastReceipt = DocumentReceipt::whereYear('receipt_date', $currentYear)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReceipt) {
            $lastNumber = (int) explode('/', $lastReceipt->receipt_number)[0];
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        $romanMonthStr = $this->romanMonth($currentMonth);
        $receiptNumber = $newNumber . '/DR/BGS/' . $romanMonthStr . '/' . $currentYear;

        // Upload attachment
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $originalName) . '.' . $file->getClientOriginalExtension();
            $folder = 'document-receipts';
            
            if (!Storage::disk('public')->exists($folder)) {
                Storage::disk('public')->makeDirectory($folder);
            }
            
            if (in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
                $manager = new ImageManager(['driver' => 'gd']);
                $image = $manager->make($file->getRealPath());
                $image->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $image->encode('jpg', 80);
                Storage::disk('public')->put($folder . '/' . $safeName, (string) $image->encode());
            } else {
                Storage::disk('public')->putFileAs($folder, $file, $safeName);
            }
            $attachmentPath = $folder . '/' . $safeName;
        }

        DocumentReceipt::create([
            'receipt_number' => $receiptNumber,
            'delivery_order_id' => $request->delivery_order_id,
            'receipt_date' => $request->receipt_date,
            'invoice_number' => $invoiceNumbers,
            'tax_invoice_number' => $taxInvoiceNumber ?? $request->tax_invoice_number,
            'attachment' => $attachmentPath,
            'notes' => $request->notes,
            'status' => 'draft',
        ]);

        return redirect()->route('document-receipts.index')->with('success', 'Document Receipt created successfully.');
    }

    public function show($id)
    {
        $receipt = DocumentReceipt::with('deliveryOrder.poCustomer.customer')->findOrFail($id);
        return view('document-receipts.show', compact('receipt'));
    }

    public function edit($id)
    {
        $receipt = DocumentReceipt::findOrFail($id);
        $deliveryOrders = DeliveryOrder::with('poCustomer.customer')
            ->whereIn('status', ['pending', 'partial', 'shipped', 'delivered'])
            ->get();
        $selectedInvoices = !empty($receipt->invoice_number) ? explode(', ', $receipt->invoice_number) : [];
        return view('document-receipts.edit', compact('receipt', 'deliveryOrders', 'selectedInvoices'));
    }

    public function update(Request $request, $id)
    {
        $receipt = DocumentReceipt::findOrFail($id);

        $request->validate([
            'delivery_order_id' => 'required|exists:delivery_orders,id',
            'receipt_date' => 'required|date',
            'invoice_number' => 'required|array|min:1',
            'invoice_number.*' => 'exists:invoice_customers,invoice_number',
            'tax_invoice_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,delivered',
        ]);

        $invoiceNumbers = implode(', ', $request->invoice_number);

        $taxInvoiceNumber = null;
        if (!empty($request->invoice_number)) {
            $firstInvoice = InvoiceCustomer::where('invoice_number', $request->invoice_number[0])->first();
            if ($firstInvoice) {
                $taxInvoiceNumber = $firstInvoice->tax_invoice_number;
            }
        }

        $attachmentPath = $receipt->attachment;
        if ($request->hasFile('attachment')) {
            if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                Storage::disk('public')->delete($attachmentPath);
            }
            $file = $request->file('attachment');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $originalName) . '.' . $file->getClientOriginalExtension();
            $folder = 'document-receipts';
            
            if (!Storage::disk('public')->exists($folder)) {
                Storage::disk('public')->makeDirectory($folder);
            }
            
            if (in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
                $manager = new ImageManager(['driver' => 'gd']);
                $image = $manager->make($file->getRealPath());
                $image->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $image->encode('jpg', 80);
                Storage::disk('public')->put($folder . '/' . $safeName, (string) $image->encode());
            } else {
                Storage::disk('public')->putFileAs($folder, $file, $safeName);
            }
            $attachmentPath = $folder . '/' . $safeName;
        }

        $receipt->update([
            'delivery_order_id' => $request->delivery_order_id,
            'receipt_date' => $request->receipt_date,
            'invoice_number' => $invoiceNumbers,
            'tax_invoice_number' => $taxInvoiceNumber ?? $request->tax_invoice_number,
            'attachment' => $attachmentPath,
            'notes' => $request->notes,
            'status' => $request->status,
        ]);

        return redirect()->route('document-receipts.index')
            ->with('success', 'Document Receipt updated successfully.');
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

    public function getInvoicesByPo($po_customer_id)
    {
        $invoices = InvoiceCustomer::where('po_customer_id', $po_customer_id)
            ->select('id', 'invoice_number', 'type', 'tax_invoice_number')
            ->get();
        return response()->json($invoices);
    }

    public function print($id)
    {
        $receipt = DocumentReceipt::with('deliveryOrder.poCustomer.customer')->findOrFail($id);
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