<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// Tambahkan baris ini untuk mengimport Paginator bawaan Laravel yang benar:
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sekarang ini akan berjalan dengan aman tanpa error
        Paginator::useBootstrapFive();
    }
}