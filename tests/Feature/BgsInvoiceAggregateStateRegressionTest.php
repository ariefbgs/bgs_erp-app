<?php

namespace Tests\Feature;

use App\Models\PoCustomer;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BgsInvoiceAggregateStateRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (app()->environment() !== 'testing') {
            throw new \RuntimeException('SAFETY BLOCK: APP_ENV must be testing.');
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
        preg_match("/enum\('([^']+)'/", strtolower($type), $match);

        return $match[1] ?? null;
    }

    private function genericRow(string $table, array $overrides = []): array
    {
        $data = [];

        foreach ($this->columns($table) as $column) {
            $name = $column->COLUMN_NAME;
            $type = strtolower($column->COLUMN_TYPE);
            $extra = strtolower($column->EXTRA ?? '');

            if (str_contains($extra, 'auto_increment')) {
                continue;
            }

            if (array_key_exists($name, $overrides)) {
                $data[$name] = $overrides[$name];
                continue;
            }

            if ($name === 'created_at' || $name === 'updated_at') {
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

        /*
         * Explicit overrides must also work for nullable/default columns.
         */
        foreach ($overrides as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

    private function createCustomer(): int
    {
        $data = $this->genericRow('customers');

        return DB::table('customers')->insertGetId($data);
    }

    private function createPo(
        float $total,
        string $invoiceStatus = 'issue yet'
    ): int {
        $customerId = $this->createCustomer();

        $data = $this->genericRow('po_customers', [
            'customer_id'    => $customerId,
            'quotation_id'   => null,
            'po_number'      => 'TEST-PO-' . uniqid(),
            'po_date'        => now()->toDateString(),
            'status'         => 'received',
            'invoice_status' => $invoiceStatus,
            'total'          => $total,
        ]);

        return DB::table('po_customers')->insertGetId($data);
    }

    private function createInvoice(
        int $poCustomerId,
        float $total,
        string $status = 'partial',
        string $paymentStatus = 'sent'
    ): int {
        $data = $this->genericRow('invoice_customers', [
            'po_customer_id' => $poCustomerId,
            'invoice_number' => 'TEST-INV-' . uniqid(),
            'invoice_date'   => now()->toDateString(),
            'status'         => $status,
            'invoice_status' => 'issue yet',
            'payment_status' => $paymentStatus,
            'total'          => $total,
            'remaining_amount' => 0,
        ]);

        return DB::table('invoice_customers')->insertGetId($data);
    }

    public function test_partial_invoice_sets_po_invoice_status_partial(): void
    {
        $poId = $this->createPo(4075920);

        $this->createInvoice(
            $poId,
            2037960,
            'partial',
            'sent'
        );

        $po = PoCustomer::findOrFail($poId);

        $po->updateInvoiceStatus();
        $po->refresh();

        $this->assertSame('partial', $po->invoice_status);
        $this->assertEquals(2037960, (float) $po->total_invoiced);
        $this->assertEquals(2037960, (float) $po->remaining);
    }

    public function test_cancelled_invoice_status_is_excluded_from_coverage(): void
    {
        $poId = $this->createPo(1000000);

        $this->createInvoice(
            $poId,
            500000,
            'cancelled',
            'cancelled'
        );

        $po = PoCustomer::findOrFail($poId);

        $po->updateInvoiceStatus();
        $po->refresh();

        $this->assertSame('issue yet', $po->invoice_status);
        $this->assertEquals(0, (float) $po->total_invoiced);
        $this->assertEquals(1000000, (float) $po->remaining);
    }

    public function test_full_active_invoice_coverage_sets_completed(): void
    {
        $poId = $this->createPo(1000000);

        $this->createInvoice(
            $poId,
            600000,
            'partial',
            'sent'
        );

        $this->createInvoice(
            $poId,
            400000,
            'completed',
            'paid'
        );

        $po = PoCustomer::findOrFail($poId);

        $po->updateInvoiceStatus();
        $po->refresh();

        $this->assertSame('completed', $po->invoice_status);
        $this->assertEquals(1000000, (float) $po->total_invoiced);
        $this->assertEquals(0, (float) $po->remaining);
        $this->assertTrue($po->is_fully_invoiced);
    }
}