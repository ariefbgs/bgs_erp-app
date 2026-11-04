<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_customer_details', function (Blueprint $table) {
            $table->unsignedBigInteger('po_customer_detail_id')
                ->nullable()
                ->after('invoice_customer_id');

            $table->index(
                'po_customer_detail_id',
                'icd_pcd_idx'
            );

            $table->foreign(
                'po_customer_detail_id',
                'icd_pcd_fk'
            )
                ->references('id')
                ->on('po_customer_details')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_customer_details', function (Blueprint $table) {
            $table->dropForeign('icd_pcd_fk');
            $table->dropIndex('icd_pcd_idx');
            $table->dropColumn('po_customer_detail_id');
        });
    }
};
