<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_code', 50)->unique();
            $table->string('name', 100);
            $table->string('logo')->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('phone1', 20)->nullable();
            $table->string('fax', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 100)->nullable();
            $table->string('npwp', 50)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 100)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('pic_sales_support')->nullable();
            $table->string('ttd_sales_support')->nullable();
            $table->string('pic_do', 100)->nullable();
            $table->string('ttd_do')->nullable();
            $table->string('pic_inv', 100)->nullable();
            $table->string('ttd_inv')->nullable();
            $table->string('ttd_inv2')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 50)->unique();
            $table->string('name', 100);
            $table->string('email', 100)->nullable();
            $table->string('phone', 100)->nullable();
            $table->text('address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('document_address')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('pic_quotation_name', 100)->nullable();
            $table->string('pic_quotation_phone', 100)->nullable();
            $table->string('pic_do_name', 100)->nullable();
            $table->string('pic_do_phone', 100)->nullable();
            $table->string('pic_invoice_name', 100)->nullable();
            $table->string('pic_invoice_phone', 100)->nullable();
            $table->timestamps();

            $table->unique('name', 'name');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique();
            $table->string('product_code2')->nullable();
            $table->text('name');
            $table->text('name2')->nullable();
            $table->text('specification')->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('unit', 20);
            $table->decimal('price', 15, 2);
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code', 50)->unique();
            $table->string('name', 100);
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('companies');
    }
};