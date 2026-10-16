<!DOCTYPE html>
<html lang="id">
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Order - {{ $do->do_number }}</title>
    @include('layouts.print-styles', ['bgsPrintProfile' => 'delivery_orders-print'])
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
                    <div class="do-title">DELIVERY ORDER</div>
                </td>
            </tr>
        </table>

        <!-- DO INFO -->
        <table class="do-info">
            <tr>
                <td width="25%"><strong>Delivery Order #</strong><br>{{ $do->do_number }}</td>
                <td width="25%"><strong>Delivery Date</strong><br>{{ \Carbon\Carbon::parse($do->delivery_date)->format('d F Y') }}</td>
            </tr>
            <tr>
                <td width="25%"><strong>PO Customer #</strong><br>{{ $do->poCustomer->po_number ?? '-' }}</td>
                <td width="25%"><strong>PO Date</strong><br>{{ $do->poCustomer->po_date ? \Carbon\Carbon::parse($do->poCustomer->po_date)->format('d F Y') : '-' }}</td>
            </tr>
        </table>

        <!-- DO FOR -->
        <div class="section-card">
            <div class="section-header">Delivery to:</div>
            <div class="section-body">
                <strong>{{ $do->poCustomer->customer->name ?? $do->poCustomer->customer_name ?? '-' }}</strong><br>
                {!! nl2br(e($do->shipping_address ?? '-')) !!}<br>
                Telp : {{ $do->poCustomer->customer->pic_do_phone ?? '-' }}<br>
                PIC : {{ $do->poCustomer->customer->pic_do_name ?? '-' }}
            </div>
        </div>

        <!-- ITEMS TABLE -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th width="12%">Code</th>
                    <th width="12%">Brand</th>
                    <th>Description</th>
                    <th width="7%">Qty</th>
                    <th width="7%">Unit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($do->details as $index => $detail)
                @php
                    $productImagePath = null;
                    if(!empty($detail->product->image) && file_exists(public_path($detail->product->image))){
                        $productImagePath = public_path($detail->product->image);
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    @if($do->show_image_on_print)
                        <td class="text-center">
                            @if($productImagePath)
                                <img src="{{ $productImagePath }}" class="product-image">
                            @else
                                -
                            @endif
                        </td>
                    @endif
                    <td class="text-center">{{ $detail->product->product_code ?? '-' }}</td>
                    <td class="text-center">{{ $detail->product->brand ?? '-' }}</td>
                    <td>{{ $detail->product->name ?? '-' }}</td>
                    <td class="text-center">{{ number_format($detail->quantity, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $detail->product->unit ?? 'Pcs' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- SIGNATURE DUA KOLOM PASTI TIDAK BERGUMPAL -->
        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-label">Best Regards,</div>
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
                    <div class="signature-label">Received by,</div>
                    <div class="bgs-print-u-8f16685ce914"></div>
                    <br><br><br>
                    <div class="signature-line"></div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>