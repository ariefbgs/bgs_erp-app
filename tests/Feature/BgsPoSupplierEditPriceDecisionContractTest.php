<?php

namespace Tests\Feature;

use Tests\TestCase;

class BgsPoSupplierEditPriceDecisionContractTest extends TestCase
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

    public function test_edit_update_accepts_per_item_master_price_decision(): void
    {
        $update = $this->extractUpdate();

        $this->assertStringContainsString(
            "'items.*.master_price_decision'",
            $update,
            'Edit update must validate per-item master price decision.'
        );

        $this->assertStringContainsString(
            'in:update,keep',
            $update,
            'Edit decision must only accept update or keep.'
        );
    }

    public function test_edit_update_locks_product_master_before_price_comparison(): void
    {
        $update = $this->extractUpdate();

        $this->assertStringContainsString(
            'Product::where',
            $update
        );

        $this->assertStringContainsString(
            'lockForUpdate()',
            $update
        );

        $this->assertStringContainsString(
            'purchase_price',
            $update
        );
    }

    public function test_edit_update_requires_decision_when_transaction_price_differs(): void
    {
        $update = $this->extractUpdate();

        $this->assertStringContainsString(
            'masterPriceDifferent',
            $update
        );

        $this->assertStringContainsString(
            'masterPriceDecision',
            $update
        );

        $this->assertStringContainsString(
            "['update', 'keep']",
            $update
        );
    }

    public function test_edit_update_only_updates_master_on_explicit_update_choice(): void
    {
        $update = $this->extractUpdate();

        $this->assertStringContainsString(
            "=== 'update'",
            $update
        );

        $this->assertMatchesRegularExpression(
            '/productMaster->(?:purchase_price|update)/',
            $update,
            'Product Master may only be changed under explicit update decision.'
        );
    }

    public function test_edit_preserves_transaction_purchase_price(): void
    {
        $update = $this->extractUpdate();

        $this->assertStringContainsString(
            "'purchase_price'",
            $update
        );

        $this->assertStringContainsString(
            '$price',
            $update
        );
    }

    public function test_edit_ui_carries_original_master_price_reference(): void
    {
        $this->assertStringContainsString(
            'original-master-price',
            $this->blade,
            'Edit UI must carry current Product Master purchase price reference.'
        );
    }

    public function test_edit_ui_has_per_item_master_price_decision_field(): void
    {
        $this->assertStringContainsString(
            'master_price_decision',
            $this->blade
        );

        $this->assertStringContainsString(
            'Update Master',
            $this->blade
        );

        $this->assertStringContainsString(
            'Keep Existing',
            $this->blade
        );
    }

    public function test_edit_price_difference_is_checked_before_submit(): void
    {
        $this->assertTrue(
            str_contains(
                $this->blade,
                'priceDecision'
            )
            ||
            str_contains(
                $this->blade,
                'master-price'
            )
            ||
            str_contains(
                $this->blade,
                'masterPrice'
            ),
            'Edit UI must evaluate purchase-price difference before final submit.'
        );
    }

    private function extractUpdate(): string
    {
        preg_match(
            '/public function update\(Request \$request, \$id\)([\s\S]*?)public function updateStatus\(/',
            $this->controller,
            $matches
        );

        return $matches[1] ?? '';
    }
}