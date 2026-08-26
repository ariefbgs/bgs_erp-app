<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po_customers', function (Blueprint $table) {
            $table->decimal('remaining_amount', 15, 2)
                ->default(0)
                ->after('total');
        });

        DB::statement(<<<'SQL'
            UPDATE po_customers pc
            SET remaining_amount = GREATEST(
                0,
                pc.total - COALESCE((
                    SELECT SUM(ic.total)
                    FROM invoice_customers ic
                    WHERE ic.po_customer_id = pc.id
                      AND ic.status != 'cancelled'
                ), 0)
            )
        SQL);
    }

    public function down(): void
    {
        Schema::table('po_customers', function (Blueprint $table) {
            $table->dropColumn('remaining_amount');
        });
    }
};
