<?php

namespace Tests\Feature;

use Tests\TestCase;

class BgsPoCustomerIndexSourceContractTest extends TestCase
{
    public function test_po_customer_index_uses_canonical_invoice_coverage(): void
    {
        $path = app_path(
            'Http/Controllers/PoCustomerController.php'
        );

        $source = file_get_contents($path);

        $this->assertStringContainsString(
            '$totalInvoiced = $po->total_invoiced;',
            $source
        );

        $this->assertStringNotContainsString(
            'InvoiceCustomer::where(' .
            "'po_customer_id', " .
            '$po->id)->sum(' .
            "'total');",
            $source,
            'PO Customer index must not bypass canonical cancelled-invoice semantics.'
        );
    }
}