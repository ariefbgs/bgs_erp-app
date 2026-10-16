<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Receipt - {{ $receipt->receipt_number }}</title>
    @include('layouts.print-styles', ['bgsPrintProfile' => 'document-receipts-print'])
</head>
<body>
<div class="print-container">
    @php
        $poCustomer = $receipt->poCustomer;
        $customer = $poCustomer->customer ?? null;

        $deliveryOrders = $receipt->deliveryOrders;
        $invoices = $receipt->invoices;

        $primaryDeliveryOrder = $deliveryOrders->first();

        $receiverName =
            $primaryDeliveryOrder->receiver_name
            ?? ($customer->name ?? '-');
    @endphp

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td width="10%">
                @php
                    $logoPath = null;

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
            <td class="bgs-print-u-28e8259a7e55">
                <div class="company-name">{{ $company->name ?? 'PT. BAGAS GEMILANG SATWIKA' }}</div>
                <div class="company-info">
                    {!! nl2br(e($company->address ?? '-')) !!}<br>
                    Grand Galaxy City, {{ $company->city ?? '-' }} {{ $company->postal_code ?? '-' }}<br>
                    Telp : {{ $company->phone ?? '-' }}<br>
                    Email : {{ $company->email ?? '-' }}
                </div>
            </td>
            <td width="30%">
                <div class="do-title">TANDA TERIMA DOKUMEN</div>
            </td>
        </tr>
    </table>

    <!-- RECEIPT INFO -->
    <table class="do-info">
        <tr>
            <td>
                <strong>Receipt #</strong><br>
                {{ $receipt->receipt_number }}
            </td>
            <td>
                <strong>Receipt Date</strong><br>
                {{ \Carbon\Carbon::parse($receipt->receipt_date)->format('d F Y') }}
            </td>
            <td>
                <strong>Status</strong><br>
                {{ ucfirst($receipt->status ?? '-') }}
            </td>
        </tr>
    </table>

    <!-- CUSTOMER INFORMATION -->
    <div class="section-card">
        <div class="section-header">Customer Information</div>
        <div class="section-body">
            <strong>{{ $customer->name ?? '-' }}</strong><br>

            PIC Customer :
            {{ $customer->pic_invoice_name ?? '-' }}<br>

            PIC Phone :
            {{ $customer->pic_invoice_phone ?? '-' }}<br>

            Address :
            {!! nl2br(e($customer->document_address ?? '-')) !!}<br>

            Email :
            {{ $customer->email ?? '-' }}
        </div>
    </div>

    <!-- BGS unified document list -->
<p class="bgs-print-u-e36c5878cdb9">Berikut daftar dokumen yang dikirim untuk diterima oleh pihak customer:</p>
@include('document-receipts.document-list')
<!-- SIGNATURE DUA KOLOM -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-label">Pengirim,</div>
                @php
                    $signaturePath = null;

                    if ($company && !empty($company->ttd_do)) {
                        $configuredSignature = ltrim($company->ttd_do, '/');

                        if (file_exists(public_path($configuredSignature))) {
                            $signaturePath = public_path($configuredSignature);
                        }
                    }

                    if (!$signaturePath) {
                        $canonicalSignature = public_path('uploads/ttd/ttd_do.jpg');

                        if (file_exists($canonicalSignature)) {
                            $signaturePath = $canonicalSignature;
                        }
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
                <div class="signature-label">Penerima</div>
                <div class="bgs-print-u-8f16685ce914"></div>
                <br><br><br>
                <div class="signature-line"></div>
            </td>
        </tr>
    </table>

    <div class="bgs-print-u-95172dd3f0c9">
        Document Receipt ini adalah bukti serah terima dokumen asli dan copy.
    </div>
</div>
</body>
</html>