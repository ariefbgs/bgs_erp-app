<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('invoice_customers', function (Blueprint $table) {
        // Menambahkan field setelah tax_invoice_number
        $table->string('tax_invoice_attachment')->nullable()->after('tax_invoice_number');
    });
}

public function down(): void
{
    Schema::table('invoice_customers', function (Blueprint $table) {
        $table->dropColumn('tax_invoice_attachment');
    });
}
};
