<?php

namespace Tests\Feature;

use Tests\TestCase;

class BgsPoSupplierIndexFilterContractTest extends TestCase
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
            $root . '/resources/views/po_suppliers/index.blade.php'
        );
    }

    public function test_index_accepts_request_for_filter_processing(): void
    {
        $this->assertMatchesRegularExpression(
            '/public\s+function\s+index\s*\(\s*Request\s+\$request\s*\)/',
            $this->controller,
            'H7G2 RED: PoSupplierController@index must receive Request.'
        );
    }

    public function test_index_filters_po_supplier_number(): void
    {
        $this->assertMatchesRegularExpression(
            '/where\s*\(\s*[\'"]po_supplier_number[\'"]/',
            $this->controller,
            'H7G2 RED: search must filter PO Supplier Number.'
        );
    }

    public function test_index_searches_supplier_relation(): void
    {
        $this->assertMatchesRegularExpression(
            '/(?:or)?whereHas\s*\(\s*[\'"]supplier[\'"]/i',
            $this->controller,
            'H7G2: search must include Supplier relation using whereHas/orWhereHas.'
        );
    }

    public function test_index_processes_po_status_filter(): void
    {
        $this->assertMatchesRegularExpression(
            '/\$request->(?:filled|input)\s*\(\s*[\'"]status[\'"]/',
            $this->controller,
            'H7G2 RED: index must process status filter.'
        );
    }

    public function test_index_processes_receipt_status_filter(): void
    {
        $this->assertStringContainsString(
            'receipt_status',
            $this->controller,
            'H7G2 RED: index must filter canonical receipt_status column.'
        );

        $this->assertStringContainsString(
            'receive_status',
            $this->controller,
            'H7G2 RED: index must consume existing receive_status request parameter.'
        );
    }

    public function test_index_keeps_eight_rows_per_page(): void
    {
        $this->assertMatchesRegularExpression(
            '/paginate\s*\(\s*8\s*\)/',
            $this->controller,
            'H7G2: PO Supplier Index must remain maximum 8 rows/page.'
        );
    }

    public function test_pagination_preserves_query_string(): void
    {
        $this->assertTrue(
            str_contains($this->view, 'appends(request()->query())') ||
            str_contains($this->controller, 'withQueryString()'),
            'H7G2: pagination must preserve active query string.'
        );
    }

    public function test_filter_form_keeps_existing_request_contract(): void
    {
        $this->assertStringContainsString('name="search"', $this->view);
        $this->assertStringContainsString('name="status"', $this->view);
        $this->assertStringContainsString('name="receive_status"', $this->view);

        $this->assertStringContainsString(
            "value=\"completed\"",
            $this->view,
            'Receipt completed filter must use canonical DB enum value completed.'
        );
    }
}
