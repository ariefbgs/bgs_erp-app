<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Step 1:
         * Temporarily allow both legacy "processed"
         * and canonical "proceed".
         */
        DB::statement("
            ALTER TABLE po_customers
            MODIFY status ENUM(
                'received',
                'processed',
                'proceed',
                'delivered',
                'cancelled'
            ) NOT NULL DEFAULT 'received'
        ");

        /*
         * Step 2:
         * Convert all legacy business data.
         */
        DB::table('po_customers')
            ->where('status', 'processed')
            ->update([
                'status' => 'proceed',
            ]);

        /*
         * Step 3:
         * Remove legacy value from the schema.
         */
        DB::statement("
            ALTER TABLE po_customers
            MODIFY status ENUM(
                'received',
                'proceed',
                'delivered',
                'cancelled'
            ) NOT NULL DEFAULT 'received'
        ");
    }

    public function down(): void
    {
        /*
         * Safe reverse path:
         * temporarily support both values,
         * map canonical back to legacy,
         * then remove "proceed".
         */
        DB::statement("
            ALTER TABLE po_customers
            MODIFY status ENUM(
                'received',
                'processed',
                'proceed',
                'delivered',
                'cancelled'
            ) NOT NULL DEFAULT 'received'
        ");

        DB::table('po_customers')
            ->where('status', 'proceed')
            ->update([
                'status' => 'processed',
            ]);

        DB::statement("
            ALTER TABLE po_customers
            MODIFY status ENUM(
                'received',
                'processed',
                'delivered',
                'cancelled'
            ) NOT NULL DEFAULT 'received'
        ");
    }
};