<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BgsQuotationLifecycleContractTest extends TestCase
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

    private function controllerSource(string $controller): string
    {
        $path = app_path('Http/Controllers/' . $controller);

        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    public function test_new_quotation_starts_as_draft(): void
    {
        $source = $this->controllerSource(
            'QuotationController.php'
        );

        $this->assertMatchesRegularExpression(
            "/['\"]status['\"]\s*=>\s*['\"]draft['\"]/",
            $source
        );
    }

    public function test_explicit_approval_route_exists(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(function ($route) {

                return $route->getName() ===
                    'quotations.approve';
            });

        $this->assertNotNull($route);

        $this->assertContains(
            'POST',
            $route->methods()
        );
    }

    public function test_approval_action_only_accepts_draft_and_sets_approved(): void
    {
        $source = $this->controllerSource(
            'QuotationController.php'
        );

        $this->assertStringContainsString(
            'public function approve',
            $source
        );

        $this->assertStringContainsString(
            "\$quotation->status !== 'draft'",
            $source
        );

        $this->assertStringContainsString(
            "'status' => 'approved'",
            $source
        );
    }

    public function test_print_does_not_transition_draft_to_sent(): void
    {
        $source = $this->controllerSource(
            'QuotationController.php'
        );

        $printPos = strpos(
            $source,
            'public function print'
        );

        $this->assertNotFalse($printPos);

        $printSource = substr(
            $source,
            $printPos,
            2500
        );

        $this->assertDoesNotMatchRegularExpression(
            "/status\s*===\s*['\"]draft['\"].{0,300}" .
            "status\s*=\s*['\"]sent['\"]/s",
            $printSource
        );
    }

    public function test_print_transitions_approved_to_sent(): void
    {
        $source = $this->controllerSource(
            'QuotationController.php'
        );

        $printPos = strpos(
            $source,
            'public function print'
        );

        $this->assertNotFalse($printPos);

        $printSource = substr(
            $source,
            $printPos,
            2500
        );

        $this->assertMatchesRegularExpression(
            "/status\s*===\s*['\"]approved['\"].{0,300}" .
            "status\s*=\s*['\"]sent['\"]/s",
            $printSource
        );
    }

    public function test_normal_edit_preserves_lifecycle_status(): void
    {
        $source = $this->controllerSource(
            'QuotationController.php'
        );

        $this->assertStringContainsString(
            "'status' => \$quotation->status",
            $source
        );

        $this->assertStringNotContainsString(
            "'status' => \$request->status ?? \$quotation->status",
            $source
        );

        $view = resource_path(
            'views/quotations/edit.blade.php'
        );

        $this->assertFileExists($view);

        $viewSource = file_get_contents($view);

        $this->assertDoesNotMatchRegularExpression(
            '/name\s*=\s*["\']status["\']/',
            $viewSource
        );
    }

    public function test_po_customer_from_quotation_sets_received_po_customer_state(): void
    {
        $source = $this->controllerSource(
            'PoCustomerController.php'
        );

        /*
         * Accept the existing controlled implementation:
         *
         * $updateData = ['status' => 'rec_po'];
         * ...
         * $quotation->update($updateData);
         *
         * We intentionally do not require the status literal to be
         * inside update() itself.
         */

        $this->assertMatchesRegularExpression(
            "/updateData\s*=\s*\[\s*['\"]status['\"]" .
            "\s*=>\s*['\"]rec_po['\"]\s*\]/s",
            $source
        );

        $this->assertStringContainsString(
            '$quotation->update($updateData);',
            $source
        );
    }

    public function test_show_view_only_offers_approve_for_draft(): void
    {
        $view = resource_path(
            'views/quotations/show.blade.php'
        );

        $this->assertFileExists($view);

        $source = file_get_contents($view);

        $this->assertStringContainsString(
            "\$quotation->status === 'draft'",
            $source
        );

        $this->assertStringContainsString(
            "route('quotations.approve'",
            $source
        );
    }

    public function test_show_view_no_longer_fakes_draft_to_sent_in_javascript(): void
    {
        $view = resource_path(
            'views/quotations/show.blade.php'
        );

        $source = file_get_contents($view);

        $this->assertStringNotContainsString(
            "currentBadge.innerText.trim().toLowerCase() === 'draft'",
            $source
        );
    }
}