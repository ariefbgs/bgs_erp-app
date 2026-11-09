<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/** Import an approved JSON snapshot; never connect to legacy production. */
class ImportLegacyMasters extends Command
{
    protected $signature = 'legacy:load-masters {snapshot : Private JSON snapshot} {--apply : Commit ONLY in isolated testing environment}';
    protected $description = 'Transactional master ETL with ID registry and exceptions; production apply is deliberately disabled';

    private const SOURCE = 'smartlo1_erp_app';
    private const KEYS = [
        'companies' => 'company_code',
        'customers' => 'customer_code',
        'suppliers' => 'supplier_code',
        'products' => 'product_code',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        if ($apply) {
            try { \App\Support\LegacyEtlTestGuard::assertApplyAllowed(); }
            catch (\Throwable $e) { $this->error($e->getMessage()); return 1; }
        }
        $path = (string) $this->argument('snapshot');
        if (!is_file($path) || !is_readable($path) || is_link($path) || filesize($path) > 33554432) {
            $this->error('Invalid private snapshot (must be readable regular file <=32 MiB).');
            return 1;
        }
        try {
            $snapshot = json_decode(file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($snapshot) || ($snapshot['database'] ?? '') !== self::SOURCE
                || !isset($snapshot['tables']) || !is_array($snapshot['tables'])
                || array_diff(array_keys($snapshot['tables']), array_keys(self::KEYS)) !== []) {
                throw new RuntimeException('Invalid snapshot identity or table scope.');
            }
            foreach (array_keys(self::KEYS) as $table) {
                if (!isset($snapshot['tables'][$table]) || !is_array($snapshot['tables'][$table])
                    || !array_is_list($snapshot['tables'][$table])) {
                    throw new RuntimeException("Missing or invalid {$table} records.");
                }
            }
            $connection = DB::connection();
            $identity = $connection->getDriverName() === 'sqlite'
                ? $connection->getDatabaseName()
                : (DB::selectOne('SELECT DATABASE() AS name')->name ?? null);
            if ($identity === null || $identity === self::SOURCE || !Schema::hasTable('legacy_migration_ids')) {
                throw new RuntimeException('Target database identity or registry invalid.');
            }
            $summary = ['source' => self::SOURCE, 'target' => $identity, 'mode' => $apply ? 'TEST_APPLY' : 'DRY_RUN',
                'inserted' => [], 'mapped' => [], 'exceptions' => []];
            DB::beginTransaction();
            try {
                foreach (self::KEYS as $table => $naturalKey) {
                    $summary['inserted'][$table] = 0;
                    $summary['mapped'][$table] = 0;
                    $summary['exceptions'][$table] = 0;
                    $allowed = Schema::getColumnListing($table);
                    $seen = [];
                    foreach ($snapshot['tables'][$table] as $row) {
                        if (!is_array($row) || !isset($row['id']) || !ctype_digit((string) $row['id'])
                            || (int) $row['id'] < 1 || !isset($row[$naturalKey])
                            || !is_string($row[$naturalKey]) || trim($row[$naturalKey]) === '') {
                            $summary['exceptions'][$table]++;
                            continue;
                        }
                        $oldId = (int) $row['id'];
                        $code = $row[$naturalKey];
                        if (isset($seen[$oldId]) || isset($seen['key:'.$code])) {
                            $summary['exceptions'][$table]++;
                            continue;
                        }
                        $seen[$oldId] = true;
                        $seen['key:'.$code] = true;
                        $hash = hash('sha256', json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                        $existing = DB::table('legacy_migration_ids')
                            ->where('source_database', self::SOURCE)->where('source_table', $table)
                            ->where('source_id', $oldId)->first();
                        if ($existing) {
                            if ($existing->source_row_hash !== $hash
                                || !DB::table($table)->where('id', $existing->target_id)->exists()) {
                                $summary['exceptions'][$table]++;
                            } else {
                                $summary['mapped'][$table]++;
                            }
                            continue;
                        }
                        if (DB::table($table)->where($naturalKey, $code)->exists()) {
                            // No guessing or overwriting an existing business identity.
                            $summary['exceptions'][$table]++;
                            continue;
                        }
                        $record = array_intersect_key($row, array_flip($allowed));
                        unset($record['id']); // Legacy IDs must never be reused implicitly.
                        // Never reuse source attachment paths before a verified file migration.
                        foreach (['logo', 'image', 'ttd_sales_support', 'ttd_do', 'ttd_inv', 'ttd_inv2'] as $attachment) {
                            if (!empty($record[$attachment])) {
                                $summary['exceptions'][$table]++;
                                // Keep the source row out until the supporting file is verified.
                                continue 2;
                            }
                        }
                        try {
                            $targetId = DB::table($table)->insertGetId($record);
                            DB::table('legacy_migration_ids')->insert([
                                'source_database' => self::SOURCE, 'source_table' => $table,
                                'source_id' => $oldId, 'target_table' => $table, 'target_id' => $targetId,
                                'source_row_hash' => $hash, 'created_at' => now(), 'updated_at' => now(),
                            ]);
                            $summary['inserted'][$table]++;
                        } catch (Throwable $e) {
                            // Fail closed: no partially imported master batch.
                            throw new RuntimeException("Cannot load {$table} row {$oldId}; rollback required.", 0, $e);
                        }
                    }
                }
                // Never approve a partial dataset automatically.
                if ($apply && array_sum($summary['exceptions']) === 0) {
                    DB::commit();
                } else {
                    DB::rollBack();
                }
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }
            $summary['database_writes_committed'] = $apply && array_sum($summary['exceptions']) === 0
                ? array_sum($summary['inserted']) : 0;
            $summary['transaction_status'] = $summary['database_writes_committed'] > 0
                ? 'COMMITTED_TEST_ONLY' : 'ROLLED_BACK';
            $summary['go_live_approved'] = false;
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            return array_sum($summary['exceptions']) === 0 ? 0 : 2;
        } catch (Throwable $e) {
            $this->error('ETL_BLOCKED: '.$e->getMessage());
            return 1;
        }
    }
}
