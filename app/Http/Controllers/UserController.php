<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth; // Ditambahkan untuk manajemen session login

class UserController extends Controller
{
    public function __construct()
    {
        // Memastikan hanya Arief dan Cicha yang bisa mengakses controller ini via Gate
        $this->middleware(function ($request, $next) {
            if (Gate::denies('access-dashboard')) {
                return redirect()->route('landing-page')->with('error', 'Anda tidak memiliki akses ke manajemen user.');
            }
            return $next($request);
        });
    }

    // Tampil Daftar User
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    // Simpan User Baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Dipastikan menggunakan Hash::make() murni
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()->with('success', 'User berhasil ditambahkan!');
    }

    // Update / Maintain Password
    public function updatePassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::findOrFail($id);
        
        // 1. Update password baru dengan Enkripsi Hash Bcrypt Laravel
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // 2. JIKA YANG DIUBAH ADALAH PASSWORD DIRI SENDIRI YANG SEDANG LOGIN (Misal Arief mengubah password Arief)
        if ($user->id === Auth::id()) {
            // Perbarui session agar Laravel tidak memakai token password lama
            Auth::login($user);
            return redirect()->back()->with('success', 'Password Anda berhasil diperbarui dan disinkronkan!');
        }

        // 3. JIKA YANG DIUBAH ADALAH PASSWORD USER LAIN (Misal Arief mengubah password Mita)
        return redirect()->back()->with('success', 'Password user ' . $user->name . ' berhasil diperbarui di database!');
    }

    // Hapus User
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Mencegah menghapus diri sendiri saat login
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri yang sedang aktif!');
        }

        $user->delete();
        return redirect()->back()->with('success', 'User berhasil dihapus!');
    }
}