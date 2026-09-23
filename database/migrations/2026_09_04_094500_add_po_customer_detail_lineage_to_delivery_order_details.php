<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_order_details', function (Blueprint $table) {
            $table->unsignedBigInteger('po_customer_detail_id')
                ->nullable()
                ->after('delivery_order_id');

            $table->index(
                'po_customer_detail_id',
                'dod_po_customer_detail_idx'
            );
        });

        DB::statement("
            UPDATE delivery_order_details dod
            JOIN delivery_orders d
              ON d.id = dod.delivery_order_id
            JOIN (
                SELECT
                    po_customer_id,
                    product_id,
                    MIN(id) AS po_customer_detail_id
                FROM po_customer_details
                GROUP BY po_customer_id, product_id
                HAVING COUNT(*) = 1
            ) pcd
              ON pcd.po_customer_id = d.po_customer_id
             AND pcd.product_id = dod.product_id
            SET dod.po_customer_detail_id = pcd.po_customer_detail_id
            WHERE dod.po_customer_detail_id IS NULL
        ");

        Schema::table('delivery_order_details', function (Blueprint $table) {
            $table->foreign(
                'po_customer_detail_id',
                'dod_pcd_fk'
            )->references('id')
              ->on('po_customer_details');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_order_details', function (Blueprint $table) {
            $table->dropForeign('dod_pcd_fk');
            $table->dropIndex('dod_po_customer_detail_idx');
            $table->dropColumn('po_customer_detail_id');
        });
    }
};
