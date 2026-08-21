<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Receipt - {{ $receipt->receipt_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

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
            @page { size: A4; margin: 10mm 12mm; }
            body { background: #fff !important; padding: 0 !important; }
            .print-container { padding: 0 !important; margin: 0 !important; box-shadow: none; border-radius: 0; }
        }

        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 6px 8px; vertical-align: top; }

        .header-table {
            width: 100%;
            border-bottom: 3px solid #111827;
            margin-bottom: 12px;
            padding-bottom: 6px;
        }
        .company-logo { max-width: 70px; max-height: 70px; object-fit: contain; }
        .company-name { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .company-info { font-size: 10px; font-weight: 700; line-height: 1.2; color: #4b5563; }
        .do-title { text-align: right; font-size: 18px; font-weight: 700; letter-spacing: 1px; color: #111827; }

        .do-info { margin-bottom: 12px; }
        .do-info td { background: #f9fafb; border: 1px solid #e5e7eb; font-size: 12px; padding: 6px 10px; }

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
        }
        .section-body {
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
        }

        .items-table { margin-bottom: 12px; }
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
        .items-table tbody tr:nth-child(even) { background: #f9fafb; }

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
            color: #374151;
            margin-bottom: 8px;
            display: block;
            text-transform: capitalize;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 110px;
            margin: 8px auto 5px auto;
            font-size: 12px; 
        }

        .signature-label, .signature-line {
            margin-left: 0;
            text-align: left;
        }
    </style>
</head>
<body>
<div class="print-container">
    @php
        // Ambil data dari $receipt yang dikirim controller
        $deliveryOrder = $receipt->deliveryOrder;
        $poCustomer = $deliveryOrder->poCustomer ?? null;
        $customer = $poCustomer->customer ?? null;
        
        // Nomor invoice & faktur pajak dari receipt (case-sensitive)
        $invoiceNumber = $receipt->invoice_number ?? '-';
        $taxInvoiceNumber = $receipt->tax_invoice_number ?? '-';
        
        // Nama penerima (received by) dari delivery order
        $receiverName = $deliveryOrder->receiver_name ?? ($customer->name ?? '-');
    @endphp

    <!-- HEADER -->
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
                <div class="company-name">{{ $company->name ?? 'PT. BAGAS GEMILANG SATWIKA' }}</div>
                <div class="company-info">
                    {!! nl2br(e($company->address ?? '-')) !!}<br>
                    Grand Galaxy City, {{ $company->city ?? '-' }} {{ $company->postal_code ?? '-' }}<br>
                    Telp : {{ $company->phone ?? '-' }}<br>
                    Email : {{ $company->email ?? '-' }}
                </div>
            </td>
            <td width="30%">
                <div class="do-title">DOCUMENT RECEIPT</div>
            </td>
        </tr>
    </table>

    <!-- RECEIPT INFO -->
    <table class="do-info">
        <tr>
            <td><strong>Receipt #</strong><br>{{ $receipt->receipt_number }}</td>
            <td><strong>Receipt Date</strong><br>{{ \Carbon\Carbon::parse($receipt->receipt_date)->format('d F Y') }}</td>
            <td><strong>Reference DO</strong><br>{{ $deliveryOrder->do_number ?? '-' }}</td>
        </tr>
    </table>

    <!-- DELIVERY TO (Customer) -->
    <div class="section-card">
        <div class="section-header">Delivery to: </div>
        <div class="section-body">
            <strong>{{ $customer->name ?? '-' }}</strong><br>
            {!! nl2br(e($customer->document_address ?? $customer->address ?? '-')) !!}<br>
            Telp : {{ $customer->phone ?? '-' }}<br>
            PIC : {{ $customer->pic_invoice_name ?? '-' }}
        </div>
    </div>

    <!-- DAFTAR DOKUMEN -->
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
            <td><strong>{{ $invoiceNumber }}</strong></td>
            <td>1 rangkap (Asli)</td>
        </tr>
        <tr>
            <td>Faktur Pajak</td>
            <td><strong>{{ $taxInvoiceNumber }}</strong></td>
            <td>
                @if(!empty($taxInvoiceNumber) && $taxInvoiceNumber != '-')
                    2 rangkap (Asli + Copy)
                @else
                    &nbsp;
                @endif
            </td>
        </tr>
        <tr>
            <td>PO Customer</td>
            <td><strong>{{ $poCustomer->po_number ?? '-' }}</strong></td>
            <td>1 rangkap</td>
        </tr>
        <tr>
            <td>Delivery Order</td>
            <td><strong>{{ $deliveryOrder->do_number ?? '-' }}</strong></td>
            <td>3 rangkap (Asli + Copy)</td>
        </tr>
        </tbody>
    </table>

    <!-- SIGNATURE DUA KOLOM -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-label">Best Regards,</div>
                @php
                    $signaturePath = null;
                    if($company && $company->ttd_do && file_exists(public_path($company->ttd_do))){
                        $signaturePath = public_path($company->ttd_do);
                    } elseif(file_exists(public_path('uploads/companies/signatures/1779281433_ttd_do_ttd_afni.jpg'))){
                        $signaturePath = public_path('uploads/companies/signatures/1779281433_ttd_do_ttd_afni.jpg');
                    }
                @endphp
                @if($signaturePath)
                    <img src="{{ $signaturePath }}" class="signature-image">
                @endif
                <div>
                    <div class="signature-name">{{ $company->pic_do ?? '-' }}</div>
                </div>
                <div class="signature-line"></div>
                <div class="signature-position">Delivery Support</div>
            </td>
            <td>
                <div class="signature-label">Received by,</div>
                <div style="height: 60px;"></div>
                <br><br><br>
                <div class="signature-line"></div>
            </td>
        </tr>
    </table>

    <div style="font-size: 9px; color: #6b7280; text-align: center; margin-top: 20px; border-top: 1px dashed #e5e7eb; padding-top: 8px;">
        Document Receipt ini adalah bukti serah terima dokumen asli dan copy.
    </div>
</div>
</body>
</html>