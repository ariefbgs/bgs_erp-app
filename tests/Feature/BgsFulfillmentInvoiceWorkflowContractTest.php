<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class BgsFulfillmentInvoiceWorkflowContractTest extends TestCase
{
    public function test_goods_receipt_dropdown_is_not_limited_to_confirmed_supplier_pos(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/app/Http/Controllers/GoodsReceiptController.php'
        );

        $this->assertStringNotContainsString(
            "PoSupplier::where('status', 'confirmed')",
            $source
        );
        $this->assertStringContainsString("where('status', '!=', 'cancelled')", $source);
        $this->assertStringContainsString('quantity_received', $source);
    }

    public function test_delivery_order_is_guarded_by_active_goods_receipt_quantity(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/app/Http/Controllers/DeliveryOrderController.php'
        );

        $this->assertStringContainsString('getDeliverableQuantities', $source);
        $this->assertStringContainsString('goods_receipt_details as grd', $source);
        $this->assertStringContainsString('delivery_order_details as dod', $source);
        $this->assertStringContainsString(
            'Quantity Delivery Order melebihi quantity yang sudah diterima melalui Goods Receipt.',
            $source
        );
    }

    public function test_po_customer_header_has_canonical_remaining_invoice_amount(): void
    {
        $model = file_get_contents(
            dirname(__DIR__, 2) . '/app/Models/PoCustomer.php'
        );
        $migration = file_get_contents(
            dirname(__DIR__, 2)
            . '/database/migrations/2026_08_25_220000_add_remaining_amount_to_po_customers_table.php'
        );

        $this->assertStringContainsString("'remaining_amount'", $model);
        $this->assertStringContainsString("decimal('remaining_amount', 15, 2)", $migration);
        $this->assertStringContainsString('updateInvoiceStatus', $model);
    }

    public function test_sales_invoice_remaining_is_po_remaining_after_current_invoice(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/app/Http/Controllers/InvoiceCustomerController.php'
        );

        $this->assertStringContainsString(
            '$remainingAfterInvoice = max(0, $remainingPreTax - $baseAmount);',
            $source
        );
        $this->assertStringNotContainsString(
            '$remainingAfterInvoice = ($dpPercent > 0) ? ($subtotal - $dpAmount) : 0;',
            $source
        );
        $this->assertStringContainsString('lockForUpdate()', $source);
    }

    public function test_goods_receipt_edit_back_button_returns_to_goods_receipt_index(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/goods_receipts/edit.blade.php'
        );

        $this->assertStringContainsString("route('goods-receipts.index')", $source);
        $this->assertStringNotContainsString("route('quotations.index')", $source);
    }

    public function test_invoice_limit_compares_the_same_whole_rupiah_shown_in_ui(): void
    {
        $controller = file_get_contents(
            dirname(__DIR__, 2) . '/app/Http/Controllers/InvoiceCustomerController.php'
        );
        $createView = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/invoice_customers/create.blade.php'
        );

        $this->assertStringContainsString('round($baseAmount) > round($remainingPreTax)', $controller);
        $this->assertStringContainsString('invoiceBaseRupiah > poRemainingBaseRupiah', $createView);
        $this->assertStringContainsString('Math.max(0, poRemainingBase - baseAmount)', $createView);
    }

    public function test_sales_invoice_show_uses_po_customer_visual_structure(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/invoice_customers/show.blade.php'
        );

        foreach (['main-card', 'card-header-custom', 'info-box-bg', 'table-modern', 'summary-card'] as $class) {
            $this->assertStringContainsString($class, $source);
        }
    }

    public function test_parent_invoice_is_system_assigned_to_first_active_invoice(): void
    {
        $controller = file_get_contents(
            dirname(__DIR__, 2) . '/app/Http/Controllers/InvoiceCustomerController.php'
        );
        $createView = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/invoice_customers/create.blade.php'
        );

        $this->assertStringContainsString('rootInvoiceForPo', $controller);
        $this->assertStringContainsString(
            '$data[\'parent_invoice_id\'] = $rootInvoice?->id',
            $controller
        );
        $this->assertStringNotContainsString('name="parent_invoice_id"', $createView);
        $this->assertStringContainsString('Ditentukan otomatis oleh sistem', $createView);
    }

    public function test_follow_up_invoice_uses_remaining_pre_tax_base_before_tax(): void
    {
        $controller = file_get_contents(
            dirname(__DIR__, 2) . '/app/Http/Controllers/InvoiceCustomerController.php'
        );
        $createView = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/invoice_customers/create.blade.php'
        );

        $this->assertStringContainsString('remainingPreTaxAmount', $controller);
        $this->assertStringContainsString('CASE WHEN dp_amount > 0 THEN dp_amount ELSE subtotal END', $controller);
        $this->assertStringContainsString("return round((\$derivedPphAmount / \$poPreTax) * 100, 4);", $controller);
        $this->assertStringContainsString("$('#has_pph23').prop('checked', poPphPercent > 0);", $createView);
        $this->assertStringContainsString('Payment Amount melebihi sisa PO Amount.', $controller);
    }

    public function test_customer_invoice_prints_do_not_disclose_remaining_amount(): void
    {
        $controller = file_get_contents(
            dirname(__DIR__, 2) . '/app/Http/Controllers/InvoiceCustomerController.php'
        );
        $this->assertStringContainsString('publicImageDataUri', $controller);
        $this->assertStringContainsString("'data:' . \$mime . ';base64,'", $controller);

        foreach (['print.blade.php', 'print_invoice.blade.php'] as $file) {
            $source = file_get_contents(
                dirname(__DIR__, 2) . '/resources/views/invoice_customers/' . $file
            );
            $this->assertStringNotContainsString('Remaining Amount', $source);
            $this->assertStringNotContainsString('$invoice->remaining_amount', $source);
            $this->assertStringContainsString("\$printAssets['logo']", $source);
            $this->assertStringNotContainsString('Grand Galaxy City', $source);

            if ($file === 'print.blade.php') {
                $this->assertStringContainsString("\$printAssets['signature']", $source);
            } else {
                $this->assertStringNotContainsString("\$printAssets['signature']", $source);
                $this->assertStringContainsString('tanda tangan manual', $source);
            }
        }
    }
}
