<?php

namespace App\Http\Controllers;

use App\Models\UserManual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManualController extends Controller
{
    // Tambahkan method __construct ini sebagai tameng pengaman backend
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || Auth::user()->email !== 'arief@gmail.com') {
                abort(403, 'Anda tidak memiliki hak akses untuk mengelola dokumentasi sistem.');
            }
            return $next($request);
        });
    }

    // Mengeset daftar modul ERP yang tersedia agar konsisten
    private $modules = [
        'dashboard' => 'Dashboard / Ringkasan',
        'expenses' => 'Pengeluaran & Biaya',
        'quotations' => 'Quotation / Penawaran',
        'po-customers' => 'PO Customer',
        'po-suppliers' => 'PO Supplier',
        'goods-receipts' => 'Goods Receipt',
        'delivery-orders' => 'Delivery Order',
        'invoice-customers' => 'Sales Invoice'
    ];

    public function index()
    {
        // Hanya ambil modul utama di halaman index, sub-modul bisa di-expand lewat relasi children
        $manuals = UserManual::with('children')->whereNull('parent_id')->get();
        return view('user_manuals.index', compact('manuals'));
    }

    public function create()
    {
        $modules = $this->modules;
        // Ambil semua panduan yang sudah dibuat untuk dijadikan pilihan Parent/Induk
        $parentManuals = UserManual::whereIn('type', ['modul', 'sub_modul'])->get();
        
        return view('user_manuals.create', compact('modules', 'parentManuals'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'module_name' => 'required|string',
            'type' => 'required|in:modul,sub_modul,feature',
            'parent_id' => 'nullable|exists:user_manuals,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        UserManual::create($request->all());

        return redirect()->route('user-manuals.index')
            ->with('success', 'Struktur Panduan sistem berhasil ditambahkan!');
    }

    public function edit($id)
    {
        // 1. Cari data user manual yang sedang di-edit
        $userManual = UserManual::findOrFail($id);

        // 2. Ambil semua data manual untuk pilihan Induk (Parent)
        // Kita ambil semua data agar JavaScript bisa memfilter secara dinamis
        $parentManuals = UserManual::all();

        // 3. Array daftar modul ERP Anda (Sesuaikan dengan daftar modul di tempat Anda)
        $modules = [
            'quotations'   => 'Quotation Management',
            'sales_orders' => 'Sales Order',
            'invoices'     => 'Invoicing & Bill',
            'inventory'    => 'Inventory Management',
        ];

        // 4. Passing semua variabel ke view edit
        return view('user_manuals.edit', compact('userManual', 'parentManuals', 'modules'));
    }

    public function update(Request $request, UserManual $userManual)
    {
        $request->validate([
            'module_name' => 'required|string',
            'type' => 'required|in:modul,sub_modul,feature',
            'parent_id' => 'nullable|exists:user_manuals,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        // Tambahan logika pengaman: Mencegah sebuah panduan memilih dirinya sendiri sebagai parent
        if ($request->filled('parent_id') && $request->parent_id == $userManual->id) {
            return back()->withErrors(['parent_id' => 'Panduan tidak boleh menjadikan dirinya sendiri sebagai Parent/Induk.'])->withInput();
        }

        $userManual->update($request->all());

        return redirect()->route('user-manuals.index')
            ->with('success', 'Struktur Panduan sistem berhasil diperbarui!');
    }

    public function destroy(UserManual $userManual)
    {
        $userManual->delete();
        return redirect()->route('user-manuals.index')
            ->with('success', 'Panduan sistem berhasil dihapus!');
    }
}