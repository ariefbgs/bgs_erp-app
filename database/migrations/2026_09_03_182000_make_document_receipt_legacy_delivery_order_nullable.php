<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * RM012F3A
         *
         * Document Receipt is now relational through:
         * - document_receipt_delivery_orders
         * - document_receipt_invoices
         *
         * Legacy delivery_order_id is retained temporarily only as a
         * compatibility shadow field.
         *
         * Remove DELETE CASCADE dependency and allow NULL so runtime
         * legacy writes can be removed safely in the next remediation.
         */

        DB::statement(
            'ALTER TABLE document_receipts
             DROP FOREIGN KEY document_receipts_delivery_order_id_foreign'
        );

        DB::statement(
            'ALTER TABLE document_receipts
             MODIFY delivery_order_id BIGINT UNSIGNED NULL'
        );
    }

    public function down(): void
    {
        /*
         * Rollback requires every row to have legacy delivery_order_id.
         * Do not silently fabricate legacy linkage.
         */

        $nullCount = DB::table('document_receipts')
            ->whereNull('delivery_order_id')
            ->count();

        if ($nullCount > 0) {
            throw new RuntimeException(
                'RM012F3A rollback blocked: document_receipts contains NULL delivery_order_id.'
            );
        }

        DB::statement(
            'ALTER TABLE document_receipts
             MODIFY delivery_order_id BIGINT UNSIGNED NOT NULL'
        );

        DB::statement(
            'ALTER TABLE document_receipts
             ADD CONSTRAINT document_receipts_delivery_order_id_foreign
             FOREIGN KEY (delivery_order_id)
             REFERENCES delivery_orders(id)
             ON DELETE CASCADE'
        );
    }
};
