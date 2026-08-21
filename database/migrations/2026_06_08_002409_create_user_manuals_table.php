<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_manuals', function (Blueprint $table) {
            $table->id();
            $table->string('module_name')->unique(); // Contoh: expenses, quotations, invoices
            $table->string('title'); // Judul panduan
            $table->longText('content'); // Isi panduan (bisa menampung HTML/teks panjang)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_manuals');
    }
};
