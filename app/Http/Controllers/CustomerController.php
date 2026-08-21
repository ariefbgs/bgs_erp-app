<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_code', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%")
                  ->orWhere('shipping_address', 'LIKE', "%{$search}%")
                  ->orWhere('document_address', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->orderBy('updated_at', 'desc')->paginate(10);
        $customers->appends($request->only('search'));

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    // ============================================================
    // STORE – dengan try-catch dan notifikasi error
    // ============================================================
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_code' => 'required|unique:customers,customer_code',
                'name' => 'required|unique:customers,name',
                'email' => [
                    'nullable',
                    'regex:/^(-|[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})$/'
                ],
                'phone' => 'nullable',
                'address' => 'nullable',
                'shipping_address' => 'nullable|string',
                'document_address' => 'nullable|string',
                'tax_number' => 'nullable',
                'pic_quotation_name' => 'nullable',
                'pic_quotation_phone' => 'nullable',
                'pic_do_name' => 'nullable',
                'pic_do_phone' => 'nullable',
                'pic_invoice_name' => 'nullable',
                'pic_invoice_phone' => 'nullable',
            ], [
                'name.unique' => 'Nama customer ":input" sudah terdaftar. Silakan gunakan nama lain.',
                'customer_code.unique' => 'Kode customer sudah digunakan, silakan buat kode lain.',
            ]);

            Customer::create($validated);

            return redirect()->route('customers.index')
                ->with('success', 'Customer berhasil ditambahkan.');

        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->validator);
        } catch (QueryException $e) {
            $errorMessage = 'Gagal menyimpan customer. ';
            if ($e->getCode() == 23000) {
                $errorMessage .= 'Data duplikat ditemukan. Pastikan kode dan nama customer unik.';
            } else {
                $errorMessage .= $e->getMessage();
            }
            return back()->withInput()->with('error', $errorMessage);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan customer: ' . $e->getMessage());
        }
    }

    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    // ============================================================
    // EDIT – Tidak dicek relasi, customer tetap bisa diedit 
    // meskipun sudah digunakan di transaksi (cukup peringatan di view)
    // ============================================================
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    // ============================================================
    // UPDATE – dengan try-catch dan notifikasi error
    // ============================================================
    public function update(Request $request, Customer $customer)
    {
        try {
            $validated = $request->validate([
                'customer_code' => 'required|unique:customers,customer_code,' . $customer->id,
                'name' => 'required|unique:customers,name,' . $customer->id,
                'email' => [
                    'nullable',
                    'regex:/^(-|[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})$/'
                ],
                'phone' => 'nullable',
                'address' => 'nullable',
                'shipping_address' => 'nullable|string',
                'document_address' => 'nullable|string',
                'tax_number' => 'nullable',
                'pic_quotation_name' => 'nullable',
                'pic_quotation_phone' => 'nullable',
                'pic_do_name' => 'nullable',
                'pic_do_phone' => 'nullable',
                'pic_invoice_name' => 'nullable',
                'pic_invoice_phone' => 'nullable',
            ], [
                'name.unique' => 'Nama customer ":input" sudah terdaftar. Silakan gunakan nama lain.',
                'customer_code.unique' => 'Kode customer sudah digunakan, silakan buat kode lain.',
            ]);

            $customer->update($validated);

            return redirect()->route('customers.index')
                ->with('success', 'Customer berhasil diupdate.');

        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->validator);
        } catch (QueryException $e) {
            $errorMessage = 'Gagal memperbarui customer. ';
            if ($e->getCode() == 23000) {
                $errorMessage .= 'Data duplikat ditemukan. Pastikan kode dan nama customer unik.';
            } else {
                $errorMessage .= $e->getMessage();
            }
            return back()->withInput()->with('error', $errorMessage);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui customer: ' . $e->getMessage());
        }
    }

    // ============================================================
    // DESTROY – dengan pengecekan relasi dan try-catch
    // ============================================================
    public function destroy(Customer $customer)
    {
        // Cek apakah customer sudah digunakan
        if ($this->isCustomerUsed($customer)) {
            return back()->with('error', 'Customer tidak dapat dihapus karena sudah digunakan di transaksi.');
        }

        try {
            $customer->delete();
            return redirect()->route('customers.index')
                ->with('success', 'Customer berhasil dihapus.');
        } catch (QueryException $e) {
            // Jika ada foreign key constraint yang tidak terdeteksi
            return back()->with('error', 'Customer tidak dapat dihapus karena terkait dengan data lain.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus customer: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        $customer = Customer::findOrFail($id);
        $company = Company::first();
        return view('customers.print', compact('customer', 'company'));
    }

    // ============================================================
    // PRIVATE HELPER: CEK APAKAH CUSTOMER SUDAH DIGUNAKAN
    // ============================================================
    private function isCustomerUsed(Customer $customer)
    {
        // Cek relasi ke tabel transaksi
        // Asumsi model Customer memiliki relasi hasMany ke:
        // - PoCustomer (PO)
        // - Quotation
        // - InvoiceCustomer
        // - DeliveryOrder

        if ($customer->poCustomers()->exists() ||
            $customer->quotations()->exists() ||
            $customer->invoiceCustomers()->exists() ||
            $customer->deliveryOrders()->exists()) {
            return true;
        }

        return false;
    }
}