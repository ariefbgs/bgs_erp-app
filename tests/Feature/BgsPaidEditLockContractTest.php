<?php

namespace Tests\Feature;

use Tests\TestCase;

class BgsPaidEditLockContractTest extends TestCase
{
    private function quotationController(): string
    {
        return file_get_contents(
            app_path('Http/Controllers/QuotationController.php')
        );
    }

    private function poCustomerController(): string
    {
        return file_get_contents(
            app_path('Http/Controllers/PoCustomerController.php')
        );
    }

    /** @test */
    public function quotation_has_separate_paid_edit_lock_helper(): void
    {
        $source = $this->quotationController();

        $this->assertStringContainsString(
            'function isQuotationEditLocked',
            $source,
            'Quotation must have a dedicated edit-lock helper.'
        );

        $this->assertStringContainsString(
            "'payment_status', 'paid'",
            $source,
            'Quotation edit lock must use payment_status=paid.'
        );

        $this->assertStringContainsString(
            "'status', '!=', 'cancelled'",
            $source,
            'Cancelled invoices must not trigger Quotation edit lock.'
        );
    }

    /** @test */
    public function quotation_edit_and_update_use_paid_lock_not_usage_lock(): void
    {
        $source = $this->quotationController();

        preg_match(
            '/public function edit\(Quotation \$quotation\)(.*?)public function update/s',
            $source,
            $editMatch
        );

        preg_match(
            '/public function update\(Request \$request, Quotation \$quotation\)(.*?)public function destroy/s',
            $source,
            $updateMatch
        );

        $edit = $editMatch[1] ?? '';
        $update = $updateMatch[1] ?? '';

        $this->assertStringContainsString(
            'isQuotationEditLocked',
            $edit
        );

        $this->assertStringContainsString(
            'isQuotationEditLocked',
            $update
        );

        $this->assertStringNotContainsString(
            'isQuotationUsed($quotation)',
            $edit,
            'Quotation edit must no longer be blocked merely because downstream data exists.'
        );

        $this->assertStringNotContainsString(
            'isQuotationUsed($quotation)',
            $update,
            'Quotation update must no longer be blocked merely because downstream data exists.'
        );
    }

    /** @test */
    public function quotation_delete_keeps_existing_usage_protection(): void
    {
        $source = $this->quotationController();

        preg_match(
            '/public function destroy\(Quotation \$quotation\)(.*?)public function print/s',
            $source,
            $match
        );

        $destroy = $match[1] ?? '';

        $this->assertStringContainsString(
            'isQuotationUsed($quotation)',
            $destroy,
            'Quotation delete protection must remain unchanged.'
        );
    }

    /** @test */
    public function po_customer_has_separate_paid_edit_lock_helper(): void
    {
        $source = $this->poCustomerController();

        $this->assertStringContainsString(
            'function isPoCustomerEditLocked',
            $source,
            'PO Customer must have a dedicated edit-lock helper.'
        );

        $this->assertStringContainsString(
            "'payment_status', 'paid'",
            $source,
            'PO Customer edit lock must use payment_status=paid.'
        );

        $this->assertStringContainsString(
            "'status', '!=', 'cancelled'",
            $source,
            'Cancelled invoices must not trigger PO Customer edit lock.'
        );
    }

    /** @test */
    public function po_customer_edit_and_update_use_paid_lock_not_usage_lock(): void
    {
        $source = $this->poCustomerController();

        preg_match(
            '/public function edit\(\$id\)(.*?)public function update/s',
            $source,
            $editMatch
        );

        preg_match(
            '/public function update\(Request \$request, \$id\)(.*?)public function destroy/s',
            $source,
            $updateMatch
        );

        $edit = $editMatch[1] ?? '';
        $update = $updateMatch[1] ?? '';

        $this->assertStringContainsString(
            'isPoCustomerEditLocked',
            $edit
        );

        $this->assertStringContainsString(
            'isPoCustomerEditLocked',
            $update
        );

        $this->assertStringNotContainsString(
            'isPoCustomerUsed($poCustomer)',
            $edit,
            'PO Customer edit must no longer be blocked by PO Supplier, DO, or unpaid invoice.'
        );

        $this->assertStringNotContainsString(
            'isPoCustomerUsed($poCustomer)',
            $update,
            'PO Customer update must no longer be blocked by PO Supplier, DO, or unpaid invoice.'
        );
    }

    /** @test */
    public function po_customer_delete_keeps_existing_usage_protection(): void
    {
        $source = $this->poCustomerController();

        preg_match(
            '/public function destroy\(\$id\)(.*?)public function getQuotation/s',
            $source,
            $match
        );

        $destroy = $match[1] ?? '';

        $this->assertStringContainsString(
            'isPoCustomerUsed($poCustomer)',
            $destroy,
            'PO Customer delete protection must remain unchanged.'
        );
    }
}