<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PO Supplier - {{ $poSupplier->po_supplier_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0; 
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            padding: 15px;
        }

        .print-container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 20px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        @media print {
            @page {
                size: A4;
                margin: 10mm 12mm;
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

            .no-print {
                display: none !important;
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            padding: 6px 8px;
            vertical-align: top;
        }

        /* HEADER */
        .header-table {
            width: 100%;
            border-bottom: 3px solid #111827;
            margin-bottom: 12px;
            padding-bottom: 6px;
        }

        .company-logo {
            max-width: 70px;
            max-height: 70px;
            object-fit: contain;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .company-info {
            font-size: 10px;
            font-weight: 700;
            line-height: 1.2;
            color: #4b5563;
        }

        .po-title {
            text-align: right;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #111827;
        }

        /* PURCHASE ORDER INFO */
        .po-info {
            margin-bottom: 12px;
        }

        .po-info td {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            font-size: 12px;
            padding: 6px 10px;
        }

        /* SECTION CARD */
        .section-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .section-header {
            background: #6f7786;
            color: #fff;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
        }

        .section-body {
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            line-height: 1.4;
        }

        /* SUBJECT BOX */
        .subject-box {
            background: linear-gradient(to right, #111827, #374151);
            color: #111827;
            padding: 6px 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 12px;
            font-weight: 600;
            display: block;
        }

        /* ITEMS TABLE */
        .items-table {
            margin-bottom: 12px;
        }

        .items-table thead th {
            background: #6f7786;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 5px;
            border: 1px solid #374151;
            text-align: center;
        }

        .items-table tbody td {
            border: 1px solid #d1d5db;
            font-size: 11px;
            font-weight: 600;
            padding: 5px 4px;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .product-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }

        /* TOTALS TABLE */
        .totals-table {
            width: 300px;
            margin-left: auto;
            margin-bottom: 15px;
            border-collapse: collapse;
        }

        .totals-table td {
            border: 1px solid #e5e7eb;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 10px;
        }

        .grand-total td {
            background: #6f7786;
            color: #fff;
            font-weight: 700;
        }

        /* NOTES */
        .notes-box {
            background: #f9fafb;
            border-left: 4px solid #111827;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 10px;
            line-height: 1.5;
        }

        /* TWO COLUMN */
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
            border-radius: 8px;
            overflow: hidden;
        }

        .info-card-header {
            background: #f3f4f6;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 700;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-card-body {
               padding: 10px;
            font-size: 10px;
            line-height: 1.5;
        }

        /* SIGNATURE AREA */
        .signature-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 250px;
        }

        .signature-title {
            margin-bottom: 5px;
            font-size: 12px;
        }

        .signature-name {
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            margin-top: 5px;
            display: inline-block;
        }

        .signature-position {
            font-size: 11px;
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

        .signature-label {
            font-size: 12px;
            font-weight: 500;
            color: #374151; /* Warna abu-abu gelap agar tidak terlalu kontras dengan teks utama */
            margin-bottom: 8px;
            display: block;
            text-transform: capitalize;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 110px;
            margin: 8px auto 5px auto;
            /* Tambahkan font-size jika ada teks di dalamnya */
            font-size: 12px; 
        }

        .signature-label, .signature-line {
            margin-left: 0;
            text-align: left;
        }

        .print-btn-container {
            text-align: center;
            margin-bottom: 15px;
        }

        .print-btn {
            background: #111827;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
        }

        .print-btn:hover {
            background: #000;
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
                <td width="10%">
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
                <td style="width:60%;">
                    <div class="company-name">
                        {{ $company->name ?? 'PT. BAGAS GEMILANG SATWIKA' }}
                    </div>
                    <div class="company-info">
                        {!! nl2br(e($company->address ?? '-')) !!}<br>
                        Grand Galaxy City, {{ $company->city ?? '-' }} {{ $company->postal_code ?? '-' }}<br>
                        Telp : {{ $company->phone ?? '-' }}<br>
                        Email : {{ $company->email ?? '-' }}
                    </div>
                </td>
                <td width="30%">
                    <div class="po-title">PURCHASE ORDER</div>
                </td>
            </tr>
        </table>

        <!-- PO SUPPLIER INFO -->
        <table class="po-info">
            <tr>
                <td><strong>PO Supplier Number</strong><br>{{ $poSupplier->po_supplier_number }}</td>
                <td><strong>PO Date</strong><br>{{ \Carbon\Carbon::parse($poSupplier->po_date)->format('d F Y') }}</td>
            </tr>
        </table>

        <!-- PO SUPPLIER FOR -->
        <div class="section-card">
            <div class="section-header">PO SUPPLIER FOR</div>
            <div class="section-body">
                <strong>{{ $poSupplier->supplier->name ?? '-' }}</strong><br>
                {!! nl2br(e($poSupplier->supplier->address ?? '-')) !!}
                Telp : {{ $poSupplier->supplier->phone ?? '-' }}<br>
            </div>
        </div>

        <!-- ITEMS TABLE -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th width="12%">Brand</th>
                    <th width="12%">Code</th>
                    <th>Description</th>
                    <th width="7%">Qty</th>
                    <th width="7%">Unit</th>
                    <th width="12%">Price</th>
                    <th width="12%">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($poSupplier->details as $index => $detail)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $detail->product->brand ?? '-' }}</td>
                    <td class="text-center">{{ $detail->product->product_code ?? '-' }}</td>
                    <td>{{ $detail->product->name ?? '-' }}</td>
                    <td class="text-center">{{ number_format($detail->quantity, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $detail->product->unit ?? 'Pcs' }}</td>
                    <td class="text-right">{{ number_format($detail->purchase_price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- TOTALS -->
        <table class="totals-table">
            <tr><td><strong>Subtotal</strong></td><td class="text-right">Rp {{ number_format($poSupplier->subtotal, 0, ',', '.') }}</td></tr>
            @if(($poSupplier->discount_amount ?? 0) > 0)
            <tr><td><strong>Discount</strong></td><td class="text-right">- Rp {{ number_format($poSupplier->discount_amount, 0, ',', '.') }}</td></tr>
            @endif
            @if(($poSupplier->tax_amount ?? 0) > 0)
            <tr><td><strong>PPN {{ $poSupplier->tax_percent ?? 11 }}%</strong></td><td class="text-right">Rp {{ number_format($poSupplier->tax_amount, 0, ',', '.') }}</td></tr>
            @endif
            <tr class="grand-total"><td><strong>GRAND TOTAL</strong></td><td class="text-right">Rp {{ number_format($poSupplier->total, 0, ',', '.') }}</td></tr>
        </table>

        <!-- NOTES -->
        @if(!empty($poSupplier->notes))
        <div class="notes-box">
            <strong>NOTES :</strong><br>{!! nl2br(e($poSupplier->notes)) !!}
        </div>
        @endif

        {{-- SIGNATURE CORNER --}}
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">Best Regards,</div>
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
                <div class="signature-line"></div>
                <div class="signature-position">Sales Support</div>
            </div>
        </div>
    </div>
</body>
</html>