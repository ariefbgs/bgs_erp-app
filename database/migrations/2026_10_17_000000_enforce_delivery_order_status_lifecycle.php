<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $column = DB::selectOne("SHOW FULL COLUMNS FROM delivery_orders WHERE Field = 'status'");
        if (!$column || !in_array($column->Type, [
            "enum('pending','partial','shipped','delivered')",
            "enum('pending','delivered')",
        ], true) || $column->Null !== 'NO' || $column->Default !== 'pending' || $column->Extra !== '') {
            throw new RuntimeException('Unexpected Delivery Order status schema; review before migration.');
        }

        if (DB::table('delivery_orders')->whereNull('status')
            ->orWhereNotIn('status', ['pending', 'delivered'])->exists()) {
            throw new RuntimeException('Legacy Delivery Order statuses require reviewed mapping; no rows converted.');
        }

        if ($column->Type === "enum('pending','delivered')") {
            return;
        }

        $sql = "ALTER TABLE `delivery_orders` MODIFY COLUMN `status` ENUM('pending','delivered')";
        if ($column->Collation) {
            if (!preg_match('/^[A-Za-z0-9_]+$/D', $column->Collation)) {
                throw new RuntimeException('Unexpected Delivery Order status collation.');
            }
            $sql .= ' CHARACTER SET ' . explode('_', $column->Collation)[0] . ' COLLATE ' . $column->Collation;
        }
        $sql .= " NOT NULL DEFAULT 'pending' COMMENT " . DB::connection()->getPdo()->quote($column->Comment);
        DB::statement($sql);
    }

    public function down(): void
    {
        throw new RuntimeException('Restoring retired Delivery Order statuses requires a separately reviewed change.');
    }
};
