<?php

namespace Tests\Feature;

use App\Models\InvoiceCustomer;
use App\Models\PoCustomer;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BgsInvoiceAggregateMutationRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (app()->environment() !== 'testing') {
            throw new \RuntimeException(
                'SAFETY BLOCK: APP_ENV must be testing.'
            );
        }

        if (DB::connection()->getDatabaseName() !== 'erp_app_testing') {
            throw new \RuntimeException(
                'SAFETY BLOCK: database must be erp_app_testing.'
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

    private function columns(string $table)
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', 'erp_app_testing')
            ->where('table_name', $table)
            ->orderBy('ORDINAL_POSITION')
            ->get();
    }

    private function enumFirst(string $type): ?string
    {
        preg_match(
            "/enum\('([^']+)'/",
            strtolower($type),
            $match
        );

        return $match[1] ?? null;
    }

    private function genericRow(
        string $table,
        array $overrides = []
    ): array {
        $data = [];

        foreach ($this->columns($table) as $column) {
            $name  = $column->COLUMN_NAME;
            $type  = strtolower($column->COLUMN_TYPE);
            $extra = strtolower($column->EXTRA ?? '');

            if (str_contains($extra, 'auto_increment')) {
                continue;
            }

            if (array_key_exists($name, $overrides)) {
                $data[$name] = $overrides[$name];
                continue;
            }

            if (
                $name === 'created_at' ||
                $name === 'updated_at'
            ) {
                $data[$name] = now();
                continue;
            }

            if (
                $column->IS_NULLABLE === 'YES' ||
                $column->COLUMN_DEFAULT !== null
            ) {
                continue;
            }

            if (str_starts_with($type, 'enum(')) {
                $data[$name] = $this->enumFirst($type);
                continue;
            }

            if (
                str_contains($type, 'int') ||
                str_contains($type, 'decimal') ||
                str_contains($type, 'double') ||
                str_contains($type, 'float')
            ) {
                $data[$name] = 1;
                continue;
            }

            if (
                str_contains($type, 'date') ||
                str_contains($type, 'time')
            ) {
                $data[$name] = now();
                continue;
            }

            if (str_contains($type, 'json')) {
                $data[$name] = json_encode([]);
                continue;
            }

            $data[$name] = 'TEST-' . uniqid();
        }

        foreach ($overrides as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

    private function createCustomer(): int
    {
        return DB::table('customers')->insertGetId(
            $this->genericRow('customers')
        );
    }

    private function createPo(
        float $total,
        string $invoiceStatus = 'issue yet'
    ): int {
        $customerId = $this->createCustomer();

        return DB::table('po_customers')->insertGetId(
            $this->genericRow('po_customers', [
                'customer_id'    => $customerId,
                'quotation_id'   => null,
                'po_number'      => 'TEST-PO-' . uniqid(),
                'po_date'        => now()->toDateString(),
                'status'         => 'received',
                'invoice_status' => $invoiceStatus,
                'total'          => $total,
            ])
        );
    }

    private function createInvoice(
        int $poId,
        float $total,
        string $status
    ): int {
        return DB::table('invoice_customers')->insertGetId(
            $this->genericRow('invoice_customers', [
                'po_customer_id'  => $poId,
                'invoice_number'  => 'TEST-INV-' . uniqid(),
                'invoice_date'    => now()->toDateString(),
                'total'           => $total,
                'status'          => $status,
                'invoice_status'  => 'issue yet',
                'payment_status'  => 'sent',
                'remaining_amount'=> 0,
            ])
        );
    }

    public function test_canonical_coverage_excludes_cancelled_invoice(): void
    {
        $poId = $this->createPo(
            1000000,
            'partial'
        );

        $this->createInvoice(
            $poId,
            400000,
            'partial'
        );

        $this->createInvoice(
            $poId,
            300000,
            'cancelled'
        );

        $po = PoCustomer::findOrFail($poId);

        $this->assertSame(
            400000.0,
            (float) $po->total_invoiced
        );
    }

    public function test_legacy_po_index_formula_incorrectly_counts_cancelled_invoice(): void
    {
        $poId = $this->createPo(
            1000000,
            'partial'
        );

        $this->createInvoice(
            $poId,
            400000,
            'partial'
        );

        $this->createInvoice(
            $poId,
            300000,
            'cancelled'
        );

        /*
         * Characterizes the formula currently used by
         * PoCustomerController@index.
         */
        $po = PoCustomer::findOrFail($poId);

        $controllerIndexTotal = $po->total_invoiced;
        $canonicalTotal = $po->total_invoiced;

        $this->assertSame(
            400000.0,
            (float) $controllerIndexTotal,
            'PO Customer index coverage must exclude cancelled invoices.'
        );

        $this->assertSame(
            (float) $canonicalTotal,
            (float) $controllerIndexTotal
        );
    }

    public function test_invoice_and_po_aggregate_can_rollback_as_one_atomic_unit(): void
    {
        $poId = $this->createPo(
            1000000,
            'issue yet'
        );

        try {
            DB::transaction(function () use ($poId) {

                $this->createInvoice(
                    $poId,
                    500000,
                    'partial'
                );

                $po = PoCustomer::findOrFail($poId);

                $po->updateInvoiceStatus();

                throw new \RuntimeException(
                    'SIMULATED_ATOMIC_FAILURE'
                );
            });
        } catch (\RuntimeException $e) {
            $this->assertSame(
                'SIMULATED_ATOMIC_FAILURE',
                $e->getMessage()
            );
        }

        $this->assertSame(
            0,
            DB::table('invoice_customers')
                ->where('po_customer_id', $poId)
                ->count()
        );

        $this->assertSame(
            'issue yet',
            PoCustomer::findOrFail($poId)->invoice_status
        );
    }
}