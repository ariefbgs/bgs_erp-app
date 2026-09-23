<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
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

        .invoice-title {
            text-align: right;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #111827;
        }

        /* INVOICE INFO */
        .invoice-info {
            margin-bottom: 12px;
        }

        .invoice-info td { 
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
        {{-- HEADER --}}
        <table class="header-table">
            <tr>
                <td width="10%">
                    @if($printAssets['logo'] ?? null)
                        <img src="{{ $printAssets['logo'] }}" class="company-logo">
                    @endif
                </td>
                <td style="width:60%;">
                    <div class="company-name">
                        {{ $company->name ?? 'PT. BAGAS GEMILANG SATWIKA' }}
                    </div>
                    <div class="company-info">
                        @if($company?->address){!! nl2br(e($company->address)) !!}<br>@endif
                        @if($company?->city || $company?->postal_code){{ trim(($company->city ?? '') . ' ' . ($company->postal_code ?? '')) }}<br>@endif
                        @if($company?->phone)Telp: {{ $company->phone }}@if($company->phone1 ?? null) / {{ $company->phone1 }}@endif<br>@endif
                        @if($company?->fax)Fax: {{ $company->fax }}<br>@endif
                        @if($company?->email)Email: {{ $company->email }}@endif
                    </div>
                </td>
                <td width="30%">
                    @if($invoice->type == 'proforma')
                        <div class="invoice-title">PROFORMA INVOICE</div>
                        @else
                        <div class="invoice-title">INVOICE</div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- INVOICE & PO INFO -->
        <table class="invoice-info">
            <tr>
                <td width="50%">
                    <strong>Invoice #</strong><br>
                    {{ $invoice->invoice_number }}
                </td>
                <td width="50%">
                    <strong>Invoice Date</strong><br>
                    {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d F Y') }}
                </td>
            </tr>

            <tr>
                <td>
                    <strong>PO Customer #</strong><br>
                    {{ $invoice->poCustomer->po_number ?? '-' }}
                </td>
                <td>
                    <strong>PO Customer Date</strong><br>
                    {{ \Carbon\Carbon::parse($invoice->poCustomer->po_date)->format('d F Y') }}
                </td>
            </tr>
        </table>

        <!-- INVOICE FOR -->
        <div class="section-card">
            <div class="section-header">INVOICE FOR</div>
            <div class="section-body">
                <strong>{{ $invoice->poCustomer->customer->name ?? '-' }}</strong><br>
                {!! nl2br(e($invoice->poCustomer->customer->document_address ?? '-')) !!}<br>
                Telp : {{ $invoice->poCustomer->customer->pic_invoice_phone ?? '-' }}<br>
                PIC : {{ $invoice->poCustomer->customer->pic_invoice_name ?? '-' }}<br>
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
                @foreach($invoice->details as $index => $detail)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $detail->product->brand ?? '-' }}</td>
                    <td class="text-center">{{ $detail->product->product_code ?? '-' }}</td>
                    <td>{{ $detail->product->name ?? '-' }}</td>
                    <td class="text-center">{{ number_format($detail->quantity, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $detail->product->unit ?? 'Pcs' }}</td>
                    <td class="text-right">{{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- TOTALS -->
        <table class="totals-table">
            <tr>
                <td><strong>Subtotal</strong></td>
                <td class="text-right">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if(($invoice->discount_amount ?? 0) > 0)
                <tr>
                    <td><strong>Discount</strong></td>
                    <td class="text-right">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if(($invoice->dp_amount ?? 0) > 0)
                <tr>
                    <td><strong>Payment {{ $invoice->dp_percent ?? 0 }}%</strong></td>
                    <td class="text-right">Rp {{ number_format($invoice->dp_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if(($invoice->tax_amount ?? 0) > 0)
                <tr>
                    <!--<td><strong>PPN {{ $invoice->tax_percent ?? 11 }}%</strong></td>-->
                    <td><strong>PPN </strong></td>
                    <td class="text-right">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if($invoice->pph23_amount != 0)
                <tr>
                    <!--<td><strong>PPH23 {{ $invoice->pph23_percent ?? 2 }}</strong></td>-->
                    <td><strong>PPH23 </strong></td>
                    <td class="text-right">Rp {{ number_format(($invoice->pph23_amount), 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td><strong>GRAND TOTAL</strong></td>
                <td class="text-right">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
            </tr>
        </table>

        <!-- ========================================================= -->
        <!-- PAYMENT & DELIVERY TERMS (Info Card)                       -->
        <!-- ========================================================= -->
        <div style="margin-bottom: 15px;">
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; width: 100%;">
                <div style="background: #f3f4f6; padding: 6px 10px; font-size: 11px; font-weight: 700; border-bottom: 1px solid #e5e7eb;">
                    PAYMENT &amp; DELIVERY TERMS
                </div>
                <div style="padding: 10px; font-size: 10px; line-height: 1.5;">
                    <strong>Payment Terms :</strong><br>
                    {{ $invoice->payment_terms ?? $invoice->poCustomer->payment_terms ?? '100% in Advance by T/T' }}
                    <div style="margin-top: 4px;"></div>
                    <strong>Delivery Scope Time :</strong><br>
                    {{ $invoice->delivery_time ?? 'Ready stock before sold out' }}
                    <div style="margin-top: 4px;"></div>
                    <strong>Bank Transfer Account Destination :</strong><br>
                    @if($company && $company->bank_name)
                        {{ $company->bank_name }} - {{ $company->bank_account_number }}<br>
                        a/n {{ $company->bank_account_name }}
                    @else
                        BCA KCP - 5770926680<br>
                        Account Name: PT. Bagas Gemilang Satwika
                    @endif
                </div>
            </div>
        </div>

        <!-- NOTES -->
        @if(!empty($invoice->notes))
        <div class="notes-box">
            <strong>NOTES :</strong><br>{!! nl2br(e($invoice->notes)) !!}
        </div>
        @endif
        

        <!-- SIGNATURE -->
        <div class="signature-section">
            <div class="signature-box">
                <div style="margin-bottom:8px;">Best Regards,</div>
                    {{-- Print Invoice disediakan untuk tanda tangan manual. --}}
                    <div style="height:75px;"></div>
                    <div class="signature-name">
                        {{ $company->pic_inv ?? '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
