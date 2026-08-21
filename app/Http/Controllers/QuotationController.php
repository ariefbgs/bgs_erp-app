<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Company;
use App\Models\PoCustomer;
use App\Models\InvoiceCustomer;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = Quotation::with('customer');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quotation_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('details.product', function ($pq) use ($search) {
                        $pq->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $quotations = $query->orderBy('updated_at', 'desc')->paginate(10);
        $quotations->appends($request->only(['search', 'status']));

        return view('quotations.index', compact('quotations'));
    }

    public function create()
    {
        $customers = Customer::all();
        $products = Product::all();
        
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

        return view('quotations.create', compact('customers', 'products', 'existingDeliveryTimes', 'existingPaymentTerms'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'date' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'show_image_on_print' => 'nullable|boolean',
                'discount_percent' => 'nullable|numeric|min:0|max:100',
                'discount_amount' => 'nullable|numeric|min:0',
                'discount_trigger' => 'nullable|in:percent,amount',
                'dpp' => 'nullable|numeric|min:0',
                'tax_percent' => 'nullable|numeric|min:0|max:100',
                'tax_amount' => 'nullable|numeric|min:0',
                'pph23_percent' => 'nullable|numeric|min:0|max:100',
                'pph23_amount' => 'nullable|numeric|min:0',
                'total' => 'nullable|numeric|min:0'
            ]);

            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $discountTrigger = $request->discount_trigger ?? 'percent';
            $discountPercent = $request->discount_percent ?? 0;
            $discountAmount = $request->discount_amount ?? 0;
            $dpp = $request->dpp ?? ($subtotal - $discountAmount);
            $taxPercent = $request->tax_percent ?? 0;
            $taxAmount = $request->tax_amount ?? 0;
            $pph23Percent = $request->pph23_percent ?? 0;
            $pph23Amount = $request->pph23_amount ?? 0;
            $total = $request->total ?? ($dpp + $taxAmount - $pph23Amount);

            if ($dpp < 0) $dpp = 0;
            if ($total < 0) $total = 0;

            $validUntil = $request->valid_until;
            if (empty($validUntil)) {
                $validUntil = date('Y-m-d', strtotime($request->date . ' +7 days'));
            }

            $quoDate = $request->date;
            $currentYear = date('Y', strtotime($quoDate));
            $currentMonth = date('n', strtotime($quoDate));

            $lastQuotation = Quotation::whereYear('date', $currentYear)
                ->orderBy('id', 'desc')
                ->first();

            if ($lastQuotation) {
                $lastNumber = (int) explode('/', $lastQuotation->quotation_number)[0];
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }

            $romanMonthStr = $this->romanMonth($currentMonth);
            $quotationNumber = $newNumber . '/SPH/BGS/' . $romanMonthStr . '/' . $currentYear;

            $quotation = Quotation::create([
                'quotation_number' => $quotationNumber,
                'customer_id' => $request->customer_id,
                'date' => $request->date,
                'valid_until' => $validUntil,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'dpp' => $dpp,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'pph23_percent' => $pph23Percent,
                'pph23_amount' => $pph23Amount,
                'total' => $total,
                'notes' => $request->notes,
                'payment_terms' => $request->payment_terms,
                'delivery_time' => $request->delivery_time,
                'show_image_on_print' => $request->has('show_image_on_print') ? true : false
            ]);

            foreach ($request->items as $item) {
                QuotationDetail::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['product_id'],
                    'specification' => $item['specification'] ?? null,
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price']
                ]);
            }

            return redirect()->route('quotations.index')
                ->with('success', 'Quotation successfully created.');

        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->validator);
        } catch (QueryException $e) {
            $errorMessage = 'Failed to save quotation. ';
            if ($e->getCode() == 23000) {
                $errorMessage .= 'Duplicate data found. Please check the quotation number.';
            } else {
                $errorMessage .= $e->getMessage();
            }
            return back()->withInput()->with('error', $errorMessage);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to save quotation: ' . $e->getMessage());
        }
    }

    public function show(Quotation $quotation)
    {
        $quotation->load('customer', 'details.product');
        return view('quotations.show', compact('quotation'));
    }

    /**
     * EDIT – Admin bisa edit apapun, non-admin hanya jika belum digunakan.
     */
    public function edit(Quotation $quotation)
    {
        // Admin override: boleh edit apapun
        if ($this->isAdmin()) {
            $customers = Customer::all();
            $products = Product::all();
            $quotation->load('details');

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

            return view('quotations.edit', compact('quotation', 'customers', 'products', 'existingDeliveryTimes', 'existingPaymentTerms'));
        }

        // Non-admin: cek apakah quotation sudah digunakan
        if ($this->isQuotationUsed($quotation)) {
            return redirect()->route('quotations.index')
                ->with('error', 'Quotation cannot be edited because it has been used in Purchase Order, Invoice, or Delivery Order.');
        }

        $customers = Customer::all();
        $products = Product::all();
        $quotation->load('details');

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

        return view('quotations.edit', compact('quotation', 'customers', 'products', 'existingDeliveryTimes', 'existingPaymentTerms'));
    }

    /**
     * UPDATE – Admin bisa update apapun, non-admin hanya jika belum digunakan.
     */
    public function update(Request $request, Quotation $quotation)
    {
        // Admin override: boleh update apapun
        if (!$this->isAdmin()) {
            // Non-admin: cek apakah quotation sudah digunakan
            if ($this->isQuotationUsed($quotation)) {
                return back()->with('error', 'Quotation cannot be updated because it has been used in Purchase Order, Invoice, or Delivery Order.');
            }
        }

        try {
            $validated = $request->validate([
                'quotation_number' => 'required|string|max:50|unique:quotations,quotation_number,' . $quotation->id,
                'customer_id' => 'required|exists:customers,id',
                'date' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'show_image_on_print' => 'nullable|boolean',
                'discount_percent' => 'nullable|numeric|min:0|max:100',
                'discount_amount' => 'nullable|numeric|min:0',
                'discount_trigger' => 'nullable|in:percent,amount',
                'dpp' => 'nullable|numeric|min:0',
                'tax_percent' => 'nullable|numeric|min:0|max:100',
                'tax_amount' => 'nullable|numeric|min:0',
                'pph23_percent' => 'nullable|numeric|min:0|max:100',
                'pph23_amount' => 'nullable|numeric|min:0',
                'total' => 'nullable|numeric|min:0'
            ]);

            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $discountTrigger = $request->discount_trigger ?? 'percent';
            $discountPercent = $request->discount_percent ?? 0;
            $discountAmount = $request->discount_amount ?? 0;
            $dpp = $request->dpp ?? ($subtotal - $discountAmount);
            $taxPercent = $request->tax_percent ?? 0;
            $taxAmount = $request->tax_amount ?? 0;
            $pph23Percent = $request->pph23_percent ?? 0;
            $pph23Amount = $request->pph23_amount ?? 0;
            $total = $request->total ?? ($dpp + $taxAmount - $pph23Amount);

            if ($dpp < 0) $dpp = 0;
            if ($total < 0) $total = 0;

            $validUntil = $request->valid_until;
            if (empty($validUntil)) {
                $validUntil = date('Y-m-d', strtotime($request->date . ' +7 days'));
            }

            // Update header – status bisa diubah dari form
            $quotation->update([
                'quotation_number' => $request->quotation_number,
                'customer_id' => $request->customer_id,
                'date' => $request->date,
                'valid_until' => $validUntil,
                'status' => $request->status ?? $quotation->status,
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'dpp' => $dpp,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'pph23_percent' => $pph23Percent,
                'pph23_amount' => $pph23Amount,
                'total' => $total,
                'notes' => $request->notes,
                'payment_terms' => $request->payment_terms,
                'delivery_time' => $request->delivery_time,
                'show_image_on_print' => $request->has('show_image_on_print') ? true : false
            ]);

            // Hapus detail lama
            $quotation->details()->delete();

            // Simpan detail baru
            foreach ($request->items as $item) {
                QuotationDetail::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['product_id'],
                    'specification' => $item['specification'] ?? null,
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price']
                ]);
            }

            return redirect()->route('quotations.index')
                ->with('success', 'Quotation successfully updated.');

        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->validator);
        } catch (QueryException $e) {
            $errorMessage = 'Failed to update quotation. ';
            if ($e->getCode() == 23000) {
                $errorMessage .= 'Duplicate data found. Please check the quotation number.';
            } else {
                $errorMessage .= $e->getMessage();
            }
            return back()->withInput()->with('error', $errorMessage);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update quotation: ' . $e->getMessage());
        }
    }

    /**
     * DESTROY – Admin bisa hapus apapun, non-admin hanya jika belum digunakan.
     */
    public function destroy(Quotation $quotation)
    {
        // Admin override: boleh hapus apapun
        if (!$this->isAdmin()) {
            // Non-admin: cek apakah quotation sudah digunakan
            if ($this->isQuotationUsed($quotation)) {
                return back()->with('error', 'Quotation cannot be deleted because it has been used in Purchase Order, Invoice, or Delivery Order.');
            }
        }

        try {
            $quotation->details()->delete();
            $quotation->delete();

            return redirect()->route('quotations.index')
                ->with('success', 'Quotation successfully deleted.');

        } catch (QueryException $e) {
            return back()->with('error', 'Quotation cannot be deleted because it is related to other data.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete quotation: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        try {
            $quotation = Quotation::with('customer', 'details.product')->findOrFail($id);
            $company = Company::where('is_active', true)->first();

            // Auto update status dari draft ke sent jika print
            if ($quotation->status === 'draft') {
                $quotation->status = 'sent';
                $quotation->save();
            }

            $pdf = Pdf::loadView('quotations.print', compact('quotation', 'company'));
            $pdf->setPaper('a4', 'portrait');

            $cleanName = preg_replace('/[^A-Za-z0-9_-]/', '_', $quotation->quotation_number);
            $filename = 'quotation_' . $cleanName . '.pdf';

            return $pdf->stream($filename, ['Attachment' => 0]);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to print quotation: ' . $e->getMessage());
        }
    }

    public function updateProductPrice(Request $request)
    {
        try {
            $product = Product::findOrFail($request->product_id);
            $product->price = $request->new_price;
            $product->save();

            return response()->json(['success' => true, 'message' => 'Product price successfully updated.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update price: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cek apakah user saat ini adalah admin (arief@gmail.com)
     */
    private function isAdmin()
    {
        return auth()->check() && auth()->user()->email === 'arief@gmail.com';
    }

    /**
     * Cek apakah quotation sudah digunakan di transaksi lain.
     * Digunakan untuk non-admin.
     */
    private function isQuotationUsed(Quotation $quotation)
    {
        // Cek PO Customer
        if (PoCustomer::where('quotation_id', $quotation->id)->exists()) {
            return true;
        }

        // Cek Invoice Customer melalui PO Customer
        if (InvoiceCustomer::whereHas('poCustomer', function ($query) use ($quotation) {
            $query->where('quotation_id', $quotation->id);
        })->exists()) {
            return true;
        }

        // Cek Delivery Order melalui PO Customer
        if (DeliveryOrder::whereHas('poCustomer', function ($query) use ($quotation) {
            $query->where('quotation_id', $quotation->id);
        })->exists()) {
            return true;
        }

        return false;
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