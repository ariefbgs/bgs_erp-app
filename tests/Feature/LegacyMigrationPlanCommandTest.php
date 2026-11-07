<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyMigrationPlanCommandTest extends TestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) { unlink($file); }
        }
        parent::tearDown();
    }

    private function input(array $value): string
    {
        $file = tempnam(sys_get_temp_dir(), 'bgs-g2-');
        $this->files[] = $file;
        file_put_contents($file, json_encode($value, JSON_THROW_ON_ERROR));
        return $file;
    }

    private function arguments(bool $exception = false): array
    {
        return ['manifest' => $this->input(['version' => 1, 'entities' => [
            ['name' => 'products', 'source_table' => 'old_products', 'target_table' => 'products',
                'source_id' => 'id', 'target_id' => 'id', 'fields' => ['product_code' => 'code'], 'key' => ['product_code']],
        ]]), 'source' => $this->input(['tables' => ['old_products' => [['id' => 9, 'code' => $exception ? '' : 'SYNTHETIC-1']]]]),
            'target' => $this->input(['tables' => ['products' => []]])];
    }

    public function test_command_emits_reconcilable_json_without_queries_or_input_changes(): void
    {
        $arguments = $this->arguments();
        $hashes = array_map(fn ($path) => hash_file('sha256', $path), $arguments);
        $queries = [];
        DB::listen(function ($query) use (&$queries) { $queries[] = $query->sql; });
        $this->assertSame(0, Artisan::call('legacy:plan', $arguments));
        $report = json_decode(Artisan::output(), true, 64, JSON_THROW_ON_ERROR);
        $this->assertSame('DRY_RUN', $report['mode']);
        $this->assertSame($hashes, $report['input_sha256']);
        $this->assertSame($hashes, array_map(fn ($path) => hash_file('sha256', $path), $arguments));
        $this->assertSame([], $queries);
        $this->assertNull($report['registry'][0]['target_id']);
        $this->assertSame(1, $report['reconciliation']['products']['classified_rows']);
    }

    public function test_command_returns_review_exit_code_for_an_exception(): void
    {
        $this->assertSame(2, Artisan::call('legacy:plan', $this->arguments(true)));
        $report = json_decode(Artisan::output(), true, 64, JSON_THROW_ON_ERROR);
        $this->assertSame('INCOMPLETE_BUSINESS_KEY', $report['exceptions'][0]['reason']);
    }

    public function test_malformed_input_is_rejected_without_a_success_report(): void
    {
        $arguments = $this->arguments();
        file_put_contents($arguments['source'], '{invalid');
        $this->assertSame(1, Artisan::call('legacy:plan', $arguments));
        $this->assertStringContainsString('Plan rejected:', Artisan::output());
        $this->assertStringNotContainsString('"mode": "DRY_RUN"', Artisan::output());
    }
}
