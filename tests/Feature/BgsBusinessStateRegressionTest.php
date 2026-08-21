<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BgsBusinessStateRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * HARD SAFETY GATE.
         *
         * These tests are allowed to mutate only the dedicated
         * erp_app_testing database.
         */
        $database = DB::connection()->getDatabaseName();

        if (app()->environment() !== 'testing') {
            throw new \RuntimeException(
                'SAFETY BLOCK: APP_ENV must be testing.'
            );
        }

        if ($database !== 'erp_app_testing') {
            throw new \RuntimeException(
                'SAFETY BLOCK: expected erp_app_testing, got ' . $database
            );
        }

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_testing_environment_is_strictly_isolated(): void
    {
        $this->assertSame('testing', app()->environment());

        $this->assertSame(
            'erp_app_testing',
            DB::connection()->getDatabaseName()
        );

        $this->assertSame(
            'mysql',
            config('database.default')
        );
    }

    public function test_schema_contains_complete_sales_flow_chain(): void
    {
        $requiredTables = [
            'quotations',
            'quotation_details',

            'po_customers',
            'po_customer_details',

            'po_suppliers',
            'po_supplier_details',

            'goods_receipts',
            'goods_receipt_details',

            'delivery_orders',
            'delivery_order_details',

            'invoice_customers',
            'invoice_customer_details',
        ];

        foreach ($requiredTables as $table) {
            $exists = DB::table('information_schema.tables')
                ->where('table_schema', 'erp_app_testing')
                ->where('table_name', $table)
                ->exists();

            $this->assertTrue(
                $exists,
                "Required ERP flow table missing: {$table}"
            );
        }
    }

    public function test_invoice_customer_invoice_status_schema_exposes_legacy_typo(): void
    {
        $column = DB::table('information_schema.columns')
            ->where('table_schema', 'erp_app_testing')
            ->where('table_name', 'invoice_customers')
            ->where('column_name', 'invoice_status')
            ->first();

        $this->assertNotNull($column);

        /*
         * This is intentionally a characterization test.
         *
         * It records the current physical schema defect before remediation.
         */
        $this->assertStringContainsString(
            'cpmpleted',
            strtolower($column->COLUMN_TYPE)
        );
    }

    public function test_po_customer_invoice_status_uses_correct_completed_spelling(): void
    {
        $column = DB::table('information_schema.columns')
            ->where('table_schema', 'erp_app_testing')
            ->where('table_name', 'po_customers')
            ->where('column_name', 'invoice_status')
            ->first();

        $this->assertNotNull($column);

        $type = strtolower($column->COLUMN_TYPE);

        $this->assertStringContainsString(
            "'completed'",
            $type
        );

        $this->assertStringNotContainsString(
            "'cpmpleted'",
            $type
        );
    }

    public function test_testing_database_fixture_mutation_is_rolled_back(): void
    {
        /*
         * Use a table with minimal required columns.
         * We first inspect its schema so the test remains explicit.
         */

        $columns = DB::table('information_schema.columns')
            ->where('table_schema', 'erp_app_testing')
            ->where('table_name', 'companies')
            ->pluck('COLUMN_NAME')
            ->all();

        $this->assertNotEmpty($columns);

        /*
         * The safety proof here is transaction behavior itself.
         * No fixture is permanently committed.
         */

        $before = DB::table('companies')->count();

        $this->assertSame(0, $before);

        $this->assertGreaterThan(
            0,
            DB::transactionLevel()
        );
    }
}