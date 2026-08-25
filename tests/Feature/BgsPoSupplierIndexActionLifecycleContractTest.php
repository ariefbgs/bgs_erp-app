<?php

namespace Tests\Feature;

use Tests\TestCase;

class BgsPoSupplierIndexActionLifecycleContractTest extends TestCase
{
    public function test_index_loads_active_goods_receipt_count_without_blade_query(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/PoSupplierController.php')
        );

        $blade = file_get_contents(
            resource_path('views/po_suppliers/index.blade.php')
        );

        $this->assertStringContainsString(
            'goodsReceipts as goods_receipts_count',
            $controller
        );

        $this->assertStringContainsString(
            "where('status', '!=', 'cancelled')",
            $controller
        );

        $this->assertStringNotContainsString(
            'goodsReceipts()->',
            $blade
        );
    }

    public function test_view_remains_available_and_edit_delete_are_lifecycle_guarded(): void
    {
        $blade = file_get_contents(
            resource_path('views/po_suppliers/index.blade.php')
        );

        $this->assertStringContainsString(
            "route('po-suppliers.show'",
            $blade
        );

        $this->assertStringContainsString(
            '$hasActiveGoodsReceipt',
            $blade
        );

        $this->assertStringContainsString(
            '$canEdit',
            $blade
        );

        $this->assertStringContainsString(
            "['draft', 'confirmed']",
            $blade
        );

        $this->assertStringContainsString(
            '$canDelete',
            $blade
        );

        $this->assertStringContainsString(
            '$po->status',
            $blade
        );

        $this->assertStringContainsString(
            "=== 'draft'",
            $blade
        );

        $this->assertStringContainsString(
            '@if ($canEdit)',
            $blade
        );

        $this->assertStringContainsString(
            '@if ($canDelete)',
            $blade
        );
    }
}