<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/** G4.99: remaining operational document lineage, never writes to live databases. */
class ImportLegacyFulfilment extends Command
{
    protected $signature = 'legacy:load-fulfilment {snapshot : Approved private JSON snapshot} {--apply : Test SQLite memory only}';
    protected $description = 'Transactional test ETL for receipts, invoices, delivery and document receipts';

    private const SOURCE = 'smartlo1_erp_app';
    private const PARENTS = [
        'goods_receipts' => ['po_supplier_id' => 'po_suppliers'],
        'goods_receipt_details' => ['goods_receipt_id' => 'goods_receipts', 'po_supplier_detail_id' => 'po_supplier_details', 'product_id' => 'products'],
        'invoice_customers' => ['po_customer_id' => 'po_customers'],
        'invoice_customer_details' => ['invoice_customer_id' => 'invoice_customers', 'product_id' => 'products'],
        'delivery_orders' => ['po_customer_id' => 'po_customers'],
        'delivery_order_details' => ['delivery_order_id' => 'delivery_orders', 'product_id' => 'products'],
        'document_receipts' => ['delivery_order_id' => 'delivery_orders'],
    ];
    private const NUMBERS = [
        'goods_receipts' => 'receipt_number',
        'invoice_customers' => 'invoice_number',
        'delivery_orders' => 'do_number',
        'document_receipts' => 'receipt_number',
    ];

