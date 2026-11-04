<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function hasRestrictedFullAccess(): bool
    {
        return in_array((int) $this->getKey(), config('bgs_access.restricted_full_user_ids', []), true);
    }

    public function canAccessBackup(): bool
    {
        return !$this->hasRestrictedFullAccess() && $this->canUseAdminFeatures();
    }

    public function canManageUserAccess(): bool
    {
        return $this->hasRestrictedFullAccess() || $this->isAdmin();
    }

    public function canUseAdminFeatures(): bool
    {
        return $this->hasRestrictedFullAccess() || $this->isAdmin() || (bool) ($this->accessSetting?->full_features ?? false);
    }

    public function accessSetting()
    {
        return $this->hasOne(UserAccessSetting::class);
    }

    public function canAccessDashboard(): bool
    {
        if ($this->hasRestrictedFullAccess()) {
            return false;
        }

        return (bool) ($this->accessSetting?->dashboard_access ?? $this->isAdmin());
    }

    public function loginDestination(): string
    {
        return $this->canAccessDashboard() ? route('dashboard') : route('landing-page');
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isManager()
    {
        return $this->role === 'manager';
    }

    public function isStaff()
    {
        return $this->role === 'staff';
    }

    public function canDelete()
    {
        return $this->canUseAdminFeatures() || $this->isManager();
    }

    /**
     * Deployment/RBAC compatibility layer.
     *
     * Existing ERP roles remain stored in users.role.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->hasRestrictedFullAccess()) {
            return !str_starts_with($permission, 'deployment_')
                && !str_starts_with($permission, 'backup_')
                && !in_array($permission, ['access-dashboard', 'dashboard_access'], true);
        }

        if ($this->accessSetting?->full_features) {
            return true;
        }

        if (!$this->role) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::table('role_permissions')
            ->join(
                'permissions',
                'permissions.id',
                '=',
                'role_permissions.permission_id'
            )
            ->where('role_permissions.role', $this->role)
            ->where('permissions.name', $permission)
            ->exists();
    }
}
