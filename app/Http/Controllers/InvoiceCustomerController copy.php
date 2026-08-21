<?php

namespace App\Http\Controllers;

use App\Models\InvoiceCustomer;
use App\Models\InvoiceCustomerDetail;
use App\Models\PoCustomer;
use App\Models\Product;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->whereIn('status', ['received', 'processed'])
            ->whereIn('invoice_status', ['issue yet', 'partial'])
            ->orderBy('po_date', 'desc')
            ->get();

        $poCustomers->each(function ($po) {
            $totalInvoiced = InvoiceCustomer::where('po_customer_id', $po->id)
                ->where('status', '!=', 'cancelled')
                ->sum('total');
            $po->remaining_total = max(0, $po->total - $totalInvoiced);
        });

        // Ambil daftar invoice yang bisa dijadikan parent (hanya yang status partial)
        $parentInvoices = InvoiceCustomer::where('status', 'partial')
            ->where('payment_status', '!=', 'paid')
            ->orderBy('invoice_number')
            ->get();

        return view('invoice_customers.create', compact('poCustomers', 'parentInvoices'));
    }

    // =====================================================
    // AJAX GET PO DETAIL
    // =====================================================
    public function getPoCustomerDetails($id)
    {
        $poCustomer = PoCustomer::with(['details.product', 'customer'])->findOrFail($id);
        $items = [];
        foreach ($poCustomer->details as $detail) {
            $items[] = [
                'product_id'   => $detail->product_id,
                'product_name' => $detail->product->name ?? '-',
                'product_code' => $detail->product->product_code ?? '-',
                'brand'        => $detail->product->brand ?? '-',
                'unit'         => $detail->product->unit ?? '-',
                'quantity'     => $detail->quantity,
                'unit_price'   => $detail->unit_price,
            ];
        }

        return response()->json([
            'po_customer' => $poCustomer,
            'customer'    => $poCustomer->customer,
            'items'       => $items,
            'payment_terms' => $poCustomer->payment_terms,
            'delivery_time' => $poCustomer->delivery_time,
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
            'invoice_date'   => 'required|date',
            'type'           => 'required|in:proforma,sales',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price_numeric' => 'required|numeric|min:0',
            'dp_percent'     => 'nullable|numeric|min:0|max:100',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric',
            'payment_amount' => 'nullable|numeric',
            'tax_percent'    => 'nullable|numeric|min:0|max:100',
            'pph23_percent'  => 'nullable|numeric|min:0|max:100',
            'payment_terms'  => 'nullable|string|max:255',
            'delivery_time'  => 'nullable|string|max:255',
            'due_date'       => 'nullable|date',
            'tax_invoice_number' => 'nullable|string|max:100',
            'tax_invoice_attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
            'notes'          => 'nullable|string',
            'payment_status' => 'nullable|in:sent,paid',
            'parent_invoice_id' => 'nullable|exists:invoice_customers,id', // tambahan
        ]);

        DB::beginTransaction();
        try {
            $poCustomer = PoCustomer::findOrFail($request->po_customer_id);

            // Hitung total invoice yang sudah dikeluarkan (kecuali yang dibatalkan)
            $totalInvoiced = InvoiceCustomer::where('po_customer_id', $poCustomer->id)
                ->where('status', '!=', 'cancelled')
                ->sum('total');
            $remaining = max(0, $poCustomer->total - $totalInvoiced);

            // Generate nomor invoice
            $invoiceDate = $request->invoice_date;
            $year = date('Y', strtotime($invoiceDate));
            $month = date('n', strtotime($invoiceDate));

            $lastInvoice = InvoiceCustomer::whereYear('invoice_date', $year)
                ->orderBy('id', 'desc')
                ->first();

            if ($lastInvoice) {
                $lastNumber = (int) explode('/', $lastInvoice->invoice_number)[0];
                $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '001';
            }

            $romanMonthStr = $this->romanMonth($month);
            $invoiceNumber = $newNumber . '/INV/BGS/' . $romanMonthStr . '/' . $year;

            // Hitung subtotal
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += (float) $item['quantity'] * (float) $item['unit_price_numeric'];
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
            $remainingAfterInvoice = ($dpAmount > 0) ? ($subtotal - $dpAmount) : 0;

            // Validasi total tidak melebihi sisa PO
            if ($total > $remaining) {
                return back()->withInput()->with('error',
                    'Total invoice Rp ' . number_format($total, 0, ',', '.') .
                    ' melebihi sisa tagihan PO Customer yang tersisa: Rp ' . number_format($remaining, 0, ',', '.')
                );
            }

            // Jika ada parent_invoice_id, pastikan parent masih partial dan total anak tidak melebihi sisa parent
            if ($request->filled('parent_invoice_id')) {
                $parent = InvoiceCustomer::findOrFail($request->parent_invoice_id);
                $totalChild = $parent->childInvoices()->where('status', '!=', 'cancelled')->sum('total');
                if (($totalChild + $total) > $parent->total) {
                    return back()->withInput()->with('error',
                        'Total invoice anak (' . number_format($total, 0, ',', '.') .
                        ') ditambah total anak sebelumnya (' . number_format($totalChild, 0, ',', '.') .
                        ') melebihi total induk (' . number_format($parent->total, 0, ',', '.') . ')'
                    );
                }
            }

            // Attachment
            $taxAttachmentPath = null;
            if ($request->hasFile('tax_invoice_attachment')) {
                $taxAttachmentPath = $request->file('tax_invoice_attachment')->store('tax_invoice', 'public');
            }

            $data = [
                'invoice_number' => $invoiceNumber,
                'tax_invoice_number' => $request->tax_invoice_number,
                'tax_invoice_attachment' => $taxAttachmentPath,
                'type' => $request->type,
                'po_customer_id' => $request->po_customer_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
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
                'status' => ($remainingAfterInvoice > 0) ? 'partial' : 'completed',
                'payment_status' => $request->payment_status ?? 'sent',
                'payment_terms' => $request->payment_terms,
                'delivery_time' => $request->delivery_time,
                'notes' => $request->notes,
                'parent_invoice_id' => $request->parent_invoice_id ?? null,
            ];

            $invoice = InvoiceCustomer::create($data);

            foreach ($request->items as $item) {
                InvoiceCustomerDetail::create([
                    'invoice_customer_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'unit_price' => (float) $item['unit_price_numeric'],
                    'subtotal' => (float) $item['quantity'] * (float) $item['unit_price_numeric'],
                ]);
            }

            DB::commit();

            $this->updatePoCustomerInvoiceStatus($request->po_customer_id);

            return redirect()->route('invoice-customers.index')
                ->with('success', 'Invoice successfully created.');

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'invoice_number')) {
                \Log::warning('Duplicate invoice number detected: ' . $e->getMessage());
                return back()->withInput()->with('error', 'Nomor invoice sudah terpakai. Silakan periksa tanggal invoice atau hubungi admin.');
            }
            \Log::error('STORE ERROR: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Gagal menyimpan invoice: ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('STORE ERROR: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Gagal menyimpan invoice: ' . $e->getMessage());
        }
    }

    // =====================================================
    // EDIT
    // =====================================================
    public function edit($id)
    {
        $invoice = InvoiceCustomer::with(['poCustomer.customer', 'details.product', 'parentInvoice'])->findOrFail($id);

        if (!$this->isAdmin() && $invoice->payment_status == 'paid') {
            return redirect()->route('invoice-customers.index')
                ->with('error', 'Invoice already paid and cannot be edited.');
        }

        $products = Product::orderBy('name')->get();
        // Ambil daftar invoice yang bisa dijadikan parent (partial, bukan dirinya sendiri)
        $parentInvoices = InvoiceCustomer::where('status', 'partial')
            ->where('payment_status', '!=', 'paid')
            ->where('id', '!=', $id)
            ->orderBy('invoice_number')
            ->get();

        return view('invoice_customers.edit', compact('invoice', 'products', 'parentInvoices'));
    }

    // =====================================================
    // UPDATE – invoice_number TIDAK bisa diubah
    // =====================================================
    public function update(Request $request, $id)
    {
        $invoice = InvoiceCustomer::findOrFail($id);

        if (!$this->isAdmin() && $invoice->payment_status == 'paid') {
            return back()->with('error', 'Invoice already paid and cannot be edited.');
        }

        // Validasi – HAPUS invoice_number, TAMBAHKAN parent_invoice_id
        $validated = $request->validate([
            'type' => 'required|in:proforma,sales',
            'invoice_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price_numeric' => 'required|numeric|min:0',
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
            'tax_invoice_attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
            'delete_attachment' => 'nullable|string',
            'notes' => 'nullable|string',
            'payment_status' => 'required|in:sent,paid',
            'parent_invoice_id' => 'nullable|exists:invoice_customers,id',
        ]);

        DB::beginTransaction();
        try {
            // Hitung subtotal baru
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += (float) $item['quantity'] * (float) $item['unit_price_numeric'];
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
            $remainingAfterInvoice = ($dpAmount > 0) ? ($subtotal - $dpAmount) : 0;

            // ===== VALIDASI SISA PO (dengan memperhitungkan invoice lain) =====
            $poCustomer = PoCustomer::findOrFail($invoice->po_customer_id);
            $totalInvoicedLain = InvoiceCustomer::where('po_customer_id', $poCustomer->id)
                ->where('status', '!=', 'cancelled')
                ->where('id', '!=', $id)
                ->sum('total');
            $remainingPo = max(0, $poCustomer->total - $totalInvoicedLain);
            if ($total > $remainingPo) {
                return back()->withInput()->with('error',
                    'Total invoice baru Rp ' . number_format($total, 0, ',', '.') .
                    ' melebihi sisa tagihan PO yang tersisa (Rp ' . number_format($remainingPo, 0, ',', '.') .
                    ') setelah dikurangi invoice lain.'
                );
            }

            // ===== VALIDASI PARENT INVOICE =====
            if ($request->filled('parent_invoice_id')) {
                $parent = InvoiceCustomer::findOrFail($request->parent_invoice_id);
                if ($parent->id == $id) {
                    return back()->withInput()->with('error', 'Tidak bisa menjadikan diri sendiri sebagai induk.');
                }
                $totalChildLain = $parent->childInvoices()
                    ->where('status', '!=', 'cancelled')
                    ->where('id', '!=', $id)
                    ->sum('total');
                if (($totalChildLain + $total) > $parent->total) {
                    return back()->withInput()->with('error',
                        'Total invoice anak (' . number_format($total, 0, ',', '.') .
                        ') ditambah total anak sebelumnya (' . number_format($totalChildLain, 0, ',', '.') .
                        ') melebihi total induk (' . number_format($parent->total, 0, ',', '.') . ')'
                    );
                }
            }

            // Attachment
            $taxAttachmentPath = $invoice->tax_invoice_attachment;
            if ($request->input('delete_attachment') == '1' || $request->hasFile('tax_invoice_attachment')) {
                if ($invoice->tax_invoice_attachment && Storage::disk('public')->exists($invoice->tax_invoice_attachment)) {
                    Storage::disk('public')->delete($invoice->tax_invoice_attachment);
                }
                $taxAttachmentPath = null;
            }
            if ($request->hasFile('tax_invoice_attachment')) {
                $taxAttachmentPath = $request->file('tax_invoice_attachment')->store('tax_invoice', 'public');
            }

            // Update header – TANPA invoice_number
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
                'remaining_amount' => $remainingAfterInvoice,
                'status' => ($remainingAfterInvoice > 0) ? 'partial' : 'completed',
                'payment_status' => $request->payment_status,
                'payment_terms' => $request->payment_terms,
                'delivery_time' => $request->delivery_time,
                'notes' => $request->notes,
                'parent_invoice_id' => $request->parent_invoice_id ?? null,
            ]);

            // Hapus detail lama, simpan baru
            $invoice->details()->delete();
            foreach ($request->items as $item) {
                InvoiceCustomerDetail::create([
                    'invoice_customer_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'unit_price' => (float) $item['unit_price_numeric'],
                    'subtotal' => (float) $item['quantity'] * (float) $item['unit_price_numeric'],
                ]);
            }

            DB::commit();
            $this->updatePoCustomerInvoiceStatus($invoice->po_customer_id);

            return redirect()->route('invoice-customers.index')
                ->with('success', 'Invoice successfully updated.');

        } catch (\Exception $e) {
            DB::rollBack();
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

        if (!$this->isAdmin() && $invoice->payment_status == 'paid') {
            return back()->with('error', 'Invoice already paid and cannot be deleted.');
        }

        $poCustomerId = $invoice->po_customer_id;

        if ($invoice->tax_invoice_attachment && Storage::disk('public')->exists($invoice->tax_invoice_attachment)) {
            Storage::disk('public')->delete($invoice->tax_invoice_attachment);
        }

        $invoice->details()->delete();
        $invoice->delete();

        try {
            $this->updatePoCustomerInvoiceStatus($poCustomerId);
        } catch (\Exception $e) {
            \Log::error('Gagal update status PO: ' . $e->getMessage());
        }

        return redirect()->route('invoice-customers.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    // =====================================================
    // CANCEL
    // =====================================================
    public function cancel($id)
    {
        $invoice = InvoiceCustomer::findOrFail($id);

        if (!$this->isAdmin() && $invoice->payment_status == 'paid') {
            return back()->with('error', 'Invoice already paid and cannot be cancelled.');
        }

        $invoice->update(['status' => 'cancelled']);

        try {
            $this->updatePoCustomerInvoiceStatus($invoice->po_customer_id);
        } catch (\Exception $e) {
            \Log::error('Gagal update status PO: ' . $e->getMessage());
        }

        return redirect()->route('invoice-customers.index')
            ->with('success', 'Invoice cancelled successfully.');
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
        $pdf = Pdf::loadView('invoice_customers.print', compact('invoice', 'company'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('Invoice_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }

    public function printInvoice($id)
    {
        $invoice = InvoiceCustomer::with(['poCustomer.customer', 'details.product', 'parentInvoice'])->findOrFail($id);
        $company = Company::where('is_active', true)->first();
        $pdf = Pdf::loadView('invoice_customers.print_invoice', compact('invoice', 'company'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('Invoice_' . str_replace('/', '_', $invoice->invoice_number) . '.pdf');
    }

    // =====================================================
    // PRIVATE HELPERS
    // =====================================================
    private function updatePoCustomerInvoiceStatus($poCustomerId)
    {
        $poCustomer = PoCustomer::find($poCustomerId);
        if (!$poCustomer) return;

        $totalInvoiced = InvoiceCustomer::where('po_customer_id', $poCustomerId)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        $totalPo = $poCustomer->total;

        if ($totalInvoiced >= $totalPo) {
            $status = 'completed';
        } elseif ($totalInvoiced > 0) {
            $status = 'partial';
        } else {
            $status = 'issue yet';
        }

        $poCustomer->update(['invoice_status' => $status]);
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

    /**
     * Cek apakah user saat ini adalah admin (arief@gmail.com)
     */
    private function isAdmin()
    {
        return auth()->check() && auth()->user()->email === 'arief@gmail.com';
    }
}