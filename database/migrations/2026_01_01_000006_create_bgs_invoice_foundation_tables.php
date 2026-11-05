<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_customers', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->string('type', 50)->nullable();
            $table->unsignedBigInteger('po_customer_id');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('tax_invoice_number', 50)->nullable();
            $table->string('status', 50)->nullable();

            $table->enum('payment_status', [
                'sent', 'paid', 'cancelled',
            ])->default('sent');

            // Retain historical schema spelling for now.
            $table->enum('invoice_status', [
                'issue yet', 'partial', 'cpmpleted',
            ])->default('issue yet');

            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('pph23_percent', 5, 2)->default(0);
            $table->decimal('pph23_amount', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->decimal('dp_amount', 15, 2)->default(0);
            $table->decimal('add_cost', 15, 2)->default(0);
            $table->decimal('dp_percent', 5, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->string('payment_terms', 150)->nullable();
            $table->string('delivery_time', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('parent_invoice_id')->nullable();

            // Match existing schema: indexes, not FK constraints.
            $table->index(
                'po_customer_id',
                'invoice_customers_po_customer_id_foreign'
            );
            $table->index(
                'parent_invoice_id',
                'invoice_customers_parent_invoice_id_foreign'
            );
        });

        Schema::create('invoice_customer_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_customer_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            // Historical migration adds po_customer_detail_id.
            // Existing schema has ordinary indexes for these IDs.
            $table->index(
                'invoice_customer_id',
                'invoice_customer_details_invoice_customer_id_foreign'
            );
            $table->index(
                'product_id',
                'invoice_customer_details_product_id_foreign'
            );
        });

        Schema::create('invoice_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->unsignedBigInteger('po_supplier_id');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->enum('status', [
                'unpaid', 'paid', 'overdue',
            ])->default('unpaid');

            $table->decimal('amount', 15, 2);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->timestamps();

            $table->foreign(
                'po_supplier_id',
                'invoice_suppliers_po_supplier_id_foreign'
            )->references('id')->on('po_suppliers')->cascadeOnDelete();
        });

        Schema::create('invoice_supplier_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_supplier_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->foreign(
                'invoice_supplier_id',
                'invoice_supplier_details_invoice_supplier_id_foreign'
            )->references('id')->on('invoice_suppliers')->cascadeOnDelete();

            $table->foreign(
                'product_id',
                'invoice_supplier_details_product_id_foreign'
            )->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_supplier_details');
        Schema::dropIfExists('invoice_suppliers');
        Schema::dropIfExists('invoice_customer_details');
        Schema::dropIfExists('invoice_customers');
    }
};