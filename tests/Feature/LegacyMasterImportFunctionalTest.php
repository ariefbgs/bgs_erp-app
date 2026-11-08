<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Only run with the existing SQLite :memory: test safeguard. */
class LegacyMasterImportFunctionalTest extends TestCase
{
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        foreach (['companies' => 'company_code', 'customers' => 'customer_code',
            'suppliers' => 'supplier_code', 'products' => 'product_code'] as $table => $key) {
            Schema::create($table, static function (Blueprint $t) use ($key): void {
                $t->id();
                $t->string($key)->unique();
                $t->string('name');
            });
        }
        Schema::create('legacy_migration_ids', static function (Blueprint $t): void {
            $t->id();
            $t->string('source_database');
            $t->string('source_table');
            $t->unsignedBigInteger('source_id');
            $t->string('target_table');
            $t->unsignedBigInteger('target_id');
            $t->char('source_row_hash', 64);
            $t->timestamps();
            $t->unique(['source_database','source_table','source_id']);
        });
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    private function fixture(array $companies = [['id' => 9, 'company_code' => 'UAT-CO', 'name' => 'Synthetic Company']],
        array $customers = [], array $suppliers = [], array $products = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bgs-g497-');
        $this->assertNotFalse($path);
        file_put_contents($path, json_encode([
            'database' => 'smartlo1_erp_app',
            'tables' => compact('companies', 'customers', 'suppliers', 'products'),
        ], JSON_THROW_ON_ERROR));
        $this->files[] = $path;
        return $path;
    }

    public function test_dry_run_rolls_back_master_and_registry(): void
    {
        $this->assertSame(0, Artisan::call('legacy:load-masters', ['snapshot' => $this->fixture()]));
        $report = json_decode(Artisan::output(), true, 64, JSON_THROW_ON_ERROR);
        $this->assertSame('ROLLED_BACK', $report['transaction_status']);
        $this->assertSame(0, $report['database_writes_committed']);
        $this->assertSame(0, DB::table('companies')->count());
        $this->assertSame(0, DB::table('legacy_migration_ids')->count());
    }

    public function test_test_only_apply_maps_ids_and_repeat_is_idempotent(): void
    {
        $file = $this->fixture();
        $this->assertSame(0, Artisan::call('legacy:load-masters', ['snapshot' => $file, '--apply' => true]));
        $targetId = DB::table('companies')->value('id');
        $this->assertNotNull($targetId);
        $this->assertSame(9, (int) DB::table('legacy_migration_ids')->value('source_id'));
        $this->assertSame((int) $targetId, (int) DB::table('legacy_migration_ids')->value('target_id'));
        $this->assertSame(0, Artisan::call('legacy:load-masters', ['snapshot' => $file, '--apply' => true]));
        $this->assertSame(1, DB::table('companies')->count());
        $this->assertSame(1, DB::table('legacy_migration_ids')->count());
    }

    public function test_exception_causes_atomic_rollback(): void
    {
        $file = $this->fixture([
            ['id' => 9, 'company_code' => 'UAT-CO', 'name' => 'Synthetic Company'],
            ['id' => 10, 'company_code' => '', 'name' => 'Invalid Company'],
        ]);
        $this->assertSame(2, Artisan::call('legacy:load-masters', ['snapshot' => $file, '--apply' => true]));
        $this->assertSame(0, DB::table('companies')->count());
        $this->assertSame(0, DB::table('legacy_migration_ids')->count());
    }

    public function test_existing_natural_key_does_not_get_overwritten(): void
    {
        DB::table('companies')->insert(['company_code' => 'UAT-CO', 'name' => 'Existing']);
        $this->assertSame(2, Artisan::call('legacy:load-masters', [
            'snapshot' => $this->fixture(), '--apply' => true,
        ]));
        $this->assertSame('Existing', DB::table('companies')->value('name'));
        $this->assertSame(0, DB::table('legacy_migration_ids')->count());
    }
}
