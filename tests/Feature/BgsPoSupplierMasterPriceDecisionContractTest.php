<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class BgsPoSupplierMasterPriceDecisionContractTest extends TestCase
{
    private string $controller;
    private string $view;

    protected function setUp(): void
    {
        parent::setUp();

        $root = dirname(__DIR__, 2);

        $this->controller = file_get_contents(
            $root .
            '/app/Http/Controllers/PoSupplierController.php'
        );

        $this->view = file_get_contents(
            $root .
            '/resources/views/po_suppliers/create.blade.php'
        );
    }

    public function test_create_has_master_price_decision_contract(): void
    {
        $this->assertStringContainsString(
            "'items.*.master_price_decision'",
            $this->controller
        );

        $this->assertStringContainsString(
            "'nullable|in:update,keep'",
            $this->controller
        );
    }

    public function test_current_product_master_is_locked(): void
    {
        $this->assertStringContainsString(
            '$productMaster = Product::where(',
            $this->controller
        );

        $this->assertStringContainsString(
            '->lockForUpdate()',
            $this->controller
        );
    }

    public function test_price_difference_requires_decision(): void
    {
        $this->assertStringContainsString(
            '$masterPriceDifferent',
            $this->controller
        );

        $this->assertStringContainsString(
            "['update', 'keep']",
            $this->controller
        );

        $this->assertStringContainsString(
            'Pilih Update Master atau Keep Existing.',
            $this->controller
        );
    }

    public function test_master_update_requires_explicit_update_decision(): void
    {
        $this->assertMatchesRegularExpression(
            "/master_price_decision'\\]\\s*\\?\\?\\s*null\\)\\s*===\\s*'update'/s",
            $this->controller
        );
    }

    public function test_po_supplier_keeps_transaction_price(): void
    {
        $this->assertStringContainsString(
            "'purchase_price' => \$item['purchase_price']",
            $this->controller
        );
    }

    public function test_form_has_original_master_price(): void
    {
        $this->assertStringContainsString(
            'data-master-price=',
            $this->view
        );
    }

    public function test_modal_requires_per_item_decision(): void
    {
        $this->assertStringContainsString(
            'masterPriceDecisionModal',
            $this->view
        );

        $this->assertStringContainsString(
            'Update Master',
            $this->view
        );

        $this->assertStringContainsString(
            'Keep Existing',
            $this->view
        );

        $this->assertStringContainsString(
            'master_price_choice_',
            $this->view
        );
    }

    public function test_price_difference_is_checked_before_save(): void
    {
        $this->assertStringContainsString(
            'collectMasterPriceDifferences()',
            $this->view
        );

        $this->assertStringContainsString(
            'priceDifferences.length > 0',
            $this->view
        );
    }
}