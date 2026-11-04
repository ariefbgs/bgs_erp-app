@php
    // Profiles are static template choices; never select them from request input.
    $bgsAllowedPrintProfiles = ['customers-print', 'delivery_orders-print', 'delivery_orders-receipt_print', 'document-receipts-print', 'invoice_customers-print', 'invoice_customers-print_invoice', 'po_suppliers-print', 'quotations-print', 'reports-delivery_pdf', 'reports-payment_customer_pdf', 'reports-payment_supplier_pdf', 'reports-purchasing_pdf', 'reports-sales_pdf'];
    if (!in_array($bgsPrintProfile ?? '', $bgsAllowedPrintProfiles, true)) {
        throw new \RuntimeException('Unknown BGS print CSS profile.');
    }
    $bgsPrintCssPath = public_path('css/bgs-print.css');
    if (!is_readable($bgsPrintCssPath)) {
        throw new \RuntimeException('BGS print CSS is missing or unreadable.');
    }
    $bgsPrintCssFile = file_get_contents($bgsPrintCssPath);
    $bgsPrintStart = '/* BGS-PROFILE-BEGIN:'.$bgsPrintProfile.' */';
    $bgsPrintEnd = '/* BGS-PROFILE-END:'.$bgsPrintProfile.' */';
    $bgsPrintParts = explode($bgsPrintStart, $bgsPrintCssFile);
    if (count($bgsPrintParts) !== 2 || substr_count($bgsPrintParts[1], $bgsPrintEnd) !== 1) {
        throw new \RuntimeException('BGS print CSS profile is incomplete.');
    }
    $bgsSelectedPrintCss = explode($bgsPrintEnd, $bgsPrintParts[1], 2)[0];
@endphp
<style>{!! $bgsSelectedPrintCss !!}</style>
