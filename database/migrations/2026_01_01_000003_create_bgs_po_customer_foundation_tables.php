<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_customers', function (Blueprint $table) {
            $table->id();

            // External customer PO number; preserve current unique contract.
            $table->string('po_number', 50)->unique();
            $table->unsignedBigInteger('customer_id');

            // One quotation may lead to multiple customer POs.
            $table->unsignedBigInteger('quotation_id')->nullable();

            $table->string('payment_terms', 150)->nullable();
            $table->string('delivery_time', 150)->nullable();
            $table->date('po_date');
            $table->date('delivery_date')->nullable();

            // Historical migration changes processed -> proceed.
            $table->enum('status', [
                'received', 'processed', 'delivered', 'cancelled',
            ])->default('received');

            $table->decimal('subtotal', 15, 2)->default(0);

            $table->enum('invoice_status', [
                'issue yet', 'partial', 'completed', 'paid',
            ])->default('issue yet');

            $table->decimal('tax_percent', 5, 2)->default(11);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->text('notes')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();

            $table->foreign(
                'customer_id',
                'po_customers_customer_id_foreign'
            )->references('id')->on('customers')->cascadeOnDelete();

            $table->foreign(
                'quotation_id',
                'po_customers_quotation_id_foreign'
            )->references('id')->on('quotations')->nullOnDelete();
        });

        Schema::create('po_customer_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('po_customer_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->foreign(
                'po_customer_id',
                'po_customer_details_po_customer_id_foreign'
            )->references('id')->on('po_customers')->cascadeOnDelete();

            $table->foreign(
                'product_id',
                'po_customer_details_product_id_foreign'
            )->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_customer_details');
        Schema::dropIfExists('po_customers');
    }
};