<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_TYPE = "enum('Wisnu','JNE','Courier Online')";
    private const TRANSITION_TYPE = "enum('Wisnu','JNE','Courier Online','Kurir Online (JNE/GOJEK/LALAMOVE)','Kurir Sendiri')";

    private const NEW_TYPE = "enum('Kurir Online (JNE/GOJEK/LALAMOVE)','Kurir Sendiri')";

    public function up(): void
    {
        $column = $this->column();
        if (!in_array($column->Type, [self::OLD_TYPE, self::TRANSITION_TYPE, self::NEW_TYPE], true)) {
            throw new RuntimeException('Unexpected delivery_by type; no schema change applied.');
        }
        $allowed = ['Wisnu', 'JNE', 'Courier Online', 'Kurir Online (JNE/GOJEK/LALAMOVE)', 'Kurir Sendiri'];
        if (DB::table('delivery_orders')->whereNotNull('delivery_by')->whereNotIn('delivery_by', $allowed)->exists()) {
            throw new RuntimeException('Unexpected delivery_by data; inspect before migration.');
        }
        if ($column->Type === self::NEW_TYPE) { return; }
        if ($column->Type === self::OLD_TYPE) {
            $this->alter($column, self::TRANSITION_TYPE);
        }
        DB::transaction(function () {
            DB::table('delivery_orders')->where('delivery_by', 'Wisnu')->update(['delivery_by' => 'Kurir Sendiri']);
            DB::table('delivery_orders')->whereIn('delivery_by', ['JNE', 'Courier Online'])->update([
                'delivery_by' => 'Kurir Online (JNE/GOJEK/LALAMOVE)',
            ]);
        });
        // Preserve nullability/collation/comment; translate the default with the same mapping.
        $finalColumn = $this->column();
        $finalColumn->Default = match ($finalColumn->Default) {
            'Wisnu' => 'Kurir Sendiri',
            'JNE', 'Courier Online' => 'Kurir Online (JNE/GOJEK/LALAMOVE)',
            default => $finalColumn->Default,
        };
        $this->alter($finalColumn, self::NEW_TYPE);
    }

    public function down(): void
    {
        throw new RuntimeException('Delivery choices were normalized many-to-one. Restore using the before snapshot through a reviewed recovery; automatic reverse mapping would lose history.');
    }

    private function column(): object
    {
        $column = DB::selectOne("SHOW FULL COLUMNS FROM delivery_orders WHERE Field = 'delivery_by'");
        if (!$column || $column->Extra !== '') {
            throw new RuntimeException('Unexpected delivery_by metadata; no schema change applied.');
        }
        return $column;
    }

    private function alter(object $column, string $type): void
    {
        $quote = fn (string $value): string => DB::connection()->getPdo()->quote($value);
        $sql = 'ALTER TABLE `delivery_orders` MODIFY COLUMN `delivery_by` ' . $type;
        if ($column->Collation) {
            if (!preg_match('/^[A-Za-z0-9_]+$/D', $column->Collation)) {
                throw new RuntimeException('Unexpected collation.');
            }
            $charset = explode('_', $column->Collation)[0];
            $sql .= ' CHARACTER SET ' . $charset . ' COLLATE ' . $column->Collation;
        }
        $sql .= $column->Null === 'YES' ? ' NULL' : ' NOT NULL';
        if ($column->Default !== null) {
            $sql .= ' DEFAULT ' . $quote((string) $column->Default);
        } elseif ($column->Null === 'YES') {
            $sql .= ' DEFAULT NULL';
        }
        $sql .= ' COMMENT ' . $quote((string) $column->Comment);
        DB::statement($sql);
    }
};
