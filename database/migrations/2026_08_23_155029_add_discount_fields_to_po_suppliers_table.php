<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po_suppliers', function (Blueprint $table) {

            if (!Schema::hasColumn(
                'po_suppliers',
                'discount_percent'
            )) {
                $table
                    ->decimal(
                        'discount_percent',
                        8,
                        2
                    )
                    ->default(0)
                    ->after('subtotal');
            }

            if (!Schema::hasColumn(
                'po_suppliers',
                'discount_amount'
            )) {
                $table
                    ->decimal(
                        'discount_amount',
                        18,
                        2
                    )
                    ->default(0)
                    ->after('discount_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('po_suppliers', function (Blueprint $table) {

            if (Schema::hasColumn(
                'po_suppliers',
                'discount_amount'
            )) {
                $table->dropColumn(
                    'discount_amount'
                );
            }

            if (Schema::hasColumn(
                'po_suppliers',
                'discount_percent'
            )) {
                $table->dropColumn(
                    'discount_percent'
                );
            }
        });
    }
};