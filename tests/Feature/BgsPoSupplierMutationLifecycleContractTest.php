<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class BgsPoSupplierMutationLifecycleContractTest extends TestCase
{
    private string $controller;
    private string $editView;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = file_get_contents(
            dirname(__DIR__, 2) .
            '/app/Http/Controllers/PoSupplierController.php'
        );

        $this->editView = file_get_contents(
            dirname(__DIR__, 2) .
            '/resources/views/po_suppliers/edit.blade.php'
        );
    }

    public function test_edit_form_preserves_po_customer_detail_lineage(): void
    {
        $this->assertStringContainsString(
            'po_customer_detail_id',
            $this->editView
        );

        $this->assertStringContainsString(
            '$detail->po_customer_detail_id',
            $this->editView
        );
    }

    public function test_update_requires_lineage(): void
    {
        $this->assertStringContainsString(
            "'items.*.po_customer_detail_id'",
            $this->controller
        );

        $this->assertStringContainsString(
            "'required|exists:po_customer_details,id'",
            $this->controller
        );
    }

    public function test_update_excludes_current_po_supplier_from_existing_allocation(): void
    {
        $this->assertStringContainsString(
            '$allocatedByOthers',
            $this->controller
        );

        $this->assertStringContainsString(
            "'po_supplier_details.po_supplier_id'",
            $this->controller
        );

        $this->assertStringContainsString(
            '$poSupplier->id',
            $this->controller
        );
    }

    public function test_update_has_remaining_quantity_guard(): void
    {
        $this->assertStringContainsString(
            '$availableQuantity',
            $this->controller
        );

        $this->assertTrue(
            str_contains(
                $this->controller,
                '$requestedByDetail'
            ),
            'Update must track cumulative quantity requested per PO Customer detail.'
        );

        $this->assertMatchesRegularExpression(
            '/\$requestedByDetail\s*\[\s*\$detail->id\s*\]\s*>\s*\$availableQuantity/s',
            $this->controller,
            'PO Supplier update must reject cumulative requested quantity above available quantity.'
        );
    }

    public function test_cancelled_po_supplier_is_excluded_from_active_allocation(): void
    {
        $this->assertStringContainsString(
            "'po_suppliers.status'",
            $this->controller
        );

        $this->assertStringContainsString(
            "'cancelled'",
            $this->controller
        );
    }

    public function test_mutations_reconcile_procurement_status(): void
    {
        $count = substr_count(
            $this->controller,
            '$this->refreshProcurementStatus('
        );

        /*
         * Expected calls:
         * create
         * update
         * updateStatus
         * destroy
         *
         * Plus method declaration is not counted because it does
         * not contain "$this->".
         */
        $this->assertGreaterThanOrEqual(
            4,
            $count
        );
    }

    public function test_legacy_destroy_count_rule_is_removed(): void
    {
        $this->assertStringNotContainsString(
            'remainingCount',
            $this->controller
        );

        $this->assertStringNotContainsString(
            "status == 'processed'",
            $this->controller
        );
    }

    public function test_received_po_supplier_cannot_be_deleted(): void
    {
        $this->assertStringContainsString(
            "\$poSupplier->status === 'received'",
            $this->controller
        );
    }

    public function test_cancel_after_goods_receipt_is_guarded(): void
    {
        $this->assertStringContainsString(
            "\$newStatus === 'cancelled'",
            $this->controller
        );

        $this->assertStringContainsString(
            '$this->isPoSupplierUsed($poSupplier)',
            $this->controller
        );
    }
}