<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_suppliers', function (Blueprint $table) {
            $table->id();

            $table->string('po_supplier_number', 50)->unique();
            $table->unsignedBigInteger('po_customer_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('supplier_id');
            $table->date('po_date');

            $table->enum('status', [
                'draft', 'sent', 'confirmed', 'received', 'cancelled',
            ])->default('draft');

            $table->enum('receipt_status', [
                'pending', 'partial', 'completed', 'cancelled',
            ])->nullable()->default('pending');

            $table->text('notes')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);

            // Discount columns are added by a historical migration.

            $table->decimal('tax_percent', 5, 2)->default(11);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign(
                'po_customer_id',
                'po_suppliers_po_customer_id_foreign'
            )->references('id')->on('po_customers')->cascadeOnDelete();

            $table->foreign(
                'customer_id',
                'po_suppliers_customer_id_foreign'
            )->references('id')->on('customers');

            $table->foreign(
                'supplier_id',
                'po_suppliers_supplier_id_foreign'
            )->references('id')->on('suppliers');
        });

        Schema::create('po_supplier_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('po_supplier_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            // Historical migration adds po_customer_detail_id,
            // backfills it, and enforces NOT NULL + FK.

            $table->foreign(
                'po_supplier_id',
                'po_supplier_details_po_supplier_id_foreign'
            )->references('id')->on('po_suppliers')->cascadeOnDelete();

            $table->foreign(
                'product_id',
                'po_supplier_details_product_id_foreign'
            )->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_supplier_details');
        Schema::dropIfExists('po_suppliers');
    }
};