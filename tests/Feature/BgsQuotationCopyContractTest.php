<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BgsQuotationCopyContractTest extends TestCase
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

    private function createViewSource(): string
    {
        $path = resource_path(
            'views/quotations/create.blade.php'
        );

        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    private function quotationControllerSource(): string
    {
        $path = app_path(
            'Http/Controllers/QuotationController.php'
        );

        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    public function test_create_form_exposes_copy_from_previous_quotation_option(): void
    {
        $source = strtolower(
            $this->createViewSource()
        );

        $hasCopyLanguage =
            str_contains($source, 'copy') &&
            str_contains($source, 'quotation');

        $this->assertTrue(
            $hasCopyLanguage,
            'RED CONTRACT: Create Quotation must expose Copy From Previous Quotation.'
        );
    }

    public function test_copy_source_quotation_can_be_selected(): void
    {
        $source = strtolower(
            $this->createViewSource()
        );

        $hasSourceSelector =
            str_contains($source, 'source_quotation')
            || str_contains($source, 'copy_from')
            || str_contains($source, 'copy-quotation')
            || str_contains($source, 'copy_quotation');

        $this->assertTrue(
            $hasSourceSelector,
            'RED CONTRACT: user must be able to select the source Quotation.'
        );
    }

    public function test_application_exposes_controlled_copy_quotation_endpoint_or_action(): void
    {
        $routes = collect(
            Route::getRoutes()->getRoutes()
        );

        $copyRoute = $routes->first(
            function ($route) {
                $uri = strtolower(
                    (string) $route->uri()
                );

                $name = strtolower(
                    (string) $route->getName()
                );

                $action = strtolower(
                    (string) $route->getActionName()
                );

                return str_contains($uri, 'quotation')
                    && (
                        str_contains($uri, 'copy')
                        || str_contains($name, 'copy')
                        || str_contains($action, 'copy')
                    );
            }
        );

        $controller = strtolower(
            $this->quotationControllerSource()
        );

        $controllerHasCopyAction =
            str_contains($controller, 'function copy')
            || str_contains($controller, 'function duplicate');

        $this->assertTrue(
            $copyRoute !== null || $controllerHasCopyAction,
            'RED CONTRACT: controlled Quotation copy action is missing.'
        );
    }

    public function test_copy_contract_requires_new_number_not_source_number_reuse(): void
    {
        $source = $this->quotationControllerSource();

        /*
         * Existing normal store already generates a new 4-digit
         * Quotation number. Copy must eventually reuse this controlled
         * NEW-number path rather than persist source quotation_number.
         *
         * This assertion records the number-generation foundation that
         * must remain intact.
         */
        $this->assertStringContainsString(
            "str_pad(\$lastNumber + 1, 4, '0', STR_PAD_LEFT)",
            $source
        );

        $this->assertStringContainsString(
            "'status' => 'draft'",
            $source
        );
    }

    public function test_copy_contract_must_not_inherit_downstream_identity_or_status(): void
    {
        /*
         * This test intentionally remains RED until a controlled copy
         * implementation exists. Once implemented, the copy code must
         * explicitly construct a NEW Quotation from reusable fields
         * rather than clone persistence/lifecycle identity.
         */

        $controller = strtolower(
            $this->quotationControllerSource()
        );

        $hasControlledCopyImplementation =
            str_contains($controller, 'source_quotation')
            || str_contains($controller, 'copyfrom')
            || str_contains($controller, 'copy_from')
            || str_contains($controller, 'function copy');

        $this->assertTrue(
            $hasControlledCopyImplementation,
            'RED CONTRACT: no controlled copy implementation exists to prove source identity/lifecycle exclusion.'
        );
    }
}
