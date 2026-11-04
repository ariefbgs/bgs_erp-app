<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanda Terima - {{ $do->do_number }}</title>
    @include('layouts.print-styles', ['bgsPrintProfile' => 'delivery_orders-receipt_print'])
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

                        /*
                         * RM-005 canonical print logo resolution:
                         * 1. Company-configured logo
                         * 2. Canonical BGS fallback logo
                         */
                        $canonicalLogo = public_path('uploads/companies/logo/LogoBGS.png');

                        if (file_exists($canonicalLogo)) {
                            $logoPath = $canonicalLogo;
                        } elseif ($company && !empty($company->logo)) {
                            $configuredLogo = ltrim($company->logo, '/');

                            if (file_exists(public_path($configuredLogo))) {
                                $logoPath = public_path($configuredLogo);
                            }
                        }
                    @endphp
                    @if($logoPath)
                        <img src="{{ $logoPath }}" class="company-logo">
                    @endif
                </td>
                <td class="bgs-print-u-28e8259a7e55">
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
                <div class="bgs-print-u-c500155b62b4">Best Regards,</div>

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
                <div class="signature-name">
                    {{ $company->pic_do ?? '-' }}
                </div>
            </div>
        </div>
    </div>
</body>
</html> 