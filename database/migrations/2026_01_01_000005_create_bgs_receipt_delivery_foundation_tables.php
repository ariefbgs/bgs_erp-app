<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->unsignedBigInteger('po_supplier_id');
            $table->date('receipt_date');
            $table->enum('status', [
                'partial', 'completed', 'cancelled',
            ])->default('partial');
            $table->text('notes')->nullable();
            $table->string('received_by')->nullable();
            $table->timestamps();

            $table->foreign(
                'po_supplier_id',
                'goods_receipts_po_supplier_id_foreign'
            )->references('id')->on('po_suppliers');
        });

        Schema::create('goods_receipt_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_receipt_id');
            $table->unsignedBigInteger('po_supplier_detail_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity_received');
            $table->integer('quantity_remaining')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign(
                'goods_receipt_id',
                'goods_receipt_details_goods_receipt_id_foreign'
            )->references('id')->on('goods_receipts')->cascadeOnDelete();

            $table->foreign(
                'po_supplier_detail_id',
                'goods_receipt_details_po_supplier_detail_id_foreign'
            )->references('id')->on('po_supplier_details');

            $table->foreign(
                'product_id',
                'goods_receipt_details_product_id_foreign'
            )->references('id')->on('products');
        });

        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('do_number', 50)->unique();
            $table->unsignedBigInteger('po_customer_id');
            $table->date('delivery_date');
            $table->string('shipping_address')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->string('tax_invoice_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable();

            // Legacy-compatible status; normalize separately
            // to the approved Pending / Delivered lifecycle.
            $table->enum('status', [
                'pending', 'partial', 'shipped', 'delivered',
            ])->default('pending');

            // Required starting state for historical
            // delivery_by normalization migration.
            $table->enum('delivery_by', [
                'Wisnu', 'JNE', 'Courier Online',
            ])->nullable();

            $table->timestamps();

            $table->foreign(
                'po_customer_id',
                'delivery_orders_po_customer_id_foreign'
            )->references('id')->on('po_customers')->cascadeOnDelete();
        });

        Schema::create('delivery_order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->timestamps();

            // Historical migration adds po_customer_detail_id
            // with its index and foreign key.

            $table->foreign(
                'delivery_order_id',
                'delivery_order_details_delivery_order_id_foreign'
            )->references('id')->on('delivery_orders')->cascadeOnDelete();

            $table->foreign(
                'product_id',
                'delivery_order_details_product_id_foreign'
            )->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_details');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('goods_receipt_details');
        Schema::dropIfExists('goods_receipts');
    }
};