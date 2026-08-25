<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BgsPoCustomerLifecycleProtectionContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (app()->environment() !== 'testing') {
            throw new \RuntimeException(
                'SAFETY BLOCK: APP_ENV must be testing.'
            );
        }

        if (DB::connection()->getDatabaseName() !== 'erp_app_testing') {
            throw new \RuntimeException(
                'SAFETY BLOCK: database must be erp_app_testing.'
            );
        }
    }

    private function controllerSource(): string
    {
        $path = app_path(
            'Http/Controllers/PoCustomerController.php'
        );

        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    private function modelSource(): string
    {
        $path = app_path(
            'Models/PoCustomer.php'
        );

        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    private function editViewSource(): string
    {
        $path = resource_path(
            'views/po_customers/edit.blade.php'
        );

        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    public function test_direct_po_customer_remains_supported(): void
    {
        $source = $this->controllerSource();

        $this->assertStringContainsString(
            "'customer_id' => \$request->source_type == 'manual' ? 'required|exists:customers,id' : 'nullable'",
            $source
        );

        $this->assertStringContainsString(
            "'quotation_id' => \$request->source_type == 'quotation' ? 'required|exists:quotations,id' : 'nullable'",
            $source
        );

        $this->assertStringContainsString(
            "'quotation_id' => \$request->source_type == 'quotation' ? \$request->quotation_id : null",
            $source
        );
    }

    public function test_new_po_customer_starts_in_received_state(): void
    {
        $source = $this->controllerSource();

        $this->assertMatchesRegularExpression(
            "/['\"]status['\"]\s*=>\s*['\"]received['\"]/",
            $source
        );
    }

    public function test_po_customer_model_supports_multiple_po_suppliers(): void
    {
        $source = $this->modelSource();

        $this->assertStringContainsString(
            'function poSuppliers',
            $source
        );

        $this->assertMatchesRegularExpression(
            "/hasMany\s*\(\s*PoSupplier::class\s*,\s*" .
            "['\"]po_customer_id['\"]\s*\)/",
            $source
        );
    }

    public function test_po_supplier_is_a_downstream_usage_dependency(): void
    {
        $source = $this->controllerSource();

        $methodPos = strpos(
            $source,
            'function isPoCustomerUsed'
        );

        $this->assertNotFalse($methodPos);

        $methodSource = substr(
            $source,
            $methodPos,
            1200
        );

        $this->assertStringContainsString(
            'poSuppliers()->exists()',
            $methodSource,
            'RED CONTRACT: PO Supplier must protect PO Customer from unsafe mutation/deletion.'
        );
    }

    public function test_normal_edit_does_not_accept_arbitrary_status_from_request(): void
    {
        $source = $this->controllerSource();

        $updatePos = strpos(
            $source,
            'public function update'
        );

        $this->assertNotFalse($updatePos);

        $updateSource = substr(
            $source,
            $updatePos,
            9000
        );

        $this->assertStringNotContainsString(
            "'status' => \$request->status",
            $updateSource,
            'RED CONTRACT: normal edit must not directly control lifecycle status.'
        );
    }

    public function test_edit_form_does_not_expose_free_lifecycle_status_selector(): void
    {
        $source = $this->editViewSource();

        $this->assertDoesNotMatchRegularExpression(
            '/name\s*=\s*["\']status["\']/',
            $source,
            'RED CONTRACT: lifecycle status must not be a freely editable form field.'
        );
    }

    public function test_authorization_does_not_depend_on_hard_coded_email_identity(): void
    {
        $source = $this->controllerSource();

        $this->assertDoesNotMatchRegularExpression(
            '/auth\(\)->user\(\)->email\s*===/',
            $source,
            'RED CONTRACT: authorization must not depend on hard-coded email identity.'
        );

        $this->assertStringNotContainsString(
            'arief@gmail.com',
            $source,
            'RED CONTRACT: personal email must not be an authorization authority.'
        );
    }
}
