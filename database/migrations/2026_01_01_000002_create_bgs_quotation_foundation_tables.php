<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number', 50)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->date('date');
            $table->date('valid_until')->nullable();

            $table->enum('status', [
                'draft', 'sent', 'approved', 'rec_po', 'expired',
            ])->default('draft');

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('add_cost', 15, 2)->nullable();
            $table->decimal('dpp', 15, 2)->nullable();
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('pph23_percent', 15, 2)->default(0);
            $table->decimal('pph23_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('payment_terms', 150)->nullable();
            $table->string('delivery_time', 150)->nullable();
            $table->boolean('show_image_on_print')->default(false);
            $table->timestamps();

            $table->foreign(
                'customer_id',
                'quotations_customer_id_foreign'
            )->references('id')->on('customers')->cascadeOnDelete();
        });

        Schema::create('quotation_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id');
            $table->unsignedBigInteger('product_id');
            $table->text('specification')->nullable();
            $table->text('description')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->foreign(
                'quotation_id',
                'quotation_details_quotation_id_foreign'
            )->references('id')->on('quotations')->cascadeOnDelete();

            $table->foreign(
                'product_id',
                'quotation_details_product_id_foreign'
            )->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_details');
        Schema::dropIfExists('quotations');
    }
};