<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyOrderImportFunctionalTest extends TestCase
{
    private array $temporarySnapshots = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['customers', 'products', 'suppliers'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name')->nullable();
            });
        }
        Schema::create('legacy_migration_ids', function (Blueprint $t) {
            $t->id();
            $t->string('source_database'); $t->string('source_table'); $t->unsignedBigInteger('source_id');
            $t->string('target_table'); $t->unsignedBigInteger('target_id'); $t->string('source_row_hash');
            $t->timestamps();
            $t->unique(['source_database', 'source_table', 'source_id']);
        });
        Schema::create('quotations', function (Blueprint $t) {
            $t->id(); $t->string('quotation_number')->unique(); $t->unsignedBigInteger('customer_id'); $t->date('date');
        });
        Schema::create('quotation_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('quotation_id'); $t->unsignedBigInteger('product_id');
            $t->integer('quantity');
        });
        Schema::create('po_customers', function (Blueprint $t) {
            $t->id(); $t->string('po_number')->unique(); $t->unsignedBigInteger('customer_id');
            $t->unsignedBigInteger('quotation_id')->nullable(); $t->string('procurement_status')->default('pending');
        });
        Schema::create('po_customer_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('po_customer_id'); $t->unsignedBigInteger('product_id'); $t->integer('quantity');
        });
        Schema::create('po_suppliers', function (Blueprint $t) {
            $t->id(); $t->string('po_supplier_number')->unique(); $t->unsignedBigInteger('po_customer_id');
            $t->unsignedBigInteger('customer_id'); $t->unsignedBigInteger('supplier_id');
        });
        Schema::create('po_supplier_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('po_supplier_id'); $t->unsignedBigInteger('product_id');
            $t->unsignedBigInteger('po_customer_detail_id'); $t->integer('quantity');
        });
        foreach ([['customers', 5, 800], ['products', 7, 900], ['suppliers', 3, 700]] as [$table, $legacy, $target]) {
            DB::table($table)->insert(['id' => $target, 'name' => 'synthetic']);
            DB::table('legacy_migration_ids')->insert([
                'source_database' => 'smartlo1_erp_app', 'source_table' => $table, 'source_id' => $legacy,
                'target_table' => $table, 'target_id' => $target, 'source_row_hash' => str_repeat('a', 64),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->temporarySnapshots as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    private function snapshot(array $override = []): string
    {
        $tables = [
            'quotations' => [['id' => 10, 'quotation_number' => 'QT-10', 'customer_id' => 5, 'date' => '2026-01-01']],
            'quotation_details' => [['id' => 11, 'quotation_id' => 10, 'product_id' => 7, 'quantity' => 10]],
            'po_customers' => [
                ['id' => 20, 'po_number' => 'PO-20', 'customer_id' => 5, 'quotation_id' => 10],
                ['id' => 21, 'po_number' => 'PO-21', 'customer_id' => 5, 'quotation_id' => 10],
            ],
            'po_customer_details' => [
                ['id' => 30, 'po_customer_id' => 20, 'product_id' => 7, 'quantity' => 10],
                ['id' => 31, 'po_customer_id' => 21, 'product_id' => 7, 'quantity' => 4],
            ],
            'po_suppliers' => [['id' => 40, 'po_supplier_number' => 'PS-40', 'po_customer_id' => 20, 'customer_id' => 5, 'supplier_id' => 3]],
            'po_supplier_details' => [['id' => 50, 'po_supplier_id' => 40, 'product_id' => 7, 'quantity' => 10]],
        ];
        foreach ($override as $table => $rows) { $tables[$table] = $rows; }
        $path = tempnam(sys_get_temp_dir(), 'bgs_orders_');
        $this->temporarySnapshots[] = $path;
        file_put_contents($path, json_encode(['database' => 'smartlo1_erp_app', 'tables' => $tables], JSON_THROW_ON_ERROR));
        return $path;
    }

    public function test_dry_run_rolls_back_all_order_rows_and_registry(): void
    {
        $file = $this->snapshot();
        $this->assertSame(0, Artisan::call('legacy:load-orders', ['snapshot' => $file]));
        $this->assertSame(0, DB::table('quotations')->count());
        $this->assertSame(0, DB::table('po_suppliers')->count());
        $this->assertSame(3, DB::table('legacy_migration_ids')->count());
    }

    public function test_test_only_apply_preserves_two_po_per_quotation_and_supplier_lineage_and_is_idempotent(): void
    {
        $file = $this->snapshot();
        $this->assertSame(0, Artisan::call('legacy:load-orders', ['snapshot' => $file, '--apply' => true]));
        $quotationId = DB::table('legacy_migration_ids')->where('source_table', 'quotations')->where('source_id', 10)->value('target_id');
        $this->assertSame(2, DB::table('po_customers')->where('quotation_id', $quotationId)->count());
        $this->assertSame('pending', DB::table('po_customers')->where('po_number', 'PO-20')->value('procurement_status'));
        $detailId = DB::table('legacy_migration_ids')->where('source_table', 'po_customer_details')->where('source_id', 30)->value('target_id');
        $this->assertEquals($detailId, DB::table('po_supplier_details')->value('po_customer_detail_id'));
        $this->assertSame(0, Artisan::call('legacy:load-orders', ['snapshot' => $file, '--apply' => true]));
        $this->assertSame(1, DB::table('po_suppliers')->count());
        $this->assertSame(11, DB::table('legacy_migration_ids')->count()); // 3 masters + 8 orders
    }

    public function test_missing_parent_blocks_and_rolls_back_everything(): void
    {
        $file = $this->snapshot(['quotation_details' => [
            ['id' => 11, 'quotation_id' => 999, 'product_id' => 7, 'quantity' => 10],
        ]]);
        $this->assertSame(1, Artisan::call('legacy:load-orders', ['snapshot' => $file, '--apply' => true]));
        $this->assertSame(0, DB::table('quotations')->count());
        $this->assertSame(3, DB::table('legacy_migration_ids')->count());
    }

    public function test_ambiguous_supplier_lineage_blocks_entire_batch(): void
    {
        $file = $this->snapshot(['po_customer_details' => [
            ['id' => 30, 'po_customer_id' => 20, 'product_id' => 7, 'quantity' => 10],
            ['id' => 31, 'po_customer_id' => 20, 'product_id' => 7, 'quantity' => 4],
        ]]);
        $this->assertSame(1, Artisan::call('legacy:load-orders', ['snapshot' => $file, '--apply' => true]));
        $this->assertSame(0, DB::table('po_customers')->count());
        $this->assertSame(3, DB::table('legacy_migration_ids')->count());
    }
}
