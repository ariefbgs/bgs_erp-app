<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">

<title>
    PO Supplier - {{ $poSupplier->po_supplier_number }}
</title>

<style>

    @page {
        size: A4 portrait;
        margin: 12mm 12mm;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: DejaVu Sans, sans-serif;
        font-size: 9.5px;
        color: #111827;
        line-height: 1.35;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    .document {
        width: 100%;
    }


    /* =========================================================
       HEADER
    ========================================================= */

    .header-table {
        border-bottom: 3px solid #1f2937;
        margin-bottom: 12px;
        padding-bottom: 8px;
    }

    .header-table td {
        vertical-align: middle;
    }

    .logo-cell {
        width: 11%;
    }

    .logo {
        width: 62px;
        max-height: 70px;
        object-fit: contain;
    }

    .company-cell {
        width: 54%;
        padding-left: 5px;
    }

    .company-name {
        font-size: 17px;
        font-weight: bold;
        margin-bottom: 2px;
    }

    .company-info {
        font-size: 9px;
        line-height: 1.3;
    }

    .title-cell {
        width: 35%;
        text-align: right;
        vertical-align: top !important;
        padding-top: 4px;
    }

    .document-title {
        font-size: 17px;
        font-weight: bold;
        letter-spacing: 1px;
    }


    /* =========================================================
       PO REGISTRY
    ========================================================= */

    .registry {
        margin-bottom: 10px;
    }

    .registry td {
        border: 1px solid #d1d5db;
        background: #f9fafb;
        padding: 6px 8px;
        vertical-align: top;
    }

    .registry-label {
        font-weight: bold;
        display: block;
        margin-bottom: 2px;
    }


    /* =========================================================
       RELATIONSHIP / SUPPLIER INFORMATION
    ========================================================= */

    .relationship-table {
        margin-bottom: 10px;
    }

    .relationship-table td {
        width: 50%;
        padding: 0 4px 0 0;
        vertical-align: top;
    }

    .relationship-table td:last-child {
        padding-left: 4px;
        padding-right: 0;
    }

    .info-card {
        border: 1px solid #d1d5db;
    }

    .info-card-header {
        background: #747f91;
        color: #fff;
        font-weight: bold;
        padding: 7px 9px;
        font-size: 10px;
    }

    .info-card-body {
        padding: 8px 9px;
        min-height: 67px;
    }

    .info-row {
        margin-bottom: 3px;
    }

    .info-label {
        font-weight: bold;
    }


    /* =========================================================
       PRODUCT TABLE
    ========================================================= */

    .items {
        margin-top: 4px;
        page-break-inside: auto;
    }

    .items thead {
        display: table-header-group;
    }

    .items tr {
        page-break-inside: avoid;
    }

    .items th {
        background: #747f91;
        color: #fff;
        border: 1px solid #5f6978;
        font-weight: bold;
        text-align: center;
        text-transform: uppercase;
        padding: 6px 4px;
        font-size: 8.8px;
    }

    .items td {
        border: 1px solid #d1d5db;
        padding: 5px 4px;
        vertical-align: middle;
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }


    /* =========================================================
       TOTALS
    ========================================================= */

    .summary {
        width: 43%;
        margin-left: auto;
        margin-top: 8px;
        margin-bottom: 12px;
        page-break-inside: avoid;
    }

    .summary td {
        border: 1px solid #e5e7eb;
        padding: 5px 8px;
    }

    .summary-label {
        font-weight: bold;
    }

    .summary-after-discount td {
        background: #f9fafb;
    }

    .grand-total td {
        background: #747f91;
        color: #fff;
        font-weight: bold;
        font-size: 10px;
    }


    /* =========================================================
       NOTES
    ========================================================= */

    .notes {
        background: #f9fafb;
        border-left: 4px solid #1f2937;
        padding: 8px 10px;
        margin-top: 8px;
        margin-bottom: 14px;
        min-height: 43px;
        page-break-inside: avoid;
    }

    .notes-title {
        font-weight: bold;
        margin-bottom: 3px;
    }


    /* =========================================================
       SIGNATURE
    ========================================================= */

    .signature-section {
        margin-top: 16px;
        page-break-inside: avoid;
    }

    .best-regards {
        font-weight: normal;
        margin-bottom: 3px;
    }

    .signature-image {
        max-width: 145px;
        max-height: 78px;
        object-fit: contain;
        display: block;
        margin: 2px 0;
    }

    .signature-name {
        font-weight: bold;
        font-size: 10px;
        margin-top: 2px;
    }

    .signature-line {
        width: 145px;
        border-top: 1px solid #111;
        margin-top: 3px;
    }

    .signature-position {
        margin-top: 3px;
        color: #4b5563;
    }

</style>

</head>

<body>

@php

    /*
    |--------------------------------------------------------------------------
    | Transaction financial values
    |--------------------------------------------------------------------------
    */

    $subtotal =
        (float) ($poSupplier->subtotal ?? 0);

    $discountPercent =
        (float) ($poSupplier->discount_percent ?? 0);

    $discountAmount =
        (float) ($poSupplier->discount_amount ?? 0);

    $subtotalAfterDiscount =
        $subtotal - $discountAmount;

    $taxPercent =
        (float) ($poSupplier->tax_percent ?? 0);

    $taxAmount =
        (float) ($poSupplier->tax_amount ?? 0);

    $grandTotal =
        (float) ($poSupplier->total ?? 0);


    /*
    |--------------------------------------------------------------------------
    | Company Logo
    |--------------------------------------------------------------------------
    */

    $logoPath = null;

    if (
        $company &&
        !empty($company->logo) &&
        file_exists(public_path($company->logo))
    ) {
        $logoPath =
            public_path($company->logo);

    } elseif (
        file_exists(
            public_path(
                'uploads/companies/logo/LogoBGS.png'
            )
        )
    ) {
        $logoPath =
            public_path(
                'uploads/companies/logo/LogoBGS.png'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Sales Support Signature
    |--------------------------------------------------------------------------
    */

    $signaturePath = null;

    if (
        $company &&
        !empty($company->ttd_sales_support) &&
        file_exists(
            public_path(
                $company->ttd_sales_support
            )
        )
    ) {
        $signaturePath =
            public_path(
                $company->ttd_sales_support
            );

    } else {

        $signatureFiles =
            glob(
                public_path(
                    'uploads/companies/ttd_sales_support/*'
                )
            );

        if (
            is_array($signatureFiles) &&
            count($signatureFiles) > 0
        ) {
            $signaturePath =
                $signatureFiles[0];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Convert local assets to embedded Data URI
    |--------------------------------------------------------------------------
    |
    | DomPDF can fail to render raw Windows filesystem paths.
    | Embedding the image bytes removes dependency on filesystem URL access.
    |
    */

    $logoSrc = null;

    if (
        !empty($logoPath) &&
        file_exists($logoPath)
    ) {

        $logoMime =
            mime_content_type($logoPath)
            ?: 'image/png';

        $logoSrc =
            'data:' .
            $logoMime .
            ';base64,' .
            base64_encode(
                file_get_contents($logoPath)
            );
    }


    $signatureSrc = null;

    if (
        !empty($signaturePath) &&
        file_exists($signaturePath)
    ) {

        $signatureMime =
            mime_content_type($signaturePath)
            ?: 'image/png';

        $signatureSrc =
            'data:' .
            $signatureMime .
            ';base64,' .
            base64_encode(
                file_get_contents($signaturePath)
            );
    }

@endphp


<div class="document">


{{-- ============================================================
     HEADER
============================================================ --}}

<table class="header-table">

    <tr>

        <td class="logo-cell">

            @if($logoSrc)

                <img
                    src="{{ $logoSrc }}"
                    class="logo"
                    alt="Logo"
                >

            @endif

        </td>


        <td class="company-cell">

            <div class="company-name">

                {{
                    $company->name
                    ?? 'PT Bagas Gemilang Satwika'
                }}

            </div>


            <div class="company-info">

                {!! nl2br(
                    e(
                        $company->address
                        ?? '-'
                    )
                ) !!}

                @if(!empty($company->city))
                    <br>{{ $company->city }}
                    {{ $company->postal_code ?? '' }}
                @endif

                @if(!empty($company->phone))
                    <br>
                    Telp :
                    {{ $company->phone }}
                @endif

                @if(!empty($company->email))
                    <br>
                    Email :
                    {{ $company->email }}
                @endif

            </div>

        </td>


        <td class="title-cell">

            <div class="document-title">

                PURCHASE ORDER

            </div>

        </td>

    </tr>

</table>


{{-- ============================================================
     PO SUPPLIER REGISTRY
============================================================ --}}

<table class="registry">

    <tr>

        <td width="58%">

            <span class="registry-label">
                PO Supplier Number
            </span>

            {{
                $poSupplier->po_supplier_number
                ?? '-'
            }}

        </td>


        <td width="42%">

            <span class="registry-label">
                PO Date
            </span>

            {{
                $poSupplier->po_date
                    ? \Carbon\Carbon::parse(
                        $poSupplier->po_date
                    )->format('d F Y')
                    : '-'
            }}

        </td>

    </tr>

</table>


{{-- ============================================================
     RELATIONSHIP INFORMATION
============================================================ --}}

<table class="relationship-table">

    <tr>

        {{-- SUPPLIER --}}
        <td>

            <div class="info-card">

                <div class="info-card-header">

                    PO SUPPLIER FOR

                </div>


                <div class="info-card-body">

                    <div class="info-row">

                        <strong>
                            {{
                                $poSupplier->supplier->name
                                ?? '-'
                            }}
                        </strong>

                    </div>


                    <div class="info-row">

                        {!! nl2br(
                            e(
                                $poSupplier->supplier->address
                                ?? '-'
                            )
                        ) !!}

                    </div>


                    @if(
                        !empty(
                            $poSupplier->supplier->phone
                        )
                    )

                        <div class="info-row">

                            Telp :
                            {{
                                $poSupplier->supplier->phone
                            }}

                        </div>

                    @endif

                </div>

            </div>

        </td>


        {{-- CUSTOMER / SOURCE PO --}}
        <td>

            <div class="info-card">

                <div class="info-card-header">

                    CUSTOMER PO REFERENCE

                </div>


                <div class="info-card-body">

                    <div class="info-row">

                        <span class="info-label">
                            Customer :
                        </span>

                        {{
                            $poSupplier->customer->name
                            ?? '-'
                        }}

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            PO Customer :
                        </span>

                        {{
                            $poSupplier->poCustomer->po_number
                            ?? '-'
                        }}

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            PO Status :
                        </span>

                        {{
                            ucfirst(
                                $poSupplier->status
                                ?? '-'
                            )
                        }}

                    </div>

                </div>

            </div>

        </td>

    </tr>

</table>


{{-- ============================================================
     PRODUCT DETAILS
============================================================ --}}

<table class="items">

    <thead>

        <tr>

            <th width="4%">
                No
            </th>

            <th width="12%">
                Brand
            </th>

            <th width="13%">
                Code
            </th>

            <th>
                Description
            </th>

            <th width="7%">
                Qty
            </th>

            <th width="7%">
                Unit
            </th>

            <th width="14%">
                Price
            </th>

            <th width="14%">
                Subtotal
            </th>

        </tr>

    </thead>


    <tbody>

        @forelse(
            $poSupplier->details
            as $index => $detail
        )

            <tr>

                <td class="text-center">

                    {{ $index + 1 }}

                </td>


                <td class="text-center">

                    {{
                        $detail->product->brand
                        ?? '-'
                    }}

                </td>


                <td class="text-center">

                    {{
                        $detail->product->product_code
                        ?? '-'
                    }}

                </td>


                <td>

                    {{
                        $detail->product->name
                        ?? '-'
                    }}

                </td>


                <td class="text-center">

                    {{
                        number_format(
                            (float) (
                                $detail->quantity
                                ?? 0
                            ),
                            0,
                            ',',
                            '.'
                        )
                    }}

                </td>


                <td class="text-center">

                    {{
                        $detail->product->unit
                        ?? '-'
                    }}

                </td>


                <td class="text-right">

                    Rp {{
                        number_format(
                            (float) (
                                $detail->purchase_price
                                ?? 0
                            ),
                            0,
                            ',',
                            '.'
                        )
                    }}

                </td>


                <td class="text-right">

                    Rp {{
                        number_format(
                            (float) (
                                $detail->subtotal
                                ?? 0
                            ),
                            0,
                            ',',
                            '.'
                        )
                    }}

                </td>

            </tr>


        @empty

            <tr>

                <td
                    colspan="8"
                    class="text-center"
                >

                    No product detail available.

                </td>

            </tr>

        @endforelse

    </tbody>

</table>


{{-- ============================================================
     FINANCIAL SUMMARY
============================================================ --}}

<table class="summary">

    <tr>

        <td class="summary-label">

            Subtotal

        </td>

        <td class="text-right">

            Rp {{
                number_format(
                    $subtotal,
                    0,
                    ',',
                    '.'
                )
            }}

        </td>

    </tr>


    <tr>

        <td class="summary-label">

            Discount
            ({{ number_format(
                $discountPercent,
                2,
                ',',
                '.'
            ) }}%)

        </td>

        <td class="text-right">

            - Rp {{
                number_format(
                    $discountAmount,
                    0,
                    ',',
                    '.'
                )
            }}

        </td>

    </tr>


    <tr class="summary-after-discount">

        <td class="summary-label">

            Subtotal After Discount

        </td>

        <td class="text-right">

            Rp {{
                number_format(
                    $subtotalAfterDiscount,
                    0,
                    ',',
                    '.'
                )
            }}

        </td>

    </tr>


    <tr>

        <td class="summary-label">

            PPN
            {{ number_format(
                $taxPercent,
                2,
                ',',
                '.'
            ) }}%

        </td>

        <td class="text-right">

            Rp {{
                number_format(
                    $taxAmount,
                    0,
                    ',',
                    '.'
                )
            }}

        </td>

    </tr>


    <tr class="grand-total">

        <td>

            GRAND TOTAL

        </td>

        <td class="text-right">

            Rp {{
                number_format(
                    $grandTotal,
                    0,
                    ',',
                    '.'
                )
            }}

        </td>

    </tr>

</table>


{{-- ============================================================
     NOTES
============================================================ --}}

<div class="notes">

    <div class="notes-title">

        NOTES :

    </div>

    {!! nl2br(
        e(
            $poSupplier->notes
            ?? '-'
        )
    ) !!}

</div>


{{-- ============================================================
     SIGNATURE
============================================================ --}}

<div class="signature-section">

    <div class="best-regards">

        Best Regards,

    </div>


    @if($signatureSrc)

        <img
            src="{{ $signatureSrc }}"
            class="signature-image"
            alt="Signature"
        >

    @endif


    <div class="signature-name">

        {{
            $company->pic_sales_support
            ?? '-'
        }}

    </div>


    <div class="signature-line"></div>


    <div class="signature-position">

        Sales Support

    </div>

</div>


</div>

</body>
</html>
