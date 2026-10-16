<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Label - {{ $customer->name }}</title>
    @include('layouts.print-styles', ['bgsPrintProfile' => 'customers-print'])
</head>
<body>

    <div class="action-bar no-print">
        <button onclick="window.print();" class="btn btn-print">🖨️ Cetak Label</button>
        <button onclick="window.close();" class="btn btn-close">Tutup</button>
    </div>

    <div class="page-container">
        <div class="shipping-card">
            
            <div class="header-section">
                <div>
                    <span class="section-label">Pengirim (From):</span>
                    <h3 class="company-name">{{ $company->name ?? 'NAMA COMPANY ANDA' }}</h3>
                    <p class="address-detail">{{ $company->address ?? 'Alamat Perusahaan Belum Diatur' }}</p>
                    <p class="contact-info">📞 Telp: {{ $company->phone ?? $company->contact ?? '-' }}</p>
                </div>
                
                <div class="badge-type">
                    {{ request()->get('type') == 'dokumen' ? 'DOKUMEN' : 'PAKET BARANG' }}
                </div>
            </div>

            <div class="address-section">
                <span class="section-label">Penerima (To):</span>
                <h3 class="company-name bgs-print-u-05efd27c6e68">{{ $customer->name }}</h3>
                
                <p class="address-detail bgs-print-u-dd91158b3c97">
                    {{ request()->get('type') == 'dokumen' ? ($customer->document_address ?? $customer->address) : ($customer->shipping_address ?? $customer->address) }}
                </p>

                <p class="contact-info bgs-print-u-189407defb40">
                    @if(request()->get('type') == 'dokumen')
                        👤 **Attn / PIC (Finance):** {{ $customer->pic_invoice_name ?? '-' }} 
                        @if($customer->pic_invoice_phone) | 📞 {{ $customer->pic_invoice_phone }} @endif
                    @else
                        👤 **Attn / PIC (Delivery):** {{ $customer->pic_do_name ?? '-' }} 
                        @if($customer->pic_do_phone) | 📞 {{ $customer->pic_do_phone }} @endif
                    @endif
                </p>
            </div>

            @if(request()->get('type') != 'dokumen')
                <div class="warning-container">
                    <div class="warning-text-large">⚠️ JANGAN DIBANTING</div>
                    <div class="warning-subtext">SPARE PART</div>
                </div>
            @else
                <div class="warning-container bgs-print-u-f8919b74b186">
                    <div class="warning-text-large bgs-print-u-7d6c768eb355">📄 DOKUMEN</div>
                    <div class="warning-subtext bgs-print-u-c4190d1219c9">PENTING / CONFIDENTIAL</div>
                </div>
            @endif

        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => { window.print(); }, 600);
        });
    </script>
</body>
</html>