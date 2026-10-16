<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po_customers', function (Blueprint $table) {
            $table->decimal('pph_percent', 5, 2)
                ->default(0)
                ->after('discount_amount');

            $table->decimal('pph_amount', 15, 2)
                ->default(0)
                ->after('pph_percent');
        });
    }

    public function down(): void
    {
        Schema::table('po_customers', function (Blueprint $table) {
            $table->dropColumn([
                'pph_percent',
                'pph_amount',
            ]);
        });
    }
};
