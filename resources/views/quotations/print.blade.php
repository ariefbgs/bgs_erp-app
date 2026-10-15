<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->quotation_number }}</title>
    @include('layouts.print-styles', ['bgsPrintProfile' => 'quotations-print'])
</head>
<body>
    <div class="print-container">
        @php
            $company = App\Models\Company::where('is_active', true)->first();
        @endphp

        {{-- HEADER --}}
        <table class="header-table">
            <tr>
                <td width="12%">
                    @php
                        $logoPath = null;

                        /*
                         * RM-005 canonical print logo resolution:
                         * 1. Company-configured logo
                         * 2. Canonical BGS fallback logo
                         */
                        if ($company && !empty($company->logo)) {
                            $configuredLogo = ltrim($company->logo, '/');

                            if (file_exists(public_path($configuredLogo))) {
                                $logoPath = public_path($configuredLogo);
                            }
                        }

                        if (!$logoPath) {
                            $canonicalLogo = public_path('uploads/companies/logo/LogoBGS.png');

                            if (file_exists($canonicalLogo)) {
                                $logoPath = $canonicalLogo;
                            }
                        }
                    @endphp
                    @if($logoPath)
                        <img src="{{ $logoPath }}" class="company-logo">
                    @endif
                </td>
                <td width="58%">
                    <div class="company-name">
                        {{ $company->name ?? 'PT. BAGAS GEMILANG SATWIKA' }}
                    </div>
                    <div class="company-info">
                        {!! nl2br(e($company->address ?? '-')) !!}<br>
                        Grand Galaxy City, {{ $company->city ?? '-' }} {{ $company->postal_code ?? '-' }}<br>
                        Telp : {{ $company->phone ?? '-' }} | Email : {{ $company->email ?? '-' }}
                    </div>
                </td>
                <td width="30%" class="quotation-banner-td">
                    <div class="quotation-banner">
                        QUOTATION
                    </div>
                </td>
            </tr>
        </table>

        {{-- QUOTATION METADATA INFO --}}
        <table class="quotation-info">
            <tr>
                <td><strong>Quotation Number</strong>{{ $quotation->quotation_number }}</td>
                <td><strong>Quotation Date</strong>{{ \Carbon\Carbon::parse($quotation->date)->format('d F Y') }}</td>
                <td><strong>Valid Until</strong>{{ \Carbon\Carbon::parse($quotation->valid_until)->format('d F Y') }}</td>
            </tr>
        </table>

        {{-- CUSTOMER DATA SECTION --}}
        <div class="customer-section">
            <div class="customer-title">Quotation Prepared For:</div>
            <div class="customer-name">{{ $quotation->customer->name ?? '-' }}</div>
            <div>{!! nl2br(e($quotation->customer->document_address ?? '-')) !!}</div>
            <div class="bgs-print-u-49228a50c38e">
                <strong>Telp :</strong> {{ $quotation->customer->phone ?? '-' }} 
                <span class="bgs-print-u-b15b63a1340e">|</span>
                <strong>PIC Contact :</strong> {{ $quotation->customer->pic_quotation_name ?? '-' }}
            </div>
        </div>

        {{-- ITEM RINCIAN TABLE --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th width="4%">No</th>
                    @if($quotation->show_image_on_print) 
                        <th width="8%">Image</th>
                    @endif
                    <th width="10%">Brand</th>
                    <th>Product Description</th>
                    <th width="6%">Qty</th>
                    <th width="8%">Unit</th>
                    <th width="14%">Unit Price (Rp)</th>
                    <th width="15%">Subtotal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->details as $index => $detail)
                @php
                    $productImagePath = null;
                    if(!empty($detail->product->image) && file_exists(public_path($detail->product->image))){
                        $productImagePath = public_path($detail->product->image);
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    @if($quotation->show_image_on_print)
                        <td class="text-center">
                            @if($productImagePath)
                                <img src="{{ $productImagePath }}" class="product-image">
                            @else
                                <span class="bgs-print-u-3321ecde3a46">-</span>
                            @endif
                        </td>
                    @endif
                    <td>{{ $detail->product->brand ?? '-' }}</td>
                    <td>
                        {{ $detail->product->name2 ?? '-' }}
                        <br><br>
                        @if($detail->specification || $detail->description)
                            <div class="meta-box meta-box-spec">
                                @if($detail->specification)
                                    <div class="meta-label"><i class="bi bi-gear-fill me-1"></i> Specification :</div>
                                    <small class="bgs-print-u-825afcead48f">{{ $detail->specification }}</small>
                                    <br>
                                @endif
                                @if($detail->description)
                                    <!--<div class="meta-label mt-2"><i class="bi bi-file-text-fill me-1"></i> Description :</div>-->
                                    <small class="bgs-print-u-825afcead48f">{{ $detail->description }}</small>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($detail->quantity, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $detail->product->unit ?? 'Pcs' }}</td>
                    <td class="text-right">{{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right bgs-print-u-77a316569984">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- TOTALS TABLE OUTFLOW --}}
        <table class="totals-container-table">
            <tr>
                <td>
                    <table class="totals-table">
                        <tr>
                            <td><strong>Subtotal</strong></td>
                            <td class="text-right">Rp {{ number_format($quotation->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if(($quotation->discount_amount ?? 0) > 0)
                        <tr>
                            <td class="text-danger"><strong>Discount</strong></td>
                            <td class="text-right text-danger">- Rp {{ number_format($quotation->discount_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @if(($quotation->tax_amount ?? 0) > 0)
                        <tr>
                            <td><strong>PPN {{ $quotation->tax_percent ?? 11 }}%</strong></td>
                            <td class="text-right">Rp {{ number_format($quotation->tax_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr class="grand-total">
                            <td><strong>GRAND TOTAL</strong></td>
                            <td class="text-right">Rp {{ number_format($quotation->total, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- REMARKS / NOTES --}}
        @if(!empty($quotation->notes))
        <div class="notes-box">
            <strong class="bgs-print-u-f6d8c8682ce9">
                Remarks / Notes:
            </strong>
            {!! nl2br(e($quotation->notes)) !!}
        </div>
        @endif

        {{-- PAYMENT TERMS & CONDITIONS (TWO COLUMNS) --}}
        <table class="two-column">
            <tr>
                <td class="bgs-print-u-eeb12a480bae">
                    <div class="info-card">
                        <div class="info-card-header">PAYMENT & DELIVERY TERMS</div>
                        <div class="info-card-body">
                            <strong>Payment Terms :</strong><br>
                            {{ $quotation->payment_terms ?? '100% in Advance by T/T' }}
                            <div class="bgs-print-u-c98a9f1869e2"></div>
                            <strong>Bank Transfer Account Destination :</strong><br>
                            @if($company && $company->bank_name)
                                {{ $company->bank_name }} - {{ $company->bank_account_number }}<br>
                                a/n {{ $company->bank_account_name }}
                            @else
                                BCA KCP - 5770926680<br>
                                Account Name: PT. Bagas Gemilang Satwika
                            @endif
                            <div class="bgs-print-u-c98a9f1869e2"></div>
                            <strong>Delivery Scope Time :</strong><br>
                            {{ $quotation->delivery_time ?? 'Ready stock before sold out' }}
                        </div>
                    </div> 
                </td>
                <td class="bgs-print-u-78d01fcc6f50">
                    <div class="info-card">
                        <div class="info-card-header">TERMS & CONDITIONS</div>
                        <div class="info-card-body">
                            1. This official quotation is valid until the stated validity date parameters.<br>
                            2. Material items or goods sold cannot be cancelled, modified, or returned.<br>
                            3. Price Freight Franco Scope: {{ $quotation->customer->name ?? '-' }}.
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- SIGNATURE CORNER --}}
        <div class="signature-section">
            <div class="signature-box">
                <div class="bgs-print-u-80ad60ce35eb">Best Regards,</div>
                @php
                    $signaturePath = null;

                    if ($company && !empty($company->ttd_sales_support)) {
                        $configuredSignature = ltrim($company->ttd_sales_support, '/');

                        if (file_exists(public_path($configuredSignature))) {
                            $signaturePath = public_path($configuredSignature);
                        }
                    }

                    if (!$signaturePath) {
                        $canonicalSignature = public_path('uploads/ttd/ttd_afni.jpg');

                        if (file_exists($canonicalSignature)) {
                            $signaturePath = $canonicalSignature;
                        }
                    }
                @endphp
                @if($signaturePath)
                    <img src="{{ $signaturePath }}" class="signature-image">
                @endif
                <div>
                    <div class="signature-name">{{ $company->pic_sales_support ?? '-' }}</div>
                </div>
                <div class="signature-position">Sales Support</div>
            </div>
        </div>
    </div>
</body>
</html>