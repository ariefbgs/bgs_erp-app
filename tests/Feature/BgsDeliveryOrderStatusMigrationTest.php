<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BgsDeliveryOrderStatusMigrationTest extends TestCase
{
    private ?string $fixtureDatabase = null;
    private string $originalConnection;
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('erp_app_staging_testing_1026', DB::connection()->getDatabaseName());
        $this->originalConnection = config('database.default');
        $this->fixtureDatabase = 'erp_app_staging_testing_1026_g1_' . bin2hex(random_bytes(6));
        DB::statement('CREATE DATABASE `' . $this->fixtureDatabase . '`');
        config(['database.connections.g1_status_fixture' => array_replace(
            config('database.connections.' . $this->originalConnection),
            ['database' => $this->fixtureDatabase]
        ), 'database.default' => 'g1_status_fixture']);
        DB::statement("CREATE TABLE delivery_orders (id INT PRIMARY KEY, status ENUM('pending','partial','shipped','delivered') NOT NULL DEFAULT 'pending' COMMENT 'lifecycle fixture')");
    }

    protected function tearDown(): void
    {
        try {
            if ($this->fixtureDatabase !== null) {
                DB::disconnect('g1_status_fixture');
                config(['database.default' => $this->originalConnection]);
                DB::connection($this->originalConnection)->statement('DROP DATABASE `' . $this->fixtureDatabase . '`');
            }
        } finally {
            parent::tearDown();
        }
    }

    private function migration(): object
    {
        return require base_path('database/migrations/2026_10_17_000000_enforce_delivery_order_status_lifecycle.php');
    }

    public function test_supported_rows_and_metadata_survive_and_migration_is_repeatable(): void
    {
        DB::table('delivery_orders')->insert([['id' => 1, 'status' => 'pending'], ['id' => 2, 'status' => 'delivered']]);
        $this->migration()->up();
        $this->migration()->up();
        $column = DB::selectOne("SHOW FULL COLUMNS FROM delivery_orders WHERE Field = 'status'");
        $this->assertSame("enum('pending','delivered')", $column->Type);
        $this->assertSame('pending', $column->Default);
        $this->assertSame('lifecycle fixture', $column->Comment);
        $this->assertSame(['pending', 'delivered'], DB::table('delivery_orders')->orderBy('id')->pluck('status')->all());
    }

    public function test_legacy_rows_block_migration_without_conversion_or_schema_change(): void
    {
        foreach (['partial', 'shipped'] as $status) {
            DB::table('delivery_orders')->insert(['id' => 1, 'status' => $status]);
            try {
                $this->migration()->up();
                $this->fail('Legacy status must block migration.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('reviewed mapping', $exception->getMessage());
            }
            $this->assertSame($status, DB::table('delivery_orders')->value('status'));
            $this->assertSame("enum('pending','partial','shipped','delivered')", DB::selectOne("SHOW FULL COLUMNS FROM delivery_orders WHERE Field = 'status'")->Type);
            DB::table('delivery_orders')->delete();
        }
    }
}
