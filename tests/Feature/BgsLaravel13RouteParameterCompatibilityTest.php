<?php

namespace Tests\Feature;

use Tests\TestCase;

class BgsLaravel13RouteParameterCompatibilityTest extends TestCase
{
    public function test_po_customer_create_supplies_ajax_route_placeholder(): void
    {
        $view = file_get_contents(resource_path('views/po_customers/create.blade.php'));

        $this->assertStringContainsString(
            "route('po-customers.get-quotation', ['id' => '__ID__'])",
            $view
        );
        $this->assertStringNotContainsString(
            'route("po-customers.get-quotation", "")',
            $view
        );
    }

    public function test_sales_invoice_create_supplies_ajax_route_placeholder(): void
    {
        $view = file_get_contents(resource_path('views/invoice_customers/create.blade.php'));

        $this->assertStringContainsString(
            "route('invoice-customers.get-po-customer-details', ['id' => '__ID__'])",
            $view
        );
        $this->assertStringNotContainsString(
            'route("invoice-customers.get-po-customer-details", "")',
            $view
        );
    }
}
