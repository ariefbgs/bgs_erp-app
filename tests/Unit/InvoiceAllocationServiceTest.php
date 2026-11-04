<?php

namespace Tests\Unit;

use App\Models\InvoiceCustomer;
use App\Models\PoCustomer;
use App\Services\InvoiceAllocationService;
use Illuminate\Support\Collection;
use Tests\TestCase;
use RuntimeException;

class InvoiceAllocationServiceTest extends TestCase
{
    private InvoiceAllocationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new InvoiceAllocationService();
    }

    public function test_dp_amount_is_authoritative_when_positive(): void
    {
        $invoice = new InvoiceCustomer();

        $invoice->subtotal = 1000000;
        $invoice->dp_amount = 250000;

        $this->assertSame(
            250000.0,
            $this->service->invoiceAllocatedAmount($invoice)
        );
    }

    public function test_subtotal_is_used_when_dp_amount_is_zero(): void
    {
        $invoice = new InvoiceCustomer();

        $invoice->subtotal = 1000000;
        $invoice->dp_amount = 0;

        $this->assertSame(
            1000000.0,
            $this->service->invoiceAllocatedAmount($invoice)
        );
    }

    public function test_subtotal_is_used_when_dp_amount_is_null(): void
    {
        $invoice = new InvoiceCustomer();

        $invoice->subtotal = 750000;
        $invoice->dp_amount = null;

        $this->assertSame(
            750000.0,
            $this->service->invoiceAllocatedAmount($invoice)
        );
    }

    public function test_amount_allocation_is_independent_from_invoice_detail_quantity(): void
    {
        $invoice = new InvoiceCustomer();

        $invoice->subtotal = 5000000;
        $invoice->dp_amount = 1500000;

        $allocated = $this->service->invoiceAllocatedAmount($invoice);

        $this->assertSame(1500000.0, $allocated);

        $this->assertNotSame(
            (float) $invoice->subtotal,
            $allocated
        );
    }

    public function test_line_balance_classifies_uninvoiced_partial_and_fully_invoiced(): void
    {
        $service = new class extends InvoiceAllocationService {
            public Collection $fakeInvoiced;

            public function invoicedQuantityByPoDetail(
                PoCustomer $poCustomer,
                ?int $excludeInvoiceId = null
            ): Collection {
                return $this->fakeInvoiced;
            }
        };

        $service->fakeInvoiced = collect([
            101 => 0,
            102 => 2,
            103 => 5,
        ]);

        $po = $this->fakePo([
            $this->fakeDetail(101, 1001, 4, 100000),
            $this->fakeDetail(102, 1002, 5, 200000),
            $this->fakeDetail(103, 1003, 5, 300000),
        ]);

        $lines = $service
            ->lineBalances($po)
            ->keyBy('po_customer_detail_id');

        $this->assertSame(
            'UNINVOICED',
            $lines[101]['state']
        );

        $this->assertSame(
            4,
            $lines[101]['remaining_quantity']
        );

        $this->assertSame(
            'PARTIAL',
            $lines[102]['state']
        );

        $this->assertSame(
            3,
            $lines[102]['remaining_quantity']
        );

        $this->assertSame(
            'FULLY_INVOICED',
            $lines[103]['state']
        );

        $this->assertSame(
            0,
            $lines[103]['remaining_quantity']
        );
    }

    public function test_item_balance_counts_each_line_state(): void
    {
        $service = new class extends InvoiceAllocationService {
            public Collection $fakeInvoiced;

            public function invoicedQuantityByPoDetail(
                PoCustomer $poCustomer,
                ?int $excludeInvoiceId = null
            ): Collection {
                return $this->fakeInvoiced;
            }
        };

        $service->fakeInvoiced = collect([
            201 => 0,
            202 => 1,
            203 => 2,
        ]);

        $po = $this->fakePo([
            $this->fakeDetail(201, 2001, 2, 100000),
            $this->fakeDetail(202, 2002, 2, 100000),
            $this->fakeDetail(203, 2003, 2, 100000),
        ]);

        $balance = $service->itemBalance($po);

        $this->assertSame(3, $balance['total_items']);
        $this->assertSame(1, $balance['uninvoiced_items']);
        $this->assertSame(1, $balance['partial_items']);
        $this->assertSame(1, $balance['fully_invoiced_items']);
        $this->assertSame(2, $balance['remaining_items']);
    }

    public function test_resolve_requested_lines_supports_partial_item_subset_and_partial_quantity(): void
    {
        $service = new class extends InvoiceAllocationService {
            public Collection $fakeInvoiced;

            public function invoicedQuantityByPoDetail(
                PoCustomer $poCustomer,
                ?int $excludeInvoiceId = null
            ): Collection {
                return $this->fakeInvoiced;
            }
        };

        $service->fakeInvoiced = collect([
            301 => 0,
            302 => 0,
        ]);

        $po = $this->fakePo([
            $this->fakeDetail(301, 3001, 10, 125000),
            $this->fakeDetail(302, 3002, 5, 50000),
        ]);

        $resolved = $service->resolveRequestedLines(
            $po,
            [
                [
                    'po_customer_detail_id' => 301,
                    'quantity' => 2,
                ],
            ]
        );

        $this->assertCount(1, $resolved);

        $this->assertSame(
            301,
            $resolved[0]['po_customer_detail_id']
        );

        $this->assertSame(
            3001,
            $resolved[0]['product_id']
        );

        $this->assertSame(
            2,
            $resolved[0]['quantity']
        );

        $this->assertSame(
            125000.0,
            $resolved[0]['unit_price']
        );

        $this->assertSame(
            250000.0,
            $resolved[0]['subtotal']
        );
    }

    public function test_resolve_requested_lines_rejects_quantity_above_remaining(): void
    {
        $service = new class extends InvoiceAllocationService {
            public Collection $fakeInvoiced;

            public function invoicedQuantityByPoDetail(
                PoCustomer $poCustomer,
                ?int $excludeInvoiceId = null
            ): Collection {
                return $this->fakeInvoiced;
            }
        };

        $service->fakeInvoiced = collect([
            401 => 8,
        ]);

        $po = $this->fakePo([
            $this->fakeDetail(401, 4001, 10, 100000),
        ]);

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Invoice quantity exceeds remaining quantity'
        );

        $service->resolveRequestedLines(
            $po,
            [
                [
                    'po_customer_detail_id' => 401,
                    'quantity' => 3,
                ],
            ]
        );
    }

    public function test_resolve_requested_lines_rejects_duplicate_po_detail_inside_same_invoice(): void
    {
        $service = new class extends InvoiceAllocationService {
            public Collection $fakeInvoiced;

            public function invoicedQuantityByPoDetail(
                PoCustomer $poCustomer,
                ?int $excludeInvoiceId = null
            ): Collection {
                return $this->fakeInvoiced;
            }
        };

        $service->fakeInvoiced = collect([
            501 => 0,
        ]);

        $po = $this->fakePo([
            $this->fakeDetail(501, 5001, 10, 100000),
        ]);

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Duplicate PO Customer detail 501 in one invoice.'
        );

        $service->resolveRequestedLines(
            $po,
            [
                [
                    'po_customer_detail_id' => 501,
                    'quantity' => 2,
                ],
                [
                    'po_customer_detail_id' => 501,
                    'quantity' => 1,
                ],
            ]
        );
    }

    private function fakePo(array $details): PoCustomer
    {
        $po = new PoCustomer();

        $po->id = 999;

        $po->setRelation(
            'details',
            collect($details)
        );

        return $po;
    }

    private function fakeDetail(
        int $id,
        int $productId,
        int $quantity,
        float $unitPrice
    ): object {
        return (object) [
            'id' => $id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $quantity * $unitPrice,
        ];
    }

    public function test_percentage_allocation_uses_original_po_commercial_amount(): void
    {
        $po = $this->strategyPo(2000000.00);

        $service = $this->strategyService(null, 2000000.00);

        $this->assertSame(
            600000.00,
            $service->percentageAllocationAmount($po, 30.0)
        );
    }

    public function test_percentage_allocation_rejects_amount_above_remaining_capacity(): void
    {
        $po = $this->strategyPo(2000000.00);

        $service = $this->strategyService(null, 1000000.00);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Invoice allocated amount exceeds remaining PO Customer amount.'
        );

        $service->percentageAllocationAmount($po, 100.0);
    }

    public function test_first_percentage_strategy_is_allowed(): void
    {
        $po = $this->strategyPo();

        $service = $this->strategyService(null);

        $service->assertStrategyAvailable($po, 'PERCENTAGE');

        $this->assertTrue(true);
    }

    public function test_first_item_qty_strategy_is_allowed(): void
    {
        $po = $this->strategyPo();

        $service = $this->strategyService(null);

        $service->assertStrategyAvailable($po, 'ITEM_QTY');

        $this->assertTrue(true);
    }

    public function test_same_percentage_strategy_is_allowed(): void
    {
        $po = $this->strategyPo();

        $service = $this->strategyService('PERCENTAGE');

        $service->assertStrategyAvailable($po, 'PERCENTAGE');

        $this->assertTrue(true);
    }

    public function test_same_item_qty_strategy_is_allowed(): void
    {
        $po = $this->strategyPo();

        $service = $this->strategyService('ITEM_QTY');

        $service->assertStrategyAvailable($po, 'ITEM_QTY');

        $this->assertTrue(true);
    }

    public function test_percentage_to_item_qty_strategy_mix_is_rejected(): void
    {
        $po = $this->strategyPo();

        $service = $this->strategyService('PERCENTAGE');

        $this->expectException(\RuntimeException::class);

        $service->assertStrategyAvailable($po, 'ITEM_QTY');
    }

    public function test_item_qty_to_percentage_strategy_mix_is_rejected(): void
    {
        $po = $this->strategyPo();

        $service = $this->strategyService('ITEM_QTY');

        $this->expectException(\RuntimeException::class);

        $service->assertStrategyAvailable($po, 'PERCENTAGE');
    }

    public function test_percentage_completion_is_amount_only(): void
    {
        $po = $this->strategyPo();

        $service = $this->completionStrategyService(
            'PERCENTAGE',
            [
                'amount' => ['remaining_amount' => 0.0, 'invoiced_amount' => 1000000.0],
                'lines' => collect([
                    ['remaining_quantity' => 5.0, 'invoiced_quantity' => 5.0],
                ]),
                'items' => ['remaining_items' => 1],
            ]
        );

        $state = $service->completionState($po);

        $this->assertSame('PERCENTAGE', $state['strategy']);
        $this->assertTrue($state['completed']);
    }

    public function test_item_qty_completion_requires_all_dimensions(): void
    {
        $po = $this->strategyPo();

        $service = $this->completionStrategyService(
            'ITEM_QTY',
            [
                'amount' => ['remaining_amount' => 0.0, 'invoiced_amount' => 1000000.0],
                'lines' => collect([
                    ['remaining_quantity' => 5.0, 'invoiced_quantity' => 5.0],
                ]),
                'items' => ['remaining_items' => 1],
            ]
        );

        $state = $service->completionState($po);

        $this->assertSame('ITEM_QTY', $state['strategy']);
        $this->assertFalse($state['completed']);
    }

    private function strategyPo(float $commercialAmount = 1000000.00): PoCustomer
    {
        $po = new PoCustomer();
        $po->id = 999001;

        $detail = new \App\Models\PoCustomerDetail();
        $detail->id = 999101;
        $detail->po_customer_id = 999001;
        $detail->quantity = 10;
        $detail->unit_price = $commercialAmount / 10;
        $detail->subtotal = $commercialAmount;

        $po->setRelation(
            'details',
            new \Illuminate\Database\Eloquent\Collection([$detail])
        );

        return $po;
    }

    private function strategyService(
        ?string $activeStrategy,
        float $remainingAmount = 1000000.00
    ): InvoiceAllocationService {
        return new class($activeStrategy, $remainingAmount)
            extends InvoiceAllocationService {

            public function __construct(
                private ?string $testStrategy,
                private float $testRemainingAmount
            ) {
            }

            public function activeNormalStrategy(
                PoCustomer $poCustomer,
                ?int $excludeInvoiceId = null
            ): ?string {
                return $this->testStrategy;
            }

            public function amountBalance(
                PoCustomer $poCustomer,
                ?int $excludeInvoiceId = null
            ): array {
                $total = $this->poCommercialAmount($poCustomer);

                return [
                    'total' => $total,
                    'allocated' => max(
                        0.0,
                        $total - $this->testRemainingAmount
                    ),
                    'remaining_amount' => $this->testRemainingAmount,
                ];
            }
        };
    }

    private function completionStrategyService(
        ?string $activeStrategy,
        array $snapshot
    ): InvoiceAllocationService {
        return new class($activeStrategy, $snapshot)
            extends InvoiceAllocationService {

            public function __construct(
                private ?string $testStrategy,
                private array $testSnapshot
            ) {
            }

            public function activeNormalStrategy(
                PoCustomer $poCustomer,
                ?int $excludeInvoiceId = null
            ): ?string {
                return $this->testStrategy;
            }

            public function snapshot(
                PoCustomer $poCustomer,
                ?int $excludeInvoiceId = null
            ): array {
                return $this->testSnapshot;
            }
        };
    }
}
