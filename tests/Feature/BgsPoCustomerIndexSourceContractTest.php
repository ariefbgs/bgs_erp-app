<?php

namespace Tests\Feature;

use Tests\TestCase;

class BgsPoCustomerIndexSourceContractTest extends TestCase
{
    public function test_po_customer_index_uses_canonical_invoice_coverage(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/PoCustomerController.php')
        );

        $this->assertStringContainsString(
            '$allocationService->amountBalance($po)',
            $source
        );

        $this->assertStringContainsString(
            '$po->total_invoiced = (float) $amountBalance[' .
            "'invoiced_amount'" . '];',
            $source
        );

        $this->assertStringContainsString(
            '$po->remaining_total = (float) $amountBalance[' .
            "'remaining_amount'" . '];',
            $source
        );

        $this->assertStringNotContainsString(
            "->sum('total')",
            $source
        );
    }
}
