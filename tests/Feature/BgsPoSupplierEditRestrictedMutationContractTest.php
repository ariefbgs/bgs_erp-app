<?php

namespace Tests\Feature;

use Tests\TestCase;

class BgsPoSupplierEditRestrictedMutationContractTest extends TestCase
{
    private string $controller;
    private string $blade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = file_get_contents(
            app_path('Http/Controllers/PoSupplierController.php')
        );

        $this->blade = file_get_contents(
            resource_path('views/po_suppliers/edit.blade.php')
        );
    }

    public function test_update_does_not_validate_locked_header_fields(): void
    {
        foreach ([
            'po_supplier_number',
            'supplier_id',
            'po_date',
            'status',
        ] as $field) {
            $this->assertDoesNotMatchRegularExpression(
                "/['\"]" . preg_quote($field, '/') . "['\"]\s*=>/",
                $this->extractUpdateValidationBlock(),
                "Locked field {$field} must not be accepted by update validation."
            );
        }
    }

    public function test_update_does_not_mutate_locked_header_fields(): void
    {
        $updateBlock = $this->extractUpdateMethod();

        foreach ([
            'po_supplier_number',
            'supplier_id',
            'po_date',
            'status',
        ] as $field) {
            $this->assertDoesNotMatchRegularExpression(
                "/['\"]" . preg_quote($field, '/') . "['\"]\s*=>\s*\\\$request/",
                $updateBlock,
                "Locked field {$field} must not be mutated from request."
            );
        }

        $this->assertStringNotContainsString(
            '$request->status',
            $updateBlock,
            'Lifecycle status must not be controlled by Edit request.'
        );
    }

    public function test_edit_ui_locks_po_supplier_number(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/name=["\']po_supplier_number["\'][^>]*(?!readonly|disabled)[^>]*>/i',
            $this->blade,
            'PO Supplier Number must not remain editable.'
        );
    }

    public function test_edit_ui_has_no_editable_status_selector(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/<select[^>]+name=["\']status["\']/i',
            $this->blade,
            'PO Status must not be editable from Edit form.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/<select[^>]+name=["\'](?:receive_status|receipt_status)["\']/i',
            $this->blade,
            'Receipt Status must not be editable from Edit form.'
        );
    }

    public function test_edit_ui_tax_is_not_editable(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]+name=["\']tax_percent["\'][^>]*(?!readonly|disabled)[^>]*>/i',
            $this->blade,
            'Tax must be locked on Edit PO Supplier.'
        );
    }

    public function test_update_accepts_discount_and_calculates_it_server_side(): void
    {
        $updateBlock = $this->extractUpdateMethod();

        $this->assertMatchesRegularExpression(
            "/['\"]discount_percent['\"]\s*=>/",
            $updateBlock,
            'Discount percent must be validated server-side.'
        );

        $this->assertMatchesRegularExpression(
            '/discountAmount|discount_amount/',
            $updateBlock,
            'Discount amount must be calculated/persisted server-side.'
        );

        $this->assertMatchesRegularExpression(
            '/subtotal\s*-\s*\$?discount/i',
            $updateBlock,
            'Taxable/base amount must account for discount.'
        );
    }

    public function test_update_keeps_existing_tax_configuration(): void
    {
        $updateBlock = $this->extractUpdateMethod();

        $this->assertStringNotContainsString(
            '$request->tax_percent',
            $updateBlock,
            'Edit must not accept tax configuration from request.'
        );

        $this->assertMatchesRegularExpression(
            '/poSupplier->tax_percent|poSupplier\->tax_percent/',
            $updateBlock,
            'Existing PO Supplier tax percent must remain authoritative.'
        );
    }

    public function test_add_item_is_not_sourced_from_arbitrary_product_master(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/productsData\.forEach\s*\(/',
            $this->blade,
            'Add Item must not offer arbitrary Product Master products.'
        );

        $this->assertMatchesRegularExpression(
            '/po_customer_detail_id/',
            $this->blade,
            'Added item must retain PO Customer Detail lineage.'
        );
    }

    public function test_update_preserves_po_customer_detail_lineage_and_remaining_qty_guard(): void
    {
        $updateBlock = $this->extractUpdateMethod();

        $this->assertStringContainsString(
            'po_customer_detail_id',
            $updateBlock
        );

        $this->assertStringContainsString(
            'allocatedByOthers',
            $updateBlock
        );

        $this->assertStringContainsString(
            'availableQuantity',
            $updateBlock
        );

        $this->assertStringContainsString(
            'lockForUpdate()',
            $updateBlock
        );
    }

    public function test_qty_price_and_notes_remain_editable_contract(): void
    {
        foreach ([
            'quantity',
            'purchase_price',
            'notes',
        ] as $field) {
            $this->assertStringContainsString(
                $field,
                $this->extractUpdateMethod(),
                "{$field} must remain part of Edit mutation contract."
            );
        }
    }

    private function extractUpdateMethod(): string
    {
        preg_match(
            '/public function update\(Request \$request, \$id\)([\s\S]*?)public function updateStatus\(/',
            $this->controller,
            $matches
        );

        return $matches[1] ?? '';
    }

    private function extractUpdateValidationBlock(): string
    {
        $method = $this->extractUpdateMethod();

        preg_match(
            '/\$request->validate\(\[([\s\S]*?)\]\);/',
            $method,
            $matches
        );

        return $matches[1] ?? '';
    }
}