    public function handle(): int
    {
        $connection = DB::connection();
        $testMemory = app()->environment('testing') && $connection->getDriverName() === 'sqlite'
            && $connection->getDatabaseName() === ':memory:';
        $apply = (bool) $this->option('apply');
        if ($apply) {
            try { \App\Support\LegacyEtlTestGuard::assertApplyAllowed(); }
            catch (\Throwable $e) { $this->error($e->getMessage()); return 1; }
        }
        $path = (string) $this->argument('snapshot');
        if (!is_file($path) || !is_readable($path) || is_link($path) || filesize($path) > 33554432) {
            $this->error('ETL_BLOCKED: invalid private snapshot');
            return 1;
        }
        try {
            $snapshot = json_decode(file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($snapshot) || ($snapshot['database'] ?? null) !== self::SOURCE
                || !is_array($snapshot['tables'] ?? null)
                || array_diff(array_keys($snapshot['tables']), array_keys(self::PARENTS))) {
                throw new RuntimeException('Snapshot database or scope mismatch');
            }
            foreach (array_keys(self::PARENTS) as $table) {
                if (!isset($snapshot['tables'][$table]) || !is_array($snapshot['tables'][$table])
                    || !array_is_list($snapshot['tables'][$table]) || !Schema::hasTable($table)) {
                    throw new RuntimeException('Missing table/snapshot: '.$table);
                }
            }
            foreach (['legacy_migration_ids', 'document_receipt_delivery_orders', 'document_receipt_invoices', 'po_customer_details'] as $table) {
                if (!Schema::hasTable($table)) throw new RuntimeException('Missing target table: '.$table);
            }
            if ($connection->getDriverName() === 'mysql') {
                $db = DB::selectOne('SELECT DATABASE() AS db')->db ?? null;
                if (!$db || $db === self::SOURCE) throw new RuntimeException('Unsafe database identity');
            } elseif (!$testMemory) {
                throw new RuntimeException('Unsupported database connection');
            }
            $counts = array_fill_keys(array_keys(self::PARENTS), ['new' => 0, 'mapped' => 0]);
            DB::beginTransaction();
            try {
                $seen = [];
                foreach (self::PARENTS as $table => $fks) {
                    $columns = array_flip(Schema::getColumnListing($table));
                    foreach ($snapshot['tables'][$table] as $row) {
                        if (!is_array($row) || !ctype_digit((string) ($row['id'] ?? '')) || (int) $row['id'] < 1) {
                            throw new RuntimeException('Invalid legacy id in '.$table);
                        }
                        $id = (int) $row['id'];
                        if (isset($seen[$table][$id])) throw new RuntimeException('Duplicate legacy id in '.$table);
                        $seen[$table][$id] = true;
                        $hash = hash('sha256', json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                        $mapped = $this->mapping($table, $id);
                        if ($mapped) {
                            if ($mapped->source_row_hash !== $hash || !DB::table($table)->where('id', $mapped->target_id)->exists()) {
                                throw new RuntimeException('Changed mapping in '.$table.' #'.$id);
                            }
                            $counts[$table]['mapped']++;
                            continue;
                        }
                        foreach (['attachment', 'image', 'file', 'document_path'] as $attachment) {
                            if (!empty($row[$attachment])) throw new RuntimeException('Unverified attachment in '.$table.' #'.$id);
                        }
                        $record = array_intersect_key($row, $columns);
                        unset($record['id']);
                        foreach ($fks as $fk => $parent) {
                            $sourceId = $row[$fk] ?? null;
                            if ($table === 'document_receipts') {
                                // Legacy field is absent in canonical target; pivot is populated below.
                                continue;
                            }
                            if (!ctype_digit((string) $sourceId) || (int) $sourceId < 1) {
                                throw new RuntimeException('Invalid reference '.$table.'.'.$fk.' #'.$id);
                            }
                            $record[$fk] = $this->requiredId($parent, (int) $sourceId);
                        }
                        if ($table === 'invoice_customer_details' || $table === 'delivery_order_details') {
                            $head = $table === 'invoice_customer_details'
                                ? DB::table('invoice_customers')->where('id', $record['invoice_customer_id'])->first()
                                : DB::table('delivery_orders')->where('id', $record['delivery_order_id'])->first();
                            if (!$head) throw new RuntimeException('Missing document header for '.$table.' #'.$id);
                            $matches = DB::table('po_customer_details')
                                ->where('po_customer_id', $head->po_customer_id)
                                ->where('product_id', $record['product_id'])->pluck('id');
                            if ($matches->count() !== 1) throw new RuntimeException('Ambiguous item lineage '.$table.' #'.$id);
                            $record['po_customer_detail_id'] = $matches->first();
                        }
                        if ($table === 'document_receipts') {
                            $legacyDo = $row['delivery_order_id'] ?? null;
                            if (!ctype_digit((string) $legacyDo) || (int) $legacyDo < 1) {
                                throw new RuntimeException('Document Receipt without DO #'.$id);
                            }
                            $doId = $this->requiredId('delivery_orders', (int) $legacyDo);
                            $poId = DB::table('delivery_orders')->where('id', $doId)->value('po_customer_id');
                            if (!$poId) throw new RuntimeException('Document Receipt PO missing #'.$id);
                            $record['po_customer_id'] = $poId;
                            unset($record['delivery_order_id'], $record['invoice_number'], $record['tax_invoice_number']);
                        }
                        if ($table === 'delivery_orders') {
                            $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
                            if (!in_array($status, ['pending', 'delivered'], true)) {
                                throw new RuntimeException('Delivery status requires manual transform #'.$id);
                            }
                        }
                        if (isset(self::NUMBERS[$table])) {
                            $number = self::NUMBERS[$table];
                            if (!is_string($row[$number] ?? null) || trim($row[$number]) === '') {
                                throw new RuntimeException('Missing business number '.$table.' #'.$id);
                            }
                            if ($table !== 'document_receipts' && DB::table($table)->where($number, $row[$number])->exists()) {
                                throw new RuntimeException('Duplicate business number '.$table.' #'.$id);
                            }
                        }
                        $targetId = DB::table($table)->insertGetId($record);
                        $this->saveMap($table, $id, $targetId, $hash);
                        if ($table === 'document_receipts') {
                            $doId = $this->requiredId('delivery_orders', (int) $row['delivery_order_id']);
                            DB::table('document_receipt_delivery_orders')->insert([
                                'document_receipt_id' => $targetId, 'delivery_order_id' => $doId,
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                            $parts = array_values(array_unique(array_filter(array_map('trim',
                                explode(',', (string) ($row['invoice_number'] ?? ''))))));
                            if ($parts === []) throw new RuntimeException('Missing DR invoices #'.$id);
                            foreach ($parts as $number) {
                                $matches = DB::table('invoice_customers')->where('invoice_number', $number)
                                    ->where('po_customer_id', $record['po_customer_id'])->pluck('id');
                                if ($matches->count() !== 1) throw new RuntimeException('Unmatched DR invoice #'.$id);
                                DB::table('document_receipt_invoices')->insert([
                                    'document_receipt_id' => $targetId, 'invoice_customer_id' => $matches->first(),
                                    'created_at' => now(), 'updated_at' => now(),
                                ]);
                            }
                        }
                        $counts[$table]['new']++;
                    }
                }
                if ($apply) DB::commit(); else DB::rollBack();
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }
            $this->line(json_encode(['status' => 'PASS', 'mode' => $apply ? 'TEST_APPLY' : 'DRY_RUN',
                'counts' => $counts, 'go_live_approved' => false], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            return 0;
        } catch (Throwable $e) {
            $this->error('ETL_BLOCKED: '.$e->getMessage());
            return 1;
        }
    }

    private function mapping(string $table, int $sourceId): ?object
    {
        return DB::table('legacy_migration_ids')->where('source_database', self::SOURCE)
            ->where('source_table', $table)->where('source_id', $sourceId)->first();
    }

    private function requiredId(string $table, int $sourceId): int
    {
        $mapping = $this->mapping($table, $sourceId);
        if (!$mapping || !DB::table($table)->where('id', $mapping->target_id)->exists()) {
            throw new RuntimeException('Unmapped parent '.$table.' #'.$sourceId);
        }
        return (int) $mapping->target_id;
    }

    private function saveMap(string $table, int $source, int $target, string $hash): void
    {
        DB::table('legacy_migration_ids')->insert([
            'source_database' => self::SOURCE, 'source_table' => $table, 'source_id' => $source,
            'target_table' => $table, 'target_id' => $target, 'source_row_hash' => $hash,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
