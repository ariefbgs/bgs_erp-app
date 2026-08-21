<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::all();
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_code' => 'required|unique:suppliers',
            'name' => 'required',
            'email' => 'nullable|email|unique:suppliers',
            'phone' => 'nullable',
            'address' => 'nullable',
            'tax_number' => 'nullable'
        ]);

        Supplier::create($request->all());

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier berhasil ditambahkan');
    }

    public function show(Supplier $supplier)
    {
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'supplier_code' => 'required|unique:suppliers,supplier_code,' . $supplier->id,
            'name' => 'required',
            'email' => 'nullable|email|unique:suppliers,email,' . $supplier->id,
            'phone' => 'nullable',
            'address' => 'nullable',
            'tax_number' => 'nullable'
        ]);

        $supplier->update($request->all());

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier berhasil diupdate');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier berhasil dihapus');
    }
}