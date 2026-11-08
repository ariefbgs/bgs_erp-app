<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/** G4.98 phase A: Quotation -> Customer PO -> Supplier PO, including their details. */
class ImportLegacyTransactions extends Command
{
    protected $signature = 'legacy:load-orders {snapshot : Approved private JSON snapshot} {--apply : Test-only commit (SQLite memory)}';
    protected $description = 'Safe transactional order lineage importer. Production imports are disabled.';

    private const SOURCE = 'smartlo1_erp_app';
    private const TABLES = [
        'quotations' => ['customer_id' => 'customers'],
        'quotation_details' => ['quotation_id' => 'quotations', 'product_id' => 'products'],
        'po_customers' => ['customer_id' => 'customers', 'quotation_id' => 'quotations'],
        'po_customer_details' => ['po_customer_id' => 'po_customers', 'product_id' => 'products'],
        'po_suppliers' => ['po_customer_id' => 'po_customers', 'customer_id' => 'customers', 'supplier_id' => 'suppliers'],
        'po_supplier_details' => ['po_supplier_id' => 'po_suppliers', 'product_id' => 'products'],
    ];
    private const NATURAL = [
        'quotations' => 'quotation_number',
        'po_customers' => 'po_number',
        'po_suppliers' => 'po_supplier_number',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $connection = DB::connection();
        $testingSqlite = app()->environment('testing')
            && $connection->getDriverName() === 'sqlite'
            && $connection->getDatabaseName() === ':memory:';
        if ($apply && !$testingSqlite) {
            $this->error('APPLY_BLOCKED: only APP_ENV=testing with SQLite :memory: is supported in G4.98.');
            return 1;
        }
        $path = (string) $this->argument('snapshot');
        if (!is_file($path) || !is_readable($path) || is_link($path) || filesize($path) > 33554432) {
            $this->error('ETL_BLOCKED: invalid private snapshot.');
            return 1;
        }
        try {
            $snapshot = json_decode(file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($snapshot) || ($snapshot['database'] ?? null) !== self::SOURCE
                || !is_array($snapshot['tables'] ?? null)
                || array_diff(array_keys($snapshot['tables']), array_keys(self::TABLES))) {
                throw new RuntimeException('Invalid snapshot identity or table scope.');
            }
            foreach (self::TABLES as $table => $fks) {
                if (!isset($snapshot['tables'][$table]) || !is_array($snapshot['tables'][$table])
                    || !array_is_list($snapshot['tables'][$table])) {
                    throw new RuntimeException('Missing or invalid table: '.$table);
                }
                if (!Schema::hasTable($table)) {
                    throw new RuntimeException('Target schema missing: '.$table);
                }
            }
            if (!Schema::hasTable('legacy_migration_ids')) {
                throw new RuntimeException('Legacy ID registry missing.');
            }
            if ($connection->getDriverName() === 'mysql') {
                $actualDatabase = DB::selectOne('SELECT DATABASE() AS db')->db ?? null;
            } elseif ($testingSqlite) {
                $actualDatabase = ':memory:';
            } else {
                throw new RuntimeException('Unsupported connection; only MySQL dry-run or test SQLite allowed.');
            }
            if (!$actualDatabase || $actualDatabase === self::SOURCE) {
                throw new RuntimeException('Source/target database identity rejected.');
            }
            $counts = array_fill_keys(array_keys(self::TABLES), ['new' => 0, 'mapped' => 0]);
            $seen = [];
            DB::beginTransaction();
            try {
                foreach (self::TABLES as $table => $foreigns) {
                    $allowed = array_flip(Schema::getColumnListing($table));
                    foreach ($snapshot['tables'][$table] as $row) {
                        if (!is_array($row) || !isset($row['id']) || !ctype_digit((string) $row['id'])
                            || (int) $row['id'] <= 0) {
                            throw new RuntimeException('Invalid source id in '.$table);
                        }
                        $oldId = (int) $row['id'];
                        if (isset($seen[$table][$oldId])) {
                            throw new RuntimeException('Duplicate source ID in '.$table);
                        }
                        $seen[$table][$oldId] = true;
                        $hash = hash('sha256', json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                        $existing = $this->mapping($table, $oldId);
                        if ($existing) {
                            if ($existing->source_row_hash !== $hash
                                || !DB::table($table)->where('id', $existing->target_id)->exists()) {
                                throw new RuntimeException('Mapping changed or missing for '.$table.' #'.$oldId);
                            }
                            $counts[$table]['mapped']++;
                            continue;
                        }
                        $record = array_intersect_key($row, $allowed);
                        unset($record['id']);
                        // Never migrate unverified physical files or legacy pointers silently.
                        foreach (['attachment', 'image', 'file', 'document_path'] as $key) {
                            if (!empty($record[$key])) {
                                throw new RuntimeException('Unverified attachment in '.$table.' #'.$oldId);
                            }
                        }
                        foreach ($foreigns as $column => $parent) {
                            $sourceRef = $row[$column] ?? null;
                            if (($sourceRef === null || $sourceRef === '')
                                && $column === 'quotation_id' && $table === 'po_customers') {
                                $record[$column] = null;
                                continue;
                            }
                            if (!ctype_digit((string) $sourceRef) || (int) $sourceRef < 1) {
                                throw new RuntimeException('Invalid FK '.$table.'.'.$column.' #'.$oldId);
                            }
                            $parentMap = $this->mapping($parent, (int) $sourceRef);
                            if (!$parentMap || !DB::table($parent)->where('id', $parentMap->target_id)->exists()) {
                                throw new RuntimeException('Unmapped parent '.$parent.' for '.$table.' #'.$oldId);
                            }
                            $record[$column] = $parentMap->target_id;
                        }
                        // Business rules on current target schema must win over legacy defaults.
                        if ($table === 'po_customers') {
                            $record['procurement_status'] = 'pending';
                        }
                        if ($table === 'po_supplier_details') {
                            $supplierMap = $this->mapping('po_suppliers', (int) $row['po_supplier_id']);
                            $poCustomerId = DB::table('po_suppliers')->where('id', $supplierMap->target_id)->value('po_customer_id');
                            $matches = DB::table('po_customer_details')
                                ->where('po_customer_id', $poCustomerId)
                                ->where('product_id', $record['product_id'])->pluck('id');
                            if ($matches->count() !== 1) {
                                throw new RuntimeException('Ambiguous supplier detail lineage #'.$oldId);
                            }
                            $record['po_customer_detail_id'] = $matches->first();
                        }
                        if (isset(self::NATURAL[$table])) {
                            $key = self::NATURAL[$table];
                            if (!is_string($row[$key] ?? null) || trim($row[$key]) === ''
                                || DB::table($table)->where($key, $row[$key])->exists()) {
                                throw new RuntimeException('Missing/duplicate business number '.$table.' #'.$oldId);
                            }
                        }
                        $newId = DB::table($table)->insertGetId($record);
                        DB::table('legacy_migration_ids')->insert([
                            'source_database' => self::SOURCE, 'source_table' => $table,
                            'source_id' => $oldId, 'target_table' => $table, 'target_id' => $newId,
                            'source_row_hash' => $hash, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $counts[$table]['new']++;
                    }
                }
                if ($apply) {
                    DB::commit();
                } else {
                    DB::rollBack();
                }
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }
            $this->line(json_encode([
                'status' => 'PASS', 'mode' => $apply ? 'TEST_APPLY' : 'DRY_RUN',
                'transaction' => $apply ? 'COMMITTED_TEST_MEMORY_ONLY' : 'ROLLED_BACK',
                'counts' => $counts, 'go_live_approved' => false,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            return 0;
        } catch (Throwable $e) {
            $this->error('ETL_BLOCKED: '.$e->getMessage());
            return 1;
        }
    }

    private function mapping(string $table, int $sourceId): ?object
    {
        return DB::table('legacy_migration_ids')
            ->where('source_database', self::SOURCE)->where('source_table', $table)
            ->where('source_id', $sourceId)->first();
    }
}
