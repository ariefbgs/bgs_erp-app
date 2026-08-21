<?php

namespace Tests\Feature;

use App\Http\Controllers\InvoiceCustomerController;
use App\Models\InvoiceCustomer;
use App\Models\PoCustomer;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BgsInvoiceCustomerRuntimeAtomicityTest extends TestCase
{
    private const TRIGGER =
        'bgs_test_fail_po_invoice_status_update';

    protected function setUp(): void
    {
        parent::setUp();

        if (app()->environment() !== 'testing') {
            throw new \RuntimeException(
                'SAFETY BLOCK: APP_ENV must be testing.'
            );
        }

        if (
            DB::connection()->getDatabaseName()
            !== 'erp_app_testing'
        ) {
            throw new \RuntimeException(
                'SAFETY BLOCK: database must be erp_app_testing.'
            );
        }

        $this->dropFailureTrigger();

        DB::table('invoice_customers')->delete();
        DB::table('po_customers')->delete();
        DB::table('customers')->delete();
    }

    protected function tearDown(): void
    {
        $this->dropFailureTrigger();

        DB::table('invoice_customers')->delete();
        DB::table('po_customers')->delete();
        DB::table('customers')->delete();

        parent::tearDown();
    }

    private function dropFailureTrigger(): void
    {
        DB::unprepared(
            'DROP TRIGGER IF EXISTS ' . self::TRIGGER
        );
    }

    private function installFailureTrigger(): void
    {
        DB::unprepared(
            "
            CREATE TRIGGER " . self::TRIGGER . "
            BEFORE UPDATE ON po_customers
            FOR EACH ROW
            BEGIN
                IF NOT (NEW.invoice_status <=> OLD.invoice_status) THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT =
                        'SIMULATED_PO_INVOICE_STATUS_FAILURE';
                END IF;
            END
            "
        );
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

    private function createPo(): int
    {
        $customerId = $this->createCustomer();

        return DB::table('po_customers')->insertGetId(
            $this->genericRow('po_customers', [
                'customer_id'    => $customerId,
                'quotation_id'   => null,
                'po_number'      => 'TEST-PO-' . uniqid(),
                'po_date'        => now()->toDateString(),
                'status'         => 'received',
                'invoice_status' => 'partial',
                'total'          => 1000000,
            ])
        );
    }

    private function createInvoice(int $poId): int
    {
        return DB::table('invoice_customers')->insertGetId(
            $this->genericRow('invoice_customers', [
                'po_customer_id'   => $poId,
                'invoice_number'   => 'TEST-INV-' . uniqid(),
                'invoice_date'     => now()->toDateString(),
                'subtotal'         => 400000,
                'total'            => 400000,
                'status'           => 'partial',
                'payment_status'   => 'sent',
                'invoice_status'   => 'issue yet',
                'remaining_amount' => 0,
            ])
        );
    }

    public function test_cancel_rolls_back_when_po_aggregate_sync_fails(): void
    {
        $poId = $this->createPo();
        $invoiceId = $this->createInvoice($poId);

        $beforeInvoice = InvoiceCustomer::findOrFail($invoiceId);
        $beforePo = PoCustomer::findOrFail($poId);

        $this->assertSame(
            'partial',
            $beforeInvoice->status
        );

        $this->assertSame(
            'partial',
            $beforePo->invoice_status
        );

        /*
         * If the invoice is cancelled, canonical coverage becomes zero.
         * updateInvoiceStatus() therefore attempts:
         *
         * partial -> issue yet
         *
         * The temporary DB trigger rejects that update.
         */
        $this->installFailureTrigger();

        $controller = app(
            InvoiceCustomerController::class
        );

        $response = $controller->cancel($invoiceId);

        $this->assertNotNull($response);

        /*
         * Most important proof:
         * invoice mutation before synchronization must have rolled back.
         */
        $invoiceAfter = InvoiceCustomer::findOrFail($invoiceId);
        $poAfter = PoCustomer::findOrFail($poId);

        $this->assertSame(
            'partial',
            $invoiceAfter->status,
            'Invoice status mutation was not rolled back.'
        );

        $this->assertSame(
            'partial',
            $poAfter->invoice_status,
            'PO aggregate state changed despite synchronization failure.'
        );

        $this->assertSame(
            0,
            DB::transactionLevel(),
            'Controller leaked an open DB transaction.'
        );
    }
}