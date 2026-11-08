<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyFulfilmentImportFunctionalTest extends TestCase
{
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::create('legacy_migration_ids', function (Blueprint $t) {
            $t->id(); $t->string('source_database'); $t->string('source_table');
            $t->unsignedBigInteger('source_id'); $t->string('target_table');
            $t->unsignedBigInteger('target_id'); $t->string('source_row_hash'); $t->timestamps();
            $t->unique(['source_database', 'source_table', 'source_id']);
        });
        foreach (['products', 'po_customers', 'po_suppliers', 'po_supplier_details'] as $name) {
            Schema::create($name, fn (Blueprint $t) => $t->id());
        }
        Schema::create('po_customer_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('po_customer_id'); $t->unsignedBigInteger('product_id');
        });
        Schema::create('goods_receipts', function (Blueprint $t) {
            $t->id(); $t->string('receipt_number')->unique(); $t->unsignedBigInteger('po_supplier_id');
        });
        Schema::create('goods_receipt_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('goods_receipt_id');
            $t->unsignedBigInteger('po_supplier_detail_id'); $t->unsignedBigInteger('product_id');
        });
        Schema::create('invoice_customers', function (Blueprint $t) {
            $t->id(); $t->string('invoice_number')->unique(); $t->unsignedBigInteger('po_customer_id');
        });
        Schema::create('invoice_customer_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('invoice_customer_id');
            $t->unsignedBigInteger('product_id'); $t->unsignedBigInteger('po_customer_detail_id');
        });
        Schema::create('delivery_orders', function (Blueprint $t) {
            $t->id(); $t->string('do_number')->unique(); $t->unsignedBigInteger('po_customer_id');
            $t->string('status')->default('pending');
        });
        Schema::create('delivery_order_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('delivery_order_id');
            $t->unsignedBigInteger('product_id'); $t->unsignedBigInteger('po_customer_detail_id');
        });
        Schema::create('document_receipts', function (Blueprint $t) {
            $t->id(); $t->string('receipt_number'); $t->unsignedBigInteger('po_customer_id');
        });
        Schema::create('document_receipt_delivery_orders', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('document_receipt_id');
            $t->unsignedBigInteger('delivery_order_id'); $t->timestamps();
        });
        Schema::create('document_receipt_invoices', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('document_receipt_id');
            $t->unsignedBigInteger('invoice_customer_id'); $t->timestamps();
        });
        foreach ([['products', 7, 900], ['po_customers', 20, 800],
            ['po_suppliers', 40, 700], ['po_supplier_details', 50, 600]] as [$table, $old, $new]) {
            DB::table($table)->insert(['id' => $new]);
            DB::table('legacy_migration_ids')->insert([
                'source_database' => 'smartlo1_erp_app', 'source_table' => $table,
                'source_id' => $old, 'target_table' => $table, 'target_id' => $new,
                'source_row_hash' => str_repeat('a', 64), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('po_customer_details')->insert(['id' => 500, 'po_customer_id' => 800, 'product_id' => 900]);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) @unlink($file);
        parent::tearDown();
    }

    private function snapshot(array $changes = []): string
    {
        $tables = [
            'goods_receipts' => [['id' => 60, 'receipt_number' => 'GR-60', 'po_supplier_id' => 40]],
            'goods_receipt_details' => [['id' => 61, 'goods_receipt_id' => 60, 'po_supplier_detail_id' => 50, 'product_id' => 7]],
            'invoice_customers' => [['id' => 70, 'invoice_number' => 'INV-70', 'po_customer_id' => 20]],
            'invoice_customer_details' => [['id' => 71, 'invoice_customer_id' => 70, 'product_id' => 7]],
            'delivery_orders' => [['id' => 80, 'do_number' => 'DO-80', 'po_customer_id' => 20, 'status' => 'pending']],
            'delivery_order_details' => [['id' => 81, 'delivery_order_id' => 80, 'product_id' => 7]],
            'document_receipts' => [['id' => 90, 'receipt_number' => 'DR-90', 'delivery_order_id' => 80, 'invoice_number' => 'INV-70']],
        ];
        foreach ($changes as $table => $rows) $tables[$table] = $rows;
        $file = tempnam(sys_get_temp_dir(), 'bgs_g499_');
        $this->files[] = $file;
        file_put_contents($file, json_encode(['database' => 'smartlo1_erp_app', 'tables' => $tables], JSON_THROW_ON_ERROR));
        return $file;
    }

    public function test_memory_apply_links_all_documents_and_reapply_is_idempotent(): void
    {
        $path = $this->snapshot();
        $this->assertSame(0, Artisan::call('legacy:load-fulfilment', ['snapshot' => $path, '--apply' => true]));
        $this->assertSame(7, DB::table('legacy_migration_ids')->where('source_table', '!=', 'products')
            ->whereNotIn('source_table', ['po_customers', 'po_suppliers', 'po_supplier_details'])->count());
        $this->assertSame(1, DB::table('document_receipt_delivery_orders')->count());
        $this->assertSame(1, DB::table('document_receipt_invoices')->count());
        $this->assertSame(500, (int) DB::table('delivery_order_details')->value('po_customer_detail_id'));
        $this->assertSame(500, (int) DB::table('invoice_customer_details')->value('po_customer_detail_id'));
        $this->assertSame(0, Artisan::call('legacy:load-fulfilment', ['snapshot' => $path, '--apply' => true]));
        $this->assertSame(1, DB::table('document_receipts')->count());
    }

    public function test_dry_run_rolls_back_documents_and_registry(): void
    {
        $this->assertSame(0, Artisan::call('legacy:load-fulfilment', ['snapshot' => $this->snapshot()]));
        $this->assertSame(0, DB::table('invoice_customers')->count());
        $this->assertSame(0, DB::table('document_receipts')->count());
        $this->assertSame(4, DB::table('legacy_migration_ids')->count());
    }

    public function test_unmatched_document_receipt_invoice_fails_closed_and_rolls_back(): void
    {
        $path = $this->snapshot(['document_receipts' => [[
            'id' => 90, 'receipt_number' => 'DR-90', 'delivery_order_id' => 80,
            'invoice_number' => 'INV-MISSING',
        ]]]);
        $this->assertSame(1, Artisan::call('legacy:load-fulfilment', ['snapshot' => $path, '--apply' => true]));
        $this->assertSame(0, DB::table('goods_receipts')->count());
        $this->assertSame(0, DB::table('document_receipts')->count());
        $this->assertSame(4, DB::table('legacy_migration_ids')->count());
    }

    public function test_orphan_invoice_detail_fails_closed_and_rolls_back(): void
    {
        $path = $this->snapshot(['invoice_customer_details' => [[
            'id' => 71, 'invoice_customer_id' => 6, 'product_id' => 7,
        ]]]);
        $this->assertSame(1, Artisan::call('legacy:load-fulfilment', ['snapshot' => $path, '--apply' => true]));
        $this->assertSame(0, DB::table('invoice_customers')->count());
        $this->assertSame(4, DB::table('legacy_migration_ids')->count());
    }
}
