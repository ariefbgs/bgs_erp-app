<?php

namespace Tests\Unit;

use App\Services\LegacyMigration\DryRunPlanner;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LegacyDryRunPlannerTest extends TestCase
{
    private function manifest(): array
    {
        return ['version' => 1, 'entities' => [
            ['name' => 'customers', 'source_table' => 'old_customers', 'target_table' => 'customers',
                'source_id' => 'id', 'target_id' => 'id', 'fields' => ['customer_code' => 'code'], 'key' => ['customer_code']],
            ['name' => 'orders', 'source_table' => 'old_orders', 'target_table' => 'po_customers',
                'source_id' => 'id', 'target_id' => 'id', 'fields' => ['po_number' => 'number', 'status' => 'status'],
                'key' => ['po_number'], 'allowed' => ['status' => ['received']],
                'dependencies' => ['customer_id' => ['entity' => 'customers', 'source_field' => 'customer_id']]],
        ]];
    }

    private function source(): array
    {
        return ['tables' => ['old_customers' => [['id' => 7, 'code' => 'C-1']],
            'old_orders' => [['id' => 20, 'number' => 'PO-1', 'status' => 'received', 'customer_id' => 7]]]];
    }

    private function target(): array
    {
        return ['tables' => ['customers' => [['id' => 93, 'customer_code' => 'C-1']],
            'po_customers' => [['id' => 501, 'po_number' => 'PO-1', 'status' => 'received', 'customer_id' => 93]]]];
    }

    private function plan(?array $source = null, ?array $target = null, ?array $manifest = null): array
    {
        return (new DryRunPlanner)->plan($manifest ?? $this->manifest(), $source ?? $this->source(), $target ?? $this->target());
    }

    public function test_maps_by_business_key_and_remaps_foreign_ids_without_mutating_inputs(): void
    {
        $source = $this->source(); $target = $this->target();
        $before = serialize([$source, $target]);
        $report = $this->plan($source, $target);
        $this->assertSame(93, $report['registry'][0]['target_id']);
        $this->assertSame(501, $report['registry'][1]['target_id']);
        $this->assertSame('TRANSFORMED', $report['registry'][1]['state']);
        $this->assertSame(93, $report['registry'][1]['references']['customer_id']['target_id']);
        $this->assertSame([], $report['exceptions']);
        $this->assertSame(0, $report['database_writes']);
        $this->assertFalse($report['load_implemented']);
        $this->assertSame($before, serialize([$source, $target]));
        $this->assertSame($report, $this->plan($source, $target));
        foreach ($report['reconciliation'] as $counts) {
            $this->assertSame($counts['source_rows'], $counts['classified_rows']);
        }
    }

    public function test_new_records_never_reuse_source_ids_or_allocate_target_ids(): void
    {
        $report = $this->plan(null, ['tables' => ['customers' => [], 'po_customers' => []]]);
        $this->assertNull($report['registry'][0]['target_id']);
        $this->assertNull($report['registry'][1]['target_id']);
        $this->assertSame(7, $report['registry'][1]['references']['customer_id']['source_id']);
        $this->assertNull($report['registry'][1]['references']['customer_id']['target_id']);
        $this->assertSame([], $report['exceptions']);
    }

    public function test_ambiguous_target_keys_block_parent_and_child(): void
    {
        $target = $this->target();
        $target['tables']['customers'][] = ['id' => 94, 'customer_code' => 'C-1'];
        $report = $this->plan(null, $target);
        $this->assertSame('AMBIGUOUS_BUSINESS_KEY', $report['registry'][0]['reason']);
        $this->assertSame('UNRESOLVED_PARENT', $report['registry'][1]['reason']);
        $this->assertCount(2, $report['exceptions']);
    }

    public function test_duplicate_source_keys_and_ids_are_never_guessed(): void
    {
        foreach ([7, 8] as $id) {
            $source = $this->source();
            $source['tables']['old_customers'][] = ['id' => $id, 'code' => 'C-1'];
            $report = $this->plan($source);
            $this->assertCount(3, $report['exceptions']);
            $this->assertNull($report['registry'][0]['target_id']);
            $this->assertNull($report['registry'][1]['target_id']);
        }
    }

    public function test_orphans_conflicts_and_unapproved_values_have_explicit_reasons(): void
    {
        $source = $this->source(); $source['tables']['old_orders'][0]['customer_id'] = 999;
        $this->assertSame('UNRESOLVED_PARENT', $this->plan($source)['registry'][1]['reason']);
        $source = $this->source(); $source['tables']['old_orders'][0]['status'] = 'shipped';
        $this->assertSame('LEGACY-EXCEPTION', $this->plan($source)['registry'][1]['state']);
        $target = $this->target(); $target['tables']['po_customers'][0]['customer_id'] = 7;
        $this->assertSame('TARGET_FIELD_CONFLICT', $this->plan(null, $target)['registry'][1]['reason']);
    }

    public function test_missing_fields_and_invalid_ids_are_rejected(): void
    {
        $source = $this->source(); unset($source['tables']['old_orders'][0]['customer_id']);
        $this->assertSame('MISSING_DEPENDENCY_FIELD', $this->plan($source)['registry'][1]['reason']);
        $source['tables']['old_orders'][0]['id'] = true;
        $this->assertSame('INVALID_ID', $this->plan($source)['registry'][1]['reason']);
        $source = $this->source(); unset($source['tables']['old_customers'][0]['code']);
        $this->assertSame('MISSING_OR_INVALID_FIELD', $this->plan($source)['registry'][0]['reason']);
    }

    public function test_scope_gaps_are_reported_and_raw_business_fields_are_not_returned(): void
    {
        $source = $this->source(); $source['tables']['unmapped_table'] = [];
        $report = $this->plan($source);
        $this->assertSame(['unmapped_table'], $report['unscoped_source_tables']);
        $this->assertStringNotContainsString('PO-1', json_encode($report));
        $this->assertStringNotContainsString('C-1', json_encode($report));
    }

    public function test_duplicate_target_ids_reject_snapshot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $target = $this->target(); $target['tables']['customers'][] = ['id' => 93, 'customer_code' => 'C-2'];
        $this->plan(null, $target);
    }

    public function test_unordered_dependencies_are_invalid_manifests(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $manifest = $this->manifest(); $manifest['entities'] = array_reverse($manifest['entities']);
        $this->plan(null, null, $manifest);
    }

    public function test_target_id_copy_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $manifest = $this->manifest(); $manifest['entities'][0]['fields']['id'] = 'id';
        $this->plan(null, null, $manifest);
    }

    public function test_absent_scoped_table_is_not_treated_as_an_empty_export(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $source = $this->source(); unset($source['tables']['old_orders']);
        $this->plan($source);
    }

    public function test_type_mismatch_in_business_keys_never_creates_an_existing_mapping(): void
    {
        $source = $this->source(); $source['tables']['old_customers'][0]['code'] = 123;
        $target = $this->target(); $target['tables']['customers'][0]['customer_code'] = '123';
        $this->assertNull($this->plan($source, $target)['registry'][0]['target_id']);
    }
}
