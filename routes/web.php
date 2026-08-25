<?php

use App\Http\Controllers\DeploymentController;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PoCustomerController;
use App\Http\Controllers\PoSupplierController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\DocumentReceiptController;
use App\Http\Controllers\InvoiceCustomerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserManualController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File; // Tambahkan ini untuk File::deleteDirectory

// 1. Rute Publik / Tamu
Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();

// 2. Rute Terproteksi Login
Route::middleware(['auth'])->group(function () {

    // ==========================================
    // STORAGE LINK (PERBAIKAN)
    // ==========================================
    Route::get('/reset-storage', function () {
        $shortcutPath = public_path('storage');
        
        // Jika path adalah symlink, hapus dengan unlink
        if (is_link($shortcutPath)) {
            unlink($shortcutPath);
        } 
        // Jika path adalah direktori (bukan symlink), hapus isinya terlebih dahulu
        elseif (is_dir($shortcutPath)) {
            File::deleteDirectory($shortcutPath);
        }
        
        // Buat symlink baru
        Artisan::call('storage:link');
        return 'Symlink storage berhasil direset!';
    });

    // Route untuk membuat storage link (jika belum ada)
    Route::get('/link-storage', function () {
        $shortcutPath = public_path('storage');
        if (is_link($shortcutPath) || is_dir($shortcutPath)) {
            return 'Folder public/storage sudah ada. Silakan gunakan /reset-storage untuk mereset.';
        }
        Artisan::call('storage:link');
        return 'Storage link telah berhasil dibuat!';
    });

    // ==========================================
    // DASHBOARD
    // ==========================================
    Route::get('/dashboard', function() {
        if (Gate::allows('access-dashboard')) {
            return app(DashboardController::class)->index();
        }
        return redirect()->route('landing-page');
    })->name('dashboard');

    Route::get('/welcome', function () {
        return view('landing');
    })->name('landing-page');

    // ==========================================
    // MODUL ERP UTAMA
    // ==========================================
    
    // Master Data
    Route::resource('customers', CustomerController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('products', ProductController::class);
    
    // Company Resource
    Route::resource('companies', CompanyController::class);
    
    // Route tambahan untuk Customer
    Route::get('customers/{id}/print', [CustomerController::class, 'print'])->name('customers.print');
    
    // Quotation
    Route::get(
    '/quotations/copy-source/{source_quotation}',
    [QuotationController::class, 'copySource']
)->name('quotations.copy-source');

Route::resource('quotations', QuotationController::class);
    Route::post('/quotations/update-price', [QuotationController::class, 'updateProductPrice'])->name('quotations.update-price');
    Route::get('/quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');
    Route::post('/quotations/{quotation}/approve', [QuotationController::class, 'approve'])->name('quotations.approve');

    // PO Customer
    Route::get('po-customers/{id}/view-image', [PoCustomerController::class, 'viewImage'])->name('po-customers.view-image');
    Route::resource('po-customers', PoCustomerController::class);
    Route::get('/po-customers/get-quotation/{id}', [PoCustomerController::class, 'getQuotation'])->name('po-customers.get-quotation');

    // PO Supplier
    Route::get('po-suppliers/get-po-customer-list', [PoSupplierController::class, 'getPoCustomerList'])->name('po-suppliers.get-po-customer-list');
    Route::get('po-suppliers/get-po-customer-details/{id}', [PoSupplierController::class, 'getPoCustomerDetails'])->name('po-suppliers.get-po-customer-details');
    Route::post('po-suppliers/update-status/{id}', [PoSupplierController::class, 'updateStatus'])->name('po-suppliers.update-status');
    Route::get('po-suppliers/{poSupplier}/print', [PoSupplierController::class, 'print'])->name('po-suppliers.print');
    Route::resource('po-suppliers', PoSupplierController::class);

    // Goods Receipt
    Route::resource('goods-receipts', GoodsReceiptController::class);
    Route::get('/goods-receipts/get-po-supplier-details/{id}', [GoodsReceiptController::class, 'getPoSupplierDetails'])->name('goods-receipts.get-po-supplier-details');
    Route::post('goods-receipts/{id}/cancel', [GoodsReceiptController::class, 'cancel'])->name('goods-receipts.cancel');

    // Delivery Order
    Route::resource('delivery-orders', DeliveryOrderController::class);
    Route::get('delivery-orders/get-po-customer-details/{id}', [DeliveryOrderController::class, 'getPoCustomerDetails'])->name('delivery-orders.get-po-customer-details');
    Route::get('delivery-orders/get-invoices-by-po/{po_customer_id}', [DeliveryOrderController::class, 'getInvoicesByPo'])->name('delivery-orders.get-invoices-by-po');
    Route::get('delivery-orders/{id}/print', [DeliveryOrderController::class, 'print'])->name('delivery-orders.print');
    Route::get('delivery-orders/{id}/print-receipt', [DeliveryOrderController::class, 'printReceipt'])->name('delivery-orders.print-receipt');

    // Document Receipt
    Route::resource('document-receipts', DocumentReceiptController::class);
    Route::get('document-receipts/get-do-details/{id}', [DocumentReceiptController::class, 'getDoDetails'])->name('document-receipts.get-do-details');
    Route::get('document-receipts/get-invoices-by-po/{po_customer_id}', [DocumentReceiptController::class, 'getInvoicesByPo'])->name('document-receipts.get-invoices-by-po');
    Route::get('document-receipts/{id}/print', [DocumentReceiptController::class, 'print'])->name('document-receipts.print');

    // Invoice Customer
    Route::resource('invoice-customers', InvoiceCustomerController::class);
    Route::get('invoice-customers/get-po-customer-details/{id}', [InvoiceCustomerController::class, 'getPoCustomerDetails'])->name('invoice-customers.get-po-customer-details');
    Route::get('invoice-customers/{id}/edit', [InvoiceCustomerController::class, 'edit'])->name('invoice-customers.edit');
    Route::put('invoice-customers/{id}', [InvoiceCustomerController::class, 'update'])->name('invoice-customers.update');
    Route::delete('invoice-customers/{id}', [InvoiceCustomerController::class, 'destroy'])->name('invoice-customers.destroy');
    Route::patch('/invoice-customers/{id}/cancel', [InvoiceCustomerController::class, 'cancel'])->name('invoice-customers.cancel');
    Route::get('invoice-customers/{id}/print', [InvoiceCustomerController::class, 'print'])->name('invoice-customers.print');
    Route::get('invoice-customers/{id}/print_invoice', [InvoiceCustomerController::class, 'printInvoice'])->name('invoice-customers.print_invoice');

    // ==========================================
    // LAPORAN
    // ==========================================
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/purchasing/pdf', [ReportController::class, 'purchasingPdf'])->name('purchasing.pdf');
        Route::get('/purchasing/excel', [ReportController::class, 'purchasingExcel'])->name('purchasing.excel');
        Route::get('/sales/pdf', [ReportController::class, 'salesPdf'])->name('sales.pdf');
        Route::get('/sales/excel', [ReportController::class, 'salesExcel'])->name('sales.excel');
        Route::get('/payment-supplier/pdf', [ReportController::class, 'paymentSupplierPdf'])->name('payment-supplier.pdf');
        Route::get('/payment-supplier/excel', [ReportController::class, 'paymentSupplierExcel'])->name('payment-supplier.excel');
        Route::get('/payment-customer/pdf', [ReportController::class, 'paymentCustomerPdf'])->name('payment-customer.pdf');
        Route::get('/payment-customer/excel', [ReportController::class, 'paymentCustomerExcel'])->name('payment-customer.excel');
        Route::get('/delivery/pdf', [ReportController::class, 'deliveryPdf'])->name('delivery.pdf');
        Route::get('/delivery/excel', [ReportController::class, 'deliveryExcel'])->name('delivery.excel');
    });
    
    // ==========================================
    // BACKUP & RESTORE
    // ==========================================
    Route::prefix('backup')->name('backup.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::post('/create', [BackupController::class, 'backup'])->name('create');
        Route::get('/download/{filename}', [BackupController::class, 'download'])->name('download');
        Route::get('/restore', [BackupController::class, 'restoreForm'])->name('restore.form');
        Route::post('/restore', [BackupController::class, 'restore'])->name('restore');
    });

    Route::get('expenses/{expense}/view-image', [App\Http\Controllers\ExpenseController::class, 'viewImage'])->name('expenses.view-image');
    Route::resource('expense', ExpenseController::class);

    // ==========================================
    // USER MANAGEMENT
    // ==========================================
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{id}/password', [UserController::class, 'updatePassword'])->name('users.password.update');
    Route::delete('users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

    // ==========================================
    // USER MANUAL
    // ==========================================
    Route::resource('user-manuals', UserManualController::class);

    // ==========================================
    // VIEW TAX INVOICE
    // ==========================================
    Route::get('/view-tax-invoice/{id}', function ($id) {
        $invoice = \App\Models\InvoiceCustomer::findOrFail($id);
        if (!$invoice->tax_invoice_attachment || !Storage::disk('public')->exists($invoice->tax_invoice_attachment)) {
            abort(404, 'Berkas tidak ditemukan di server.');
        }
        return Storage::disk('public')->response($invoice->tax_invoice_attachment);
    })->name('tax-invoice.view');

    // ==========================================
    // VIEW PRODUCT IMAGE
    // ==========================================
    Route::get('products/{id}/image-view', [ProductController::class, 'viewImage'])->name('products.image.view');
    // Rute ini HANYA untuk sementara waktu!
    //Route::get('/storage-link', function () {
    //    Artisan::call('storage:link');
    //    return 'Storage linked successfully. Please delete this route now.';
    //});

}); // Penutup Middleware Auth Global

/*
|--------------------------------------------------------------------------
| Deployment Foundation
|--------------------------------------------------------------------------
|
| Controlled deployment HTTP entry points.
| Execution logic must remain outside the HTTP controller.
|
*/

Route::middleware('auth')
    ->prefix('deployment')
    ->name('deployment.')
    ->group(function () {
        Route::get('/', [DeploymentController::class, 'index'])
            ->middleware('permission:deployment_view')
            ->name('index');

        Route::post('/upload', [DeploymentController::class, 'upload'])
            ->middleware('permission:deployment_upload')
            ->name('upload');

        Route::post('/install', [DeploymentController::class, 'install'])
            ->middleware('permission:deployment_install')
            ->name('install');

        Route::post('/rollback', [DeploymentController::class, 'rollback'])
            ->middleware('permission:deployment_rollback')
            ->name('rollback');

        Route::get('/history', [DeploymentController::class, 'history'])
            ->middleware('permission:deployment_view')
            ->name('history');
    });
