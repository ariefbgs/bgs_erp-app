<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class BgsLaravel13RouteParameterCompatibilityTest extends TestCase
{
    private function viewSource(string $path): string
    {
        return file_get_contents(dirname(__DIR__, 2) . '/resources/views/' . $path);
    }

    public function test_po_customer_create_supplies_ajax_route_placeholder(): void
    {
        $view = $this->viewSource('po_customers/create.blade.php');
        $this->assertStringContainsString('route("po-customers.get-quotation", ["id" => "__ID__"])', $view);
        $this->assertStringContainsString(".replace('__ID__', encodeURIComponent(qid))", $view);
        $this->assertStringNotContainsString('route("po-customers.get-quotation", "")', $view);
    }

    public function test_sales_invoice_create_supplies_ajax_route_placeholder(): void
    {
        $view = $this->viewSource('invoice_customers/create.blade.php');
        $this->assertStringContainsString('route("invoice-customers.get-po-customer-details", "__ID__")', $view);
        $this->assertStringContainsString(".replace('__ID__', poId)", $view);
        $this->assertStringNotContainsString('route("invoice-customers.get-po-customer-details", "")', $view);
    }

    public function test_document_receipt_create_supplies_po_route_placeholder(): void
    {
        $view = $this->viewSource('document-receipts/create.blade.php');
        $this->assertStringContainsString('route("document-receipts.get-create-options-by-po", ["po_customer_id" => "__PO_ID__"])', $view);
        $this->assertStringContainsString(".replace('__PO_ID__', encodeURIComponent(poId))", $view);
        $this->assertStringNotContainsString('route("document-receipts.get-create-options-by-po", "")', $view);
    }
}
