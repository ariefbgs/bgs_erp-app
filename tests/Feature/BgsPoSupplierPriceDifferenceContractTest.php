<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class BgsPoSupplierPriceDifferenceContractTest extends TestCase
{
    private string $controller;
    private string $view;

    protected function setUp(): void
    {
        parent::setUp();

        $root = dirname(__DIR__, 2);

        $this->controller = file_get_contents(
            $root . '/app/Http/Controllers/PoSupplierController.php'
        );

        $this->view = file_get_contents(
            $root . '/resources/views/po_suppliers/create.blade.php'
        );
    }

    public function test_backend_accepts_explicit_master_price_decision(): void
    {
        $this->assertStringContainsString(
            "items.*.master_price_decision",
            $this->controller
        );

        $this->assertMatchesRegularExpression(
            '/in:update,keep/',
            $this->controller
        );
    }

    public function test_backend_compares_transaction_price_to_product_master(): void
    {
        $this->assertStringContainsString(
            'masterPurchasePrice',
            $this->controller
        );

        $this->assertStringContainsString(
            'transactionPurchasePrice',
            $this->controller
        );

        $this->assertStringContainsString(
            'masterPriceDifferent',
            $this->controller
        );
    }

    public function test_backend_requires_decision_when_price_differs(): void
    {
        $this->assertStringContainsString(
            'Pilih Update Master atau Keep Existing.',
            $this->controller
        );
    }

    public function test_backend_updates_product_master_only_for_update_decision(): void
    {
        $this->assertMatchesRegularExpression(
            "/master_price_decision['\"]?\]\s*\?\?\s*null/",
            $this->controller
        );

        $this->assertStringContainsString(
            "'update'",
            $this->controller
        );

        $this->assertStringContainsString(
            "'purchase_price'",
            $this->controller
        );
    }

    public function test_transaction_purchase_price_is_preserved_in_po_supplier_detail(): void
    {
        $this->assertMatchesRegularExpression(
            "/'purchase_price'\s*=>\s*\\\$item\['purchase_price'\]/",
            $this->controller
        );
    }

    public function test_product_master_is_locked_for_price_decision(): void
    {
        $this->assertMatchesRegularExpression(
            '/Product::where[\s\S]*?lockForUpdate\(\)/',
            $this->controller
        );
    }

    public function test_create_ui_contains_master_price_reference(): void
    {
        $this->assertMatchesRegularExpression(
            '/master[_-]?purchase[_-]?price|master[_-]?price/i',
            $this->view,
            'Create PO Supplier UI must retain the Product Master price for comparison.'
        );
    }

    public function test_create_ui_has_explicit_master_price_decision_field(): void
    {
        $this->assertStringContainsString(
            'master_price_decision',
            $this->view,
            'UI must submit an explicit per-item master price decision.'
        );
    }

    public function test_create_ui_exposes_update_master_choice(): void
    {
        $this->assertMatchesRegularExpression(
            '/update master/i',
            $this->view,
            'User must be offered Update Master when purchase price differs.'
        );
    }

    public function test_create_ui_exposes_keep_existing_choice(): void
    {
        $this->assertMatchesRegularExpression(
            '/keep existing/i',
            $this->view,
            'User must be offered Keep Existing when purchase price differs.'
        );
    }

    public function test_create_ui_does_not_silently_default_to_update_master(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/master_price_decision[^;\r\n]*[=:][^;\r\n]*["\']update["\']/i',
            $this->view,
            'UI must never silently default a price mismatch to Update Master.'
        );
    }
}