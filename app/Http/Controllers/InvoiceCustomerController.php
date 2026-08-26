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
            ->whereIn('status', ['received', 'proceed'])
            ->whereIn('invoice_status', ['issue yet', 'partial'])
            ->orderBy('po_date', 'desc')
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
    public function getPoCustomerDetails($id)
    {
        $poCustomer = PoCustomer::with(['details.product', 'customer'])->findOrFail($id);
        $rootInvoice = $this->rootInvoiceForPo($poCustomer->id);
        $remainingPreTax = $this->remainingPreTaxAmount($poCustomer);
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
            'remaining_subtotal_before_tax' => $remainingPreTax,
            'has_previous_invoice' => $rootInvoice !== null,
            'root_invoice_number' => $rootInvoice?->invoice_number,
            'recommended_percent' => (float) $poCustomer->subtotal > 0
                ? min(100, ($remainingPreTax / (float) $poCustomer->subtotal) * 100)
                : 0,
            'tax_percent' => (float) ($poCustomer->tax_percent ?? 0),
            'pph23_percent' => $this->poPphPercent($poCustomer),
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
        ]);

        try {
            $poCustomer = PoCustomer::findOrFail($request->po_customer_id);

            $remainingPreTax = $this->remainingPreTaxAmount($poCustomer);

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
            
            // Sisa canonical selalu mengikuti nilai PO Customer dikurangi seluruh
            // invoice aktif, baik invoice DP maupun payment-after-delivery.
            $remainingAfterInvoice = max(0, $remainingPreTax - $baseAmount);

            if (round($baseAmount) > round($remainingPreTax)) {
                return back()->withInput()->with('error',
                    'Payment Amount Rp ' . number_format($baseAmount, 0, ',', '.') .
                    ' melebihi sisa PO Amount: Rp ' . number_format($remainingPreTax, 0, ',', '.')
                );
            }

            // Attachment
            $taxAttachmentPath = null;
            if ($request->hasFile('tax_invoice_attachment')) {
                $taxAttachmentPath = $request->file('tax_invoice_attachment')->store('tax_invoice', 'public');
            }

            // Ã¢Å“â€¦ PASTIKAN remaining_amount DIKIRIM KE DATABASE
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
                'remaining_amount' => $remainingAfterInvoice, // Ã¢Å“â€¦ PASTIKAN INI ADA!
                'status' => ($remainingAfterInvoice > 0) ? 'partial' : 'completed',
                'payment_status' => $request->payment_status ?? 'sent',
                'payment_terms' => $request->payment_terms,
                'delivery_time' => $request->delivery_time,
                'notes' => $request->notes,
                'parent_invoice_id' => null,
            ];

            // Ã¢Å“â€¦ DEBUG: Log data sebelum simpan
            \Log::info('Data yang akan disimpan:', $data);

            DB::beginTransaction();

            $poCustomer = PoCustomer::whereKey($poCustomer->id)
                ->lockForUpdate()
                ->firstOrFail();
            $rootInvoice = $this->rootInvoiceForPo($poCustomer->id);
            $remainingPreTax = $this->remainingPreTaxAmount($poCustomer);
            if (round($baseAmount) > round($remainingPreTax)) {
                throw ValidationException::withMessages([
                    'items' => 'Payment Amount melebihi sisa PO Amount.',
                ]);
            }
            $remainingAfterInvoice = max(0, $remainingPreTax - $baseAmount);
            $data['parent_invoice_id'] = $rootInvoice?->id;

            $invoice = InvoiceCustomer::create($data);

            $invoice->update([
                'remaining_amount' => $remainingAfterInvoice,
                'status' => $remainingAfterInvoice > 0 ? 'partial' : 'completed',
            ]);

            // Simpan detail
            foreach ($request->items as $item) {
                InvoiceCustomerDetail::create([
                    'invoice_customer_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                    'unit_price' => (float) $item['unit_price_numeric'],
                    'subtotal' => (float) $item['quantity'] * (float) $item['unit_price_numeric'],
                ]);
            }

            $poCustomer->updateInvoiceStatus();
            DB::commit();

            return redirect()->route('invoice-customers.index')
                ->with('success', 'Invoice successfully created.');

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withInput()->withErrors($e->validator);
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
            ->with('poCustomer.customer')
            ->orderBy('invoice_number')
            ->get();

        return view('invoice_customers.edit', compact('invoice', 'products', 'parentInvoices'));
    }

    // =====================================================
    // UPDATE Ã¢â‚¬â€œ invoice_number TIDAK bisa diubah
    // =====================================================
    public function update(Request $request, $id)
    {
        $invoice = InvoiceCustomer::findOrFail($id);

        if (!$this->isAdmin() && $invoice->payment_status == 'paid') {
            return back()->with('error', 'Invoice already paid and cannot be edited.');
        }

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
        ]);

        try {
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

            // Ã¢Å“â€¦ UPDATE termasuk remaining_amount
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
            $remainingAfterInvoice = max(0, $remainingPreTax - $baseAmount);

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
                'remaining_amount' => $remainingAfterInvoice, // Ã¢Å“â€¦ PASTIKAN INI ADA!
                'status' => ($remainingAfterInvoice > 0) ? 'partial' : 'completed',
                'payment_status' => $request->payment_status,
                'payment_terms' => $request->payment_terms,
                'delivery_time' => $request->delivery_time,
                'notes' => $request->notes,
            ]);

            // Ã¢Å“â€¦ DEBUG: Log update
            \Log::info('Update data:', [
                'remaining_amount' => $remainingAfterInvoice,
                'status' => ($remainingAfterInvoice > 0) ? 'partial' : 'completed'
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

            $poCustomer->updateInvoiceStatus();
            DB::commit();

            return redirect()->route('invoice-customers.index')
                ->with('success', 'Invoice successfully updated.');

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withInput()->withErrors($e->validator);
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

        if (!$this->isAdmin() && $invoice->payment_status == 'paid') {
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
        return auth()->check() && auth()->user()->email === 'arief@gmail.com';
    }
}
