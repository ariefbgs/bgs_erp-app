<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE document_receipts
            MODIFY invoice_number VARCHAR(255) NULL
        ");
    }

    public function down(): void
    {
        $nullCount = DB::table('document_receipts')
            ->whereNull('invoice_number')
            ->count();

        if ($nullCount > 0) {
            throw new RuntimeException(
                'Rollback blocked: document_receipts contains NULL invoice_number.'
            );
        }

        DB::statement("
            ALTER TABLE document_receipts
            MODIFY invoice_number VARCHAR(255) NOT NULL
        ");
    }
};
