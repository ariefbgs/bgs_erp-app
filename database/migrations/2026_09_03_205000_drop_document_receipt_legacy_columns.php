<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Safety gate:
         * every Document Receipt must already have canonical
         * PO Customer, DO pivot and Invoice pivot relationships.
         */
        $invalid = DB::table('document_receipts as dr')
            ->whereNull('dr.po_customer_id')
            ->orWhereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('document_receipt_delivery_orders as p')
                    ->whereColumn('p.document_receipt_id', 'dr.id');
            })
            ->orWhereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('document_receipt_invoices as p')
                    ->whereColumn('p.document_receipt_id', 'dr.id');
            })
            ->count();

        if ($invalid > 0) {
            throw new RuntimeException(
                'RM012F6E blocked: Document Receipt relational data is incomplete.'
            );
        }

        Schema::table('document_receipts', function ($table) {
            $table->dropColumn([
                'delivery_order_id',
                'invoice_number',
                'tax_invoice_number',
            ]);
        });
    }

    public function down(): void
    {
        /*
         * Schema rollback only.
         *
         * Historical shadow values cannot be reconstructed reliably
         * after these columns have been dropped.
         */
        Schema::table('document_receipts', function ($table) {
            $table->unsignedBigInteger('delivery_order_id')
                ->nullable();

            $table->string('invoice_number')
                ->nullable();

            $table->string('tax_invoice_number')
                ->nullable();
        });
    }
};
