<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    @include('layouts.print-styles', ['bgsPrintProfile' => 'invoice_customers-print'])
</head>
<body>
    <div class="print-container">
        @php
            $subtotal     = (float)($invoice->subtotal ?? 0);
            $discount     = (float)($invoice->discount_amount ?? 0);
            $tax          = (float)($invoice->tax_amount ?? 0);      // PPN
            $pph23        = (float)($invoice->pph23_amount ?? 0);    // PPH23
            $dp_amount    = (float)($invoice->dp_amount ?? 0);

            // DPP = Subtotal - Diskon
            $dpp = $subtotal - $discount;

            // Grand Total = DPP + PPN - PPH23
            $grandTotal = $dpp + $tax - $pph23;


        @endphp

        {{-- HEADER --}}
        <table class="header-table">
            <tr>
                <td width="10%">
                    @if($printAssets['logo'] ?? null)
                        <img src="{{ $printAssets['logo'] }}" class="company-logo">
                    @endif
                </td>
                <td class="bgs-print-u-28e8259a7e55">
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
                <td><strong>Invoice Amount</strong></td>
                <td class="text-right">Rp {{ number_format((($invoice->dp_amount ?? 0) > 0 ? $invoice->dp_amount : $invoice->subtotal), 0, ',', '.') }}</td>
            </tr>
            @if(($invoice->discount_amount ?? 0) > 0)
                <tr>
                    <td><strong>Discount</strong></td>
                    <td class="text-right">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if($invoice->allocation_mode === 'PERCENTAGE' && ($invoice->dp_amount ?? 0) > 0)
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
        <div class="bgs-print-u-695de9d0bd07">
            <div class="bgs-print-u-83da589a4348">
                <div class="bgs-print-u-d1ebff0de97f">
                    PAYMENT &amp; DELIVERY TERMS
                </div>
                <div class="bgs-print-u-a32a28a167c1">
                    <strong>Payment Terms :</strong><br>
                    {{ $invoice->payment_terms ?? $invoice->poCustomer->payment_terms ?? '100% in Advance by T/T' }}
                    <div class="bgs-print-u-c98a9f1869e2"></div>
                    <strong>Delivery Scope Time :</strong><br>
                    {{ $invoice->delivery_time ?? 'Ready stock before sold out' }}
                    <div class="bgs-print-u-c98a9f1869e2"></div>
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
        

        {{-- SIGNATURE CORNER (menggunakan Grand Total yang sudah dihitung untuk menentukan ttd) --}}
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-title">Best Regards,</div>
                
                @if($printAssets['signature'] ?? null)
                    <img src="{{ $printAssets['signature'] }}" class="signature-image">
                @else
                    <div class="bgs-print-u-8f16685ce914"></div>
                @endif


                {{-- Garis Tanda Tangan (Garis di atas nama) --}}
                <div class="bgs-print-u-08e59b316056"></div>
                
                {{-- Nama --}}
                <div class="signature-name">
                    {{ $company->pic_inv ?? 'Authorized Signature' }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
