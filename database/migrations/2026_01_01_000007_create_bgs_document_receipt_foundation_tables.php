<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50);

            // Historical transitional fields.
            // Subsequent migrations convert these to relational pivots.
            $table->unsignedBigInteger('delivery_order_id');
            $table->string('invoice_number');
            $table->string('tax_invoice_number')->nullable();

            $table->date('receipt_date');
            $table->text('notes')->nullable();
            $table->enum('status', [
                'draft', 'sent', 'delivered',
            ]);
            $table->string('attachment')->nullable();
            $table->timestamps();

            $table->foreign(
                'delivery_order_id',
                'document_receipts_delivery_order_id_foreign'
            )->references('id')->on('delivery_orders')->cascadeOnDelete();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->unsignedBigInteger('delivery_order_id');
            $table->date('received_date');
            $table->string('received_by', 100);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign(
                'delivery_order_id',
                'receipts_delivery_order_id_foreign'
            )->references('id')->on('delivery_orders')->cascadeOnDelete();
        });

        Schema::create('receipts_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipts_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->timestamps();

            $table->foreign(
                'receipts_id',
                'receipts_detail_receipts_id_foreign'
            )->references('id')->on('receipts')->cascadeOnDelete();

            $table->foreign(
                'product_id',
                'receipts_detail_product_id_foreign'
            )->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts_detail');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('document_receipts');
    }
};