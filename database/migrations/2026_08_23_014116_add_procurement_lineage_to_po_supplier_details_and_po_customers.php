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
        |--------------------------------------------------------------------------
        | 1. Add explicit PO Customer Detail lineage
        |--------------------------------------------------------------------------
        */

        Schema::table('po_supplier_details', function (Blueprint $table) {
            $table->unsignedBigInteger('po_customer_detail_id')
                ->nullable()
                ->after('po_supplier_id');

            $table->index(
                'po_customer_detail_id',
                'psd_po_customer_detail_idx'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | 2. Backfill existing PO Supplier Detail lineage
        |--------------------------------------------------------------------------
        |
        | Feasibility audit R1E-02I3A proved all existing rows have
        | exactly one matching PO Customer Detail by:
        |
        |   po_supplier.po_customer_id
        |   + product_id
        |
        */

        $rows = DB::table('po_supplier_details as psd')
            ->join(
                'po_suppliers as ps',
                'ps.id',
                '=',
                'psd.po_supplier_id'
            )
            ->select([
                'psd.id',
                'ps.po_customer_id',
                'psd.product_id',
            ])
            ->orderBy('psd.id')
            ->get();

        foreach ($rows as $row) {
            $candidates = DB::table('po_customer_details')
                ->where(
                    'po_customer_id',
                    $row->po_customer_id
                )
                ->where(
                    'product_id',
                    $row->product_id
                )
                ->pluck('id');

            if ($candidates->count() !== 1) {
                throw new \RuntimeException(
                    'Cannot safely backfill po_supplier_details.id=' .
                    $row->id .
                    '. Expected exactly one matching PO Customer Detail, found ' .
                    $candidates->count() .
                    '.'
                );
            }

            DB::table('po_supplier_details')
                ->where('id', $row->id)
                ->update([
                    'po_customer_detail_id' => $candidates->first(),
                ]);
        }

        $nullCount = DB::table('po_supplier_details')
            ->whereNull('po_customer_detail_id')
            ->count();

        if ($nullCount > 0) {
            throw new \RuntimeException(
                'Backfill incomplete: ' .
                $nullCount .
                ' PO Supplier Detail rows remain without lineage.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Enforce NOT NULL + FK
        |--------------------------------------------------------------------------
        */

        DB::statement(
            'ALTER TABLE po_supplier_details
             MODIFY po_customer_detail_id BIGINT UNSIGNED NOT NULL'
        );

        Schema::table('po_supplier_details', function (Blueprint $table) {
            $table->foreign('po_customer_detail_id', 'psd_pcd_fk')
                ->references('id')
                ->on('po_customer_details')
                ->restrictOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | 4. Add procurement_status to PO Customer
        |--------------------------------------------------------------------------
        |
        | Values:
        | pending
        | partial
        | fully_procured
        |
        */

        Schema::table('po_customers', function (Blueprint $table) {
            $table->string('procurement_status', 20)
                ->default('pending')
                ->after('status');

            $table->index(
                'procurement_status',
                'po_customers_procurement_status_idx'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | 5. Backfill procurement status
        |--------------------------------------------------------------------------
        */

        $poCustomers = DB::table('po_customers')
            ->select('id', 'status')
            ->orderBy('id')
            ->get();

        foreach ($poCustomers as $poCustomer) {
            $details = DB::table('po_customer_details')
                ->where('po_customer_id', $poCustomer->id)
                ->select('id', 'quantity')
                ->get();

            if ($details->isEmpty()) {
                DB::table('po_customers')
                    ->where('id', $poCustomer->id)
                    ->update([
                        'procurement_status' => 'pending',
                    ]);

                continue;
            }

            $totalOrdered = 0;
            $totalAllocated = 0;
            $allFullyAllocated = true;

            foreach ($details as $detail) {
                $orderedQty = (float) $detail->quantity;

                $allocatedQty = (float) DB::table('po_supplier_details as psd')
                    ->join(
                        'po_suppliers as ps',
                        'ps.id',
                        '=',
                        'psd.po_supplier_id'
                    )
                    ->where(
                        'psd.po_customer_detail_id',
                        $detail->id
                    )
                    ->where(
                        'ps.status',
                        '!=',
                        'cancelled'
                    )
                    ->sum('psd.quantity');

                $totalOrdered += $orderedQty;
                $totalAllocated += $allocatedQty;

                if ($allocatedQty < $orderedQty) {
                    $allFullyAllocated = false;
                }
            }

            if ($totalAllocated <= 0) {
                $procurementStatus = 'pending';
            } elseif ($allFullyAllocated) {
                $procurementStatus = 'fully_procured';
            } else {
                $procurementStatus = 'partial';
            }

            DB::table('po_customers')
                ->where('id', $poCustomer->id)
                ->update([
                    'procurement_status' => $procurementStatus,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('po_supplier_details', function (Blueprint $table) {
            $table->dropForeign('psd_pcd_fk');
            $table->dropIndex('psd_po_customer_detail_idx');
            $table->dropColumn('po_customer_detail_id');
        });

        Schema::table('po_customers', function (Blueprint $table) {
            $table->dropIndex('po_customers_procurement_status_idx');
            $table->dropColumn('procurement_status');
        });
    }
};