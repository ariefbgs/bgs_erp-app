<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_payment_confirmations', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_kind', 16);
            $table->unsignedBigInteger('invoice_id');
            $table->string('invoice_number', 100);
            $table->decimal('invoice_total', 15, 2);
            $table->date('payment_date');
            $table->unsignedBigInteger('confirmed_by');
            $table->string('reference')->nullable();
            $table->string('previous_status', 32);
            $table->timestamps();
            $table->unique(['invoice_kind', 'invoice_id'], 'invoice_payment_kind_id_unique');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_confirmations');
    }
};
