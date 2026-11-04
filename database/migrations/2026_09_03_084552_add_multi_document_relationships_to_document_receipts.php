<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Header ownership:
         * Document Receipt belongs to PO Customer.
         *
         * Legacy delivery_order_id / invoice_number /
         * tax_invoice_number are intentionally preserved
         * during transition.
         */
        Schema::table('document_receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('po_customer_id')
                ->nullable()
                ->after('receipt_number');

            $table->index(
                'po_customer_id',
                'document_receipts_po_customer_id_index'
            );
        });

        Schema::create(
            'document_receipt_delivery_orders',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('document_receipt_id');
                $table->unsignedBigInteger('delivery_order_id');

                $table->timestamps();

                $table->unique(
                    ['document_receipt_id', 'delivery_order_id'],
                    'dr_do_unique'
                );

                $table->foreign('document_receipt_id', 'dr_do_receipt_fk')
                    ->references('id')
                    ->on('document_receipts')
                    ->cascadeOnDelete();

                /*
                 * IMPORTANT:
                 * deleting a Delivery Order must NOT delete
                 * the Document Receipt itself.
                 */
                $table->foreign('delivery_order_id', 'dr_do_delivery_fk')
                    ->references('id')
                    ->on('delivery_orders')
                    ->restrictOnDelete();
            }
        );

        Schema::create(
            'document_receipt_invoices',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('document_receipt_id');
                $table->unsignedBigInteger('invoice_customer_id');

                $table->timestamps();

                $table->unique(
                    ['document_receipt_id', 'invoice_customer_id'],
                    'dr_invoice_unique'
                );

                $table->foreign('document_receipt_id', 'dr_invoice_receipt_fk')
                    ->references('id')
                    ->on('document_receipts')
                    ->cascadeOnDelete();

                $table->foreign('invoice_customer_id', 'dr_invoice_invoice_fk')
                    ->references('id')
                    ->on('invoice_customers')
                    ->restrictOnDelete();
            }
        );

        /*
         * Migrate legacy DR records.
         */
        $receipts = DB::table('document_receipts')
            ->orderBy('id')
            ->get();

        foreach ($receipts as $receipt) {

            /*
             * 1. Legacy DO -> PO Customer + pivot DO.
             */
            if (!empty($receipt->delivery_order_id)) {

                $do = DB::table('delivery_orders')
                    ->where('id', $receipt->delivery_order_id)
                    ->first();

                if ($do) {
                    DB::table('document_receipts')
                        ->where('id', $receipt->id)
                        ->update([
                            'po_customer_id' => $do->po_customer_id,
                        ]);

                    DB::table('document_receipt_delivery_orders')
                        ->insertOrIgnore([
                            'document_receipt_id' => $receipt->id,
                            'delivery_order_id' => $do->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            /*
             * 2. Legacy comma-separated invoice numbers -> pivot IDs.
             */
            if (!empty($receipt->invoice_number)) {

                $numbers = array_values(
                    array_filter(
                        array_map(
                            'trim',
                            explode(',', $receipt->invoice_number)
                        )
                    )
                );

                foreach ($numbers as $number) {

                    $invoice = DB::table('invoice_customers')
                        ->where('invoice_number', $number)
                        ->first();

                    if (!$invoice) {
                        continue;
                    }

                    /*
                     * Only migrate invoice belonging to the same
                     * PO Customer as the DR.
                     */
                    $drPo = DB::table('document_receipts')
                        ->where('id', $receipt->id)
                        ->value('po_customer_id');

                    if (
                        $drPo &&
                        (int) $invoice->po_customer_id === (int) $drPo
                    ) {
                        DB::table('document_receipt_invoices')
                            ->insertOrIgnore([
                                'document_receipt_id' => $receipt->id,
                                'invoice_customer_id' => $invoice->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_receipt_invoices');
        Schema::dropIfExists('document_receipt_delivery_orders');

        Schema::table('document_receipts', function (Blueprint $table) {
            $table->dropIndex('document_receipts_po_customer_id_index');
            $table->dropColumn('po_customer_id');
        });
    }
};
