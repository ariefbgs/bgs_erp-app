<?php

namespace App\Services;

use App\Models\InvoiceCustomer;
use App\Models\PoCustomer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceAllocationService
{
    /**
     * Invoice rows that consume allocation capacity.
     *
     * Cancelled invoices release both amount and quantity capacity.
     */
    protected function activeInvoicesQuery(int $poCustomerId, ?int $excludeInvoiceId = null)
    {
        $query = InvoiceCustomer::query()
            ->where('po_customer_id', $poCustomerId)
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereRaw('LOWER(status) <> ?', ['cancelled']);
            })
            ->where(function ($q) {
                $q->whereNull('invoice_status')
                    ->orWhereRaw('LOWER(invoice_status) <> ?', ['cancelled']);
            });

        if ($excludeInvoiceId !== null) {
            $query->where('id', '<>', $excludeInvoiceId);
        }

        return $query;
    }

    /**
     * Commercial pre-tax value of the PO Customer.
     *
     * The PO detail table remains authoritative for product, ordered
     * quantity and unit price.
     */
    public function poCommercialAmount(PoCustomer $poCustomer): float
    {
        $poCustomer->loadMissing('details');

        return round(
            (float) $poCustomer->details->sum(
                fn ($detail) => (float) $detail->quantity * (float) $detail->unit_price
            ),
            2
        );
    }

    /**
     * Amount consumed by an invoice.
     *
     * dp_amount is the explicit allocated pre-tax amount when present.
     * Otherwise the invoice subtotal is the consumed amount.
     *
     * This intentionally keeps amount allocation independent from
     * quantity/item allocation.
     */
    public function invoiceAllocatedAmount(InvoiceCustomer $invoice): float
    {
        $dpAmount = (float) ($invoice->dp_amount ?? 0);

        if ($dpAmount > 0) {
            return round($dpAmount, 2);
        }

        return round((float) ($invoice->subtotal ?? 0), 2);
    }

    /**
     * Amount balance for a PO Customer.
     */
    public function amountBalance(
        PoCustomer $poCustomer,
        ?int $excludeInvoiceId = null
    ): array {
        $poAmount = $this->poCommercialAmount($poCustomer);

        $invoices = $this->activeInvoicesQuery(
            (int) $poCustomer->id,
            $excludeInvoiceId
        )->get();

        $invoicedAmount = round(
            (float) $invoices->sum(
                fn (InvoiceCustomer $invoice) => $this->invoiceAllocatedAmount($invoice)
            ),
            2
        );

        $remainingAmount = round(
            max(0, $poAmount - $invoicedAmount),
            2
        );

        return [
            'po_amount' => $poAmount,
            'invoiced_amount' => $invoicedAmount,
            'remaining_amount' => $remainingAmount,
        ];
    }

    /**
     * Quantity consumed by active invoices, grouped by canonical
     * po_customer_detail_id.
     */
    public function invoicedQuantityByPoDetail(
        PoCustomer $poCustomer,
        ?int $excludeInvoiceId = null
    ): Collection {
        $invoiceIds = $this->activeInvoicesQuery(
            (int) $poCustomer->id,
            $excludeInvoiceId
        )->pluck('id');

        if ($invoiceIds->isEmpty()) {
            return collect();
        }

        return DB::table('invoice_customer_details')
            ->whereIn('invoice_customer_id', $invoiceIds)
            ->whereNotNull('po_customer_detail_id')
            ->groupBy('po_customer_detail_id')
            ->select(
                'po_customer_detail_id',
                DB::raw('SUM(quantity) AS invoiced_quantity')
            )
            ->pluck(
                'invoiced_quantity',
                'po_customer_detail_id'
            );
    }

    /**
     * Quantity + item/line balance.
     *
     * State:
     * - UNINVOICED
     * - PARTIAL
     * - FULLY_INVOICED
     */
    public function lineBalances(
        PoCustomer $poCustomer,
        ?int $excludeInvoiceId = null
    ): Collection {
        $poCustomer->loadMissing('details');

        $invoicedByDetail = $this->invoicedQuantityByPoDetail(
            $poCustomer,
            $excludeInvoiceId
        );

        return $poCustomer->details->map(
            function ($detail) use ($invoicedByDetail) {
                $orderedQty = (int) $detail->quantity;

                $invoicedQty = (int) (
                    $invoicedByDetail->get($detail->id, 0)
                );

                $remainingQty = max(
                    0,
                    $orderedQty - $invoicedQty
                );

                if ($invoicedQty <= 0) {
                    $state = 'UNINVOICED';
                } elseif ($remainingQty <= 0) {
                    $state = 'FULLY_INVOICED';
                } else {
                    $state = 'PARTIAL';
                }

                return [
                    'po_customer_detail_id' => (int) $detail->id,
                    'product_id' => (int) $detail->product_id,
                    'ordered_quantity' => $orderedQty,
                    'invoiced_quantity' => $invoicedQty,
                    'remaining_quantity' => $remainingQty,
                    'unit_price' => round((float) $detail->unit_price, 2),
                    'state' => $state,
                ];
            }
        )->values();
    }

    /**
     * Aggregate item/line balance.
     */
    public function itemBalance(
        PoCustomer $poCustomer,
        ?int $excludeInvoiceId = null
    ): array {
        $lines = $this->lineBalances(
            $poCustomer,
            $excludeInvoiceId
        );

        return [
            'total_items' => $lines->count(),

            'fully_invoiced_items' => $lines
                ->where('state', 'FULLY_INVOICED')
                ->count(),

            'partial_items' => $lines
                ->where('state', 'PARTIAL')
                ->count(),

            'uninvoiced_items' => $lines
                ->where('state', 'UNINVOICED')
                ->count(),

            'remaining_items' => $lines
                ->where('state', '<>', 'FULLY_INVOICED')
                ->count(),
        ];
    }

    /**
     * Complete authoritative allocation snapshot.
     *
     * Intended reusable source for:
     * - Customer Invoice create/edit
     * - Operational Activity Dashboard
     * - Month-End Readiness
     */
    public function snapshot(
        PoCustomer $poCustomer,
        ?int $excludeInvoiceId = null
    ): array {
        $lines = $this->lineBalances(
            $poCustomer,
            $excludeInvoiceId
        );

        $items = [
            'total_items' => $lines->count(),

            'fully_invoiced_items' => $lines
                ->where('state', 'FULLY_INVOICED')
                ->count(),

            'partial_items' => $lines
                ->where('state', 'PARTIAL')
                ->count(),

            'uninvoiced_items' => $lines
                ->where('state', 'UNINVOICED')
                ->count(),

            'remaining_items' => $lines
                ->where('state', '<>', 'FULLY_INVOICED')
                ->count(),
        ];

        return [
            'amount' => $this->amountBalance(
                $poCustomer,
                $excludeInvoiceId
            ),

            'items' => $items,

            'lines' => $lines,
        ];
    }

    /**
     * Resolve the active normal allocation strategy for a PO Customer.
     *
     * Normal strategies:
     * - PERCENTAGE
     * - ITEM_QTY
     *
     * Cancelled invoices are excluded by activeInvoicesQuery().
     *
     * Active legacy NULL allocation_mode is ambiguous and must not be
     * guessed. AMOUNT_ONLY remains an internal/exception allocation and
     * does not establish a normal strategy.
     */
    public function activeNormalStrategy(
        PoCustomer $poCustomer,
        ?int $excludeInvoiceId = null
    ): ?string {
        $activeInvoices = $this->activeInvoicesQuery(
            (int) $poCustomer->id,
            $excludeInvoiceId
        )->get(['id', 'allocation_mode']);

        $hasLegacyNull = $activeInvoices->contains(
            fn (InvoiceCustomer $invoice) => $invoice->allocation_mode === null
        );

        if ($hasLegacyNull) {
            throw new RuntimeException(
                'Active legacy invoice has no allocation strategy and requires manual resolution.'
            );
        }

        $normalStrategies = $activeInvoices
            ->pluck('allocation_mode')
            ->filter(
                fn ($mode) => in_array(
                    $mode,
                    ['PERCENTAGE', 'ITEM_QTY'],
                    true
                )
            )
            ->unique()
            ->values();

        if ($normalStrategies->count() > 1) {
            throw new RuntimeException(
                'PO Customer has conflicting active invoice allocation strategies.'
            );
        }

        return $normalStrategies->first();
    }

    /**
     * Enforce one active normal allocation strategy per PO Customer.
     */
    public function assertStrategyAvailable(
        PoCustomer $poCustomer,
        string $requestedStrategy,
        ?int $excludeInvoiceId = null
    ): void {
        if (! in_array(
            $requestedStrategy,
            ['PERCENTAGE', 'ITEM_QTY'],
            true
        )) {
            throw new RuntimeException(
                'Invalid normal invoice allocation strategy.'
            );
        }

        $activeStrategy = $this->activeNormalStrategy(
            $poCustomer,
            $excludeInvoiceId
        );

        if (
            $activeStrategy !== null &&
            $activeStrategy !== $requestedStrategy
        ) {
            throw new RuntimeException(
                "PO Customer already uses {$activeStrategy} allocation strategy."
            );
        }
    }

    /**
     * Calculate PERCENTAGE allocation from the ORIGINAL PO commercial
     * amount. Current remaining amount is a capacity guard only.
     */
    public function percentageAllocationAmount(
        PoCustomer $poCustomer,
        float $percentage,
        ?int $excludeInvoiceId = null
    ): float {
        if ($percentage <= 0 || $percentage > 100) {
            throw new RuntimeException(
                'Payment percentage must be greater than 0 and not exceed 100.'
            );
        }

        $requestedAmount = round(
            $this->poCommercialAmount($poCustomer) *
                $percentage /
                100,
            2
        );

        $this->assertAmountAvailable(
            $poCustomer,
            $requestedAmount,
            $excludeInvoiceId
        );

        return $requestedAmount;
    }
    /**
     * Resolve authoritative composite invoice completion state.
     *
     * Completion requires all independent allocation dimensions:
     * - amount remaining = 0
     * - quantity remaining = 0 for every canonical PO detail
     * - item remaining = 0
     *
     * Cancelled invoices are already excluded by the balance engine.
     */
    public function completionState(
        PoCustomer $poCustomer,
        ?int $excludeInvoiceId = null
    ): array {
        $snapshot = $this->snapshot(
            $poCustomer,
            $excludeInvoiceId
        );

        $amountRemaining = round(
            (float) $snapshot['amount']['remaining_amount'],
            2
        );

        $quantityRemaining = round(
            (float) $snapshot['lines']->sum('remaining_quantity'),
            6
        );

        $remainingItems = (int) $snapshot['items']['remaining_items'];

        $amountComplete = $amountRemaining <= 0;
        $quantityComplete = $quantityRemaining <= 0;
        $itemComplete = $remainingItems === 0;

        $strategy = $this->activeNormalStrategy(
            $poCustomer,
            $excludeInvoiceId
        );

        if ($strategy === 'PERCENTAGE') {
            $completed = $amountComplete;
        } elseif ($strategy === 'ITEM_QTY') {
            $completed =
                $amountComplete &&
                $quantityComplete &&
                $itemComplete;
        } else {
            // No active normal strategy:
            // retain conservative composite semantics for an untouched PO
            // or internal AMOUNT_ONLY exception state.
            $completed =
                $amountComplete &&
                $quantityComplete &&
                $itemComplete;
        }

        $hasAllocation =
            (float) $snapshot['amount']['invoiced_amount'] > 0 ||
            (float) $snapshot['lines']->sum('invoiced_quantity') > 0;

        if ($strategy === null) {
            $hasAllocation = false;
        }

        if ($completed) {
            $status = 'completed';
        } elseif ($hasAllocation) {
            $status = 'partial';
        } else {
            $status = 'issue yet';
        }

        return [
            'status' => $status,
            'completed' => $completed,
            'strategy' => $strategy,

            'amount_complete' => $amountComplete,
            'quantity_complete' => $quantityComplete,
            'item_complete' => $itemComplete,

            'remaining_amount' => $amountRemaining,
            'remaining_quantity' => $quantityRemaining,
            'remaining_items' => $remainingItems,

            'snapshot' => $snapshot,
        ];
    }
    /**
     * Validate requested invoice lines against authoritative PO detail
     * and current remaining quantity.
     *
     * Expected request:
     *
     * [
     *   [
     *     'po_customer_detail_id' => 123,
     *     'quantity' => 2,
     *   ],
     * ]
     *
     * Browser supplied product_id, unit_price and subtotal are ignored.
     */
    public function resolveRequestedLines(
        PoCustomer $poCustomer,
        array $requestedLines,
        ?int $excludeInvoiceId = null
    ): array {
        $poCustomer->loadMissing('details');

        if (empty($requestedLines)) {
            throw new RuntimeException(
                'At least one invoice item must be selected.'
            );
        }

        $seen = [];

        $balances = $this->lineBalances(
            $poCustomer,
            $excludeInvoiceId
        )->keyBy('po_customer_detail_id');

        $resolved = [];

        foreach ($requestedLines as $line) {
            $poDetailId = isset($line['po_customer_detail_id'])
                ? (int) $line['po_customer_detail_id']
                : 0;

            $quantity = isset($line['quantity'])
                ? (int) $line['quantity']
                : 0;

            if ($poDetailId <= 0) {
                throw new RuntimeException(
                    'Invalid PO Customer detail identity.'
                );
            }

            if (isset($seen[$poDetailId])) {
                throw new RuntimeException(
                    "Duplicate PO Customer detail {$poDetailId} in one invoice."
                );
            }

            $seen[$poDetailId] = true;

            $poDetail = $poCustomer->details->firstWhere(
                'id',
                $poDetailId
            );

            if (!$poDetail) {
                throw new RuntimeException(
                    "PO Customer detail {$poDetailId} does not belong to this PO Customer."
                );
            }

            $balance = $balances->get($poDetailId);

            if (!$balance) {
                throw new RuntimeException(
                    "Unable to resolve invoice balance for PO Customer detail {$poDetailId}."
                );
            }

            if ($quantity <= 0) {
                throw new RuntimeException(
                    "Invoice quantity must be greater than zero for PO Customer detail {$poDetailId}."
                );
            }

            if ($quantity > (int) $balance['remaining_quantity']) {
                throw new RuntimeException(
                    "Invoice quantity exceeds remaining quantity for PO Customer detail {$poDetailId}."
                );
            }

            $unitPrice = round(
                (float) $poDetail->unit_price,
                2
            );

            $subtotal = round(
                $quantity * $unitPrice,
                2
            );

            $resolved[] = [
                'po_customer_detail_id' => (int) $poDetail->id,
                'product_id' => (int) $poDetail->product_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ];
        }

        return $resolved;
    }

    /**
     * Validate independent amount allocation.
     */
    public function assertAmountAvailable(
        PoCustomer $poCustomer,
        float $requestedAmount,
        ?int $excludeInvoiceId = null
    ): void {
        if ($requestedAmount <= 0) {
            throw new RuntimeException(
                'Invoice allocated amount must be greater than zero.'
            );
        }

        $balance = $this->amountBalance(
            $poCustomer,
            $excludeInvoiceId
        );

        if (
            round($requestedAmount, 2) >
            round((float) $balance['remaining_amount'], 2)
        ) {
            throw new RuntimeException(
                'Invoice allocated amount exceeds remaining PO Customer amount.'
            );
        }
    }

    /**
     * Lock canonical PO + detail rows before final store/update validation.
     *
     * Must be called inside DB::transaction().
     */
    public function lockForAllocation(PoCustomer $poCustomer): PoCustomer
    {
        $lockedPo = PoCustomer::query()
            ->whereKey($poCustomer->id)
            ->lockForUpdate()
            ->firstOrFail();

        DB::table('po_customer_details')
            ->where('po_customer_id', $lockedPo->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        return $lockedPo;
    }
}
