<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanda Terima - {{ $do->do_number }}</title>
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

        .do-title {
            text-align: right;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #111827;
        }

        /* DO INFO */
        .do-info {
            margin-bottom: 12px;
        }

        .do-info td {
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
            font-size: 13px;
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
            font-size: 11px;
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
            font-size: 11px;
            line-height: 1.3;
        }

        /* SIGNATURE */
        .signature-section {
            margin-top: 15px;
        }

        .signature-box {
            width: 280px;
            font-size: 12px;
            margin-left: auto;
            text-align: center;
        }

        .signature-name {
            margin-top: 6px;
            font-size: 12px;
            font-weight: 700;
            border-top: 1px solid #9ca3af;
            padding-top: 5px;
        }
        .signature-placeholder {
            height: 95px;
            text-align: center;
            line-height: 95px;
            color: #666;
            font-size: 12px;
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

        <!-- HEADER dengan TABEL -->
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
                <td width="50%">
                    <div class="do-title">DOCUMENT RECEIPT</div>
                </td>
            </tr>
        </table>

       
        <!-- TANDA TERIMA INFO -->
        <table class="do-info">
            <tr>
                <td><strong>Delivery Order #</strong><br>{{ $do->do_number }}</td>
                <td><strong>Delivery Date</strong><br>{{ \Carbon\Carbon::parse($do->delivery_date)->format('d F Y') }}</td>
            </tr>
        </table>

        <!-- TANDA TERIMA FOR -->
        <div class="section-card">
            <div class="section-header">Delivery to:</div>
            <div class="section-body">
                <strong>{{ $do->poCustomer->customer->name ?? $do->poCustomer->customer_name ?? '-' }}</strong><br>
                {!! nl2br(e($do->poCustomer->customer->document_address ?? '-')) !!}<br>
                Telp : {{ $do->poCustomer->customer->phone ?? '-' }}<br>
                PIC : {{ $do->poCustomer->customer->pic_invoice_name ?? '-' }}
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="20%">Description</th>
                    <th width="20%">Nomor</th>
                    <th width="30%">Qty (Rangkap)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Invoice</td>
                    <td><strong>{{ $invoiceNumber ?? '-' }}</strong></td>
                    <td>1 rangkap (Asli)</td>
                </tr>
                <tr>
                    <td>Faktur Pajak</td>
                    <td><strong>{{ $taxInvoiceNumber ?? '-' }}</strong></td>
                    <td>2 rangkap (Asli + Copy)</td>
                </tr>
                <tr>
                    <td>PO Customer</td>
                    <td><strong>{{ $do->poCustomer->po_number ?? '-' }}</strong></td>
                    <td>1 rangkap</td>
                </tr>
                <tr>
                    <td>DO</td>
                    <td><strong>{{ $do->do_number }}</strong></td>
                    <td>3 rangkap (Asli + Copy)</td>
                </tr>
            </tbody>
        </table>

        {{-- SIGNATURE --}}
        <div class="signature-section">
            <div class="signature-box">
                <div style="margin-bottom:8px;">Best Regards,</div>

                    @php
                        $signaturePath = null;
                        if($company && $company->ttd_do && file_exists(public_path($company->ttd_do))){
                            $signaturePath = public_path($company->ttd_do);
                        } elseif(file_exists(public_path('uploads/companies/signatures/1779281433_ttd_do_ttd_afni.jpg'))){
                            $signaturePath = public_path('uploads/companies/signatures/1779281433_ttd_do_ttd_afni.jpg');
                        }
                    @endphp
                    @if($signaturePath)
                        <img src="{{ $signaturePath }}" class="signature-placeholder ">
                    @endif
                <div class="signature-name">
                    {{ $company->pic_do ?? '-' }}
                </div>
            </div>
        </div>
    </div>
</body>
</html> 