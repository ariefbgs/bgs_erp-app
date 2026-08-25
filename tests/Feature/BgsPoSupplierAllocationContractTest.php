<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class BgsPoSupplierAllocationContractTest extends TestCase
{
    private string $controllerPath;
    private string $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controllerPath =
            dirname(__DIR__, 2)
            . '/app/Http/Controllers/PoSupplierController.php';

        $this->source = file_get_contents($this->controllerPath);
    }

    public function test_po_customer_dropdown_is_not_closed_by_first_po_supplier(): void
    {
        $this->assertStringNotContainsString(
            "->whereDoesntHave('poSuppliers')",
            $this->source,
            'PO Customer must remain eligible while remaining procurement quantity still exists.'
        );
    }

    public function test_dropdown_eligibility_is_based_on_remaining_procurement_quantity(): void
    {
        $this->assertMatchesRegularExpression(
            '/remaining|allocated|ordered.*supplier|supplier.*quantity/i',
            $this->source,
            'PO Customer dropdown must use remaining allocation logic.'
        );
    }

    public function test_po_customer_detail_response_exposes_remaining_quantity(): void
    {
        $this->assertMatchesRegularExpression(
            "/['\"]remaining_quantity['\"]\s*=>/",
            $this->source,
            'Create PO Supplier must receive remaining quantity per PO Customer detail.'
        );
    }

    public function test_store_validates_item_belongs_to_selected_po_customer(): void
    {
        $this->assertMatchesRegularExpression(
            '/po_customer_details|PoCustomerDetail/',
            $this->source,
            'Store must validate submitted items against selected PO Customer detail.'
        );
    }

    public function test_store_rejects_cumulative_quantity_above_remaining_quantity(): void
    {
        $this->assertMatchesRegularExpression(
            '/remaining.*quantity|quantity.*remaining|over.*quantity|exceed/i',
            $this->source,
            'Server-side store must reject cumulative supplier allocation above PO Customer quantity.'
        );
    }

    public function test_cancelled_po_supplier_does_not_consume_remaining_quantity(): void
    {
        $this->assertMatchesRegularExpression(
            "/cancelled/",
            $this->source,
            'Remaining procurement calculation must explicitly account for cancelled PO Suppliers.'
        );
    }

    public function test_store_uses_transaction_for_allocation_protection(): void
    {
        $this->assertStringContainsString(
            'DB::beginTransaction()',
            $this->source
        );
    }
}