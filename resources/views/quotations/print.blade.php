<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->quotation_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        :root {
            --primary: #111827;
            --secondary: #374151;
            --accent: #F97316;
            --light: #F8FAFC;
            --border: #D1D5DB;
            --text: #1F2937;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
            color: #000000;
            padding: 10px 20px; /* Ditambah: Jarak atas-bawah dan kiri-kanan di browser preview */
            font-size: 11px;
        }

        .print-container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 8px 25px; /* Ditambah: Margin/Padding internal kiri dan kanan diperlebar */
        }

        @media print {
            @page {
                size: A4;
                /* Perubahan Utama: Margin kiri (left) dan kanan (right) kertas dinaikkan menjadi 20mm */
                margin: 50mm 3mm 15mm 3mm; 
            }

            body {
                background: #fff !important;
                padding: 0 !important;
            }

            .print-container {
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none;
                border-radius: 0;
            }

            .product-image {
                max-width: 40px !important;
                page-break-inside: avoid;
            }
            .company-logo, .signature-image {
                page-break-inside: avoid;
            }

            .no-print {
                display: none !important;
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        td, th {
            padding: 6px 8px;
            vertical-align: top;
        }

        /* HEADER SECTION */
        .header-table {
            border-bottom: 4px solid var(--accent);
            margin-bottom: 15px;
        }

        .company-logo {
            max-width: 80px;
            max-height: 80px;
            object-fit: contain;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 4px;
        }

        .company-info {
            font-size: 10px;
            font-weight: 500;
            line-height: 1.4;
            color: #374151;
        }

        .quotation-banner-td {
            text-align: right;
            vertical-align: middle;
        }

        .quotation-banner {
            background: var(--accent);
            color: white;
            text-align: center;
            padding: 8px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            display: inline-block;
        }

        /* QUOTATION METADATA INFO */
        .quotation-info td {
            background: #F8FAFC;
            border: 1px solid #E5E7EB;
            font-size: 11px;
            padding: 8px 10px;
            width: 33.33%;
        }

        .quotation-info strong {
            color: var(--accent);
            font-size: 10px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }

        /* CLIENT / CUSTOMER INFO AREA */
        .customer-section {
            background-color: #f8fafc;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .customer-title {
            font-size: 11px;
            font-weight: 700;
            color: #4b5563;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .customer-name {
            font-size: 14px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 2px;
        }

        /* ITEMS TABLE */
        .items-table thead th {
            background-color: #111827 !important;
            color: #ffffff !important;
            border: 1px solid #111827;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
            padding: 8px 5px;
        }

        .items-table tbody td {
            border: 1px solid #cbd5e1;
            font-size: 11px;
            padding: 6px 6px;
            color: #000000;
        }

        .items-table tbody tr:nth-child(even) {
            background: #F8FAFC;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .product-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        /* FINANCIAL SUMMARY TABLE */
        .totals-container-table {
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .totals-table {
            width: 320px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals-table td {
            border: 1px solid #D1D5DB;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .grand-total td {
            background-color: #111827 !important;
            color: #ffffff !important;
            font-weight: 700;
            font-size: 12px;
            border: 1px solid #111827;
        }

        /* METADATA REMARKS NOTE BOX */
        .notes-box {
            background: #F8FAFC;
            border-left: 4px solid var(--accent);
            padding: 10px 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 11px;
            line-height: 1.5;
        }

        /* TWO COLUMN TERMS BOXES */
        .two-column {
            width: 100%;
            margin-bottom: 15px;
        }

        .two-column td {
            width: 50%;
            vertical-align: top;
            padding: 0 6px;
        }

        .info-card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
            background: #ffffff;
        }

        .info-card-header {
            background: var(--primary);
            color: white;
            padding: 6px 10px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .info-card-body {
            padding: 10px;
            font-size: 10.5px;
            line-height: 1.4;
            color: #1f2937;
        }

        /* SIGNATURE AREA */
        .signature-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 250px;
        }

        .signature-name {
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            margin-top: 5px;
            border-bottom: 1px solid #6b7280;
            padding-bottom: 2px;
            display: inline-block;
        }

        .signature-position {
            font-size: 10px;
            color: #6B7280;
            margin-top: 2px;
        }

        .signature-image {
            max-width: 180px;
            max-height: 95px;
            object-fit: contain;
            display: block;
            margin: 5px 0;
        }
    </style>
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
                        if($company && $company->logo && file_exists(public_path($company->logo))){
                            $logoPath = public_path($company->logo);
                        } elseif(file_exists(public_path('uploads/companies/LogoBGS.png'))){
                            $logoPath = public_path('uploads/companies/LogoBGS.png');
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
            <div style="margin-top: 4px; font-size: 10.5px;">
                <strong>Telp :</strong> {{ $quotation->customer->phone ?? '-' }} 
                <span style="margin: 0 8px; color: #cbd5e1;">|</span> 
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
                                <span style="color: #94a3b8;">-</span>
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
                                    <small style="color: #6B7280;">{{ $detail->specification }}</small>
                                    <br>
                                @endif
                                @if($detail->description)
                                    <!--<div class="meta-label mt-2"><i class="bi bi-file-text-fill me-1"></i> Description :</div>-->
                                    <small style="color: #6B7280;">{{ $detail->description }}</small>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($detail->quantity, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $detail->product->unit ?? 'Pcs' }}</td>
                    <td class="text-right">{{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: 700;">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
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
            <strong style="font-size:11px; text-transform: uppercase; color: #475569; display:block; margin-bottom:2px;">
                Remarks / Notes:
            </strong>
            {!! nl2br(e($quotation->notes)) !!}
        </div>
        @endif

        {{-- PAYMENT TERMS & CONDITIONS (TWO COLUMNS) --}}
        <table class="two-column">
            <tr>
                <td style="padding-left: 0;">
                    <div class="info-card">
                        <div class="info-card-header">PAYMENT & DELIVERY TERMS</div>
                        <div class="info-card-body">
                            <strong>Payment Terms :</strong><br>
                            {{ $quotation->payment_terms ?? '100% in Advance by T/T' }}
                            <div style="margin-top: 4px;"></div>
                            <strong>Bank Transfer Account Destination :</strong><br>
                            @if($company && $company->bank_name)
                                {{ $company->bank_name }} - {{ $company->bank_account_number }}<br>
                                a/n {{ $company->bank_account_name }}
                            @else
                                BCA KCP - 5770926680<br>
                                Account Name: PT. Bagas Gemilang Satwika
                            @endif
                            <div style="margin-top: 4px;"></div>
                            <strong>Delivery Scope Time :</strong><br>
                            {{ $quotation->delivery_time ?? 'Ready stock before sold out' }}
                        </div>
                    </div> 
                </td>
                <td style="padding-right: 0;">
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
                <div style="margin-bottom: 5px; font-weight: 500;">Best Regards,</div>
                @php
                    $signaturePath = null;
                    if($company && $company->ttd_sales_support && file_exists(public_path($company->ttd_sales_support))){
                        $signaturePath = public_path($company->ttd_sales_support);
                    } elseif(file_exists(public_path('uploads/companies/signatures/1779281433_ttd_do_ttd_afni.jpg'))){
                        $signaturePath = public_path('uploads/companies/signatures/1779281433_ttd_do_ttd_afni.jpg');
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