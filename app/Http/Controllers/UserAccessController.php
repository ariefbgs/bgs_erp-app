<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\UserAccessSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class UserAccessController extends Controller
{
    public function __construct() {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->canManageUserAccess(), 403, 'Anda tidak memiliki akses pengaturan pengguna.');
            return $next($request);
        });
    }
    public function index() {
        $users = User::with('accessSetting')->orderBy('name')->get();
        return view('user_access.index', compact('users'));
    }
    public function update(Request $request, User $user) {
        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'full_features' => ['required', 'boolean'],
            'dashboard_access' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($request, $user, $data) {
            // Serialisasi perubahan role untuk menjaga minimal satu Admin.
            $admins = User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if($target->id === $request->user()->id && $data['role'] !== 'admin', 422,
                'Admin tidak dapat menurunkan role dirinya sendiri.');
            abort_if($target->isAdmin() && $data['role'] !== 'admin' && $admins->count() <= 1,
                422, 'Minimal satu Admin harus tersedia.');
            $target->role = $data['role'];
            $target->save();
            UserAccessSetting::updateOrCreate(['user_id' => $target->id], [
                'full_features' => (bool) $data['full_features'],
                'dashboard_access' => (bool) $data['dashboard_access'],
            ]);
        });
        return back()->with('success', 'Pengaturan akses pengguna berhasil disimpan.');
    }
}
