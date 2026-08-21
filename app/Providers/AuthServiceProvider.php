<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Aturan autentikasi / otorisasi aplikasi.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Pengecekan email akses dashboard
        Gate::define('access-dashboard', function ($user) {
            $allowedEmails = ['chicha@gmail.com', 'arief@gmail.com'];
            return in_array($user->email, $allowedEmails);
        });
    }
}