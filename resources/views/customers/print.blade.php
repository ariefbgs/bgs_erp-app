<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Label - {{ $customer->name }}</title>
    <style>
        /* Mengatur Ukuran Kertas Fisik Global tetap A4 Potrait */
        @page {
            size: A4 portrait;
            margin: 0; /* Margin nol agar kontrol penuh ada di CSS box */
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
        }

        /* CONTAINER UTAMA HALAMAN */
        .page-container {
            padding: 15mm;
            max-width: 210mm; /* Lebar total A4 */
            margin: 0 auto;
        }
        
        /* BOX LABEL (Dibuat Pas Setengah Kertas A4 Portrait / Mendekati A5) */
        .shipping-card {
            border: 4px dashed #0f172a;
            border-radius: 12px;
            padding: 25px;
            background-color: #fff;
            box-sizing: border-box;
            
            /* Pembatasan Tinggi agar Pas Setengah Kertas A4 */
            height: 135mm; 
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Menjaga konten menyebar rapi */
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .section-label {
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
            display: block;
            letter-spacing: 0.5px;
        }

        .company-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0 0 4px 0;
        }

        .address-detail {
            font-size: 1rem;
            margin: 0 0 6px 0;
            white-space: pre-line;
        }

        .contact-info {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0;
        }

        /* Badge Kanan Atas */
        .badge-type {
            border: 3px solid #0f172a;
            padding: 4px 12px;
            font-weight: 800;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            background-color: #f8fafc;
        }

        .divider {
            border-top: 2px dashed #cbd5e1;
            margin: 15px 0;
        }

        /* NOTIFIKASI PERINGATAN BESAR (FRAGILE / BARANG) */
        .warning-container {
            border: 4px solid #dc2626;
            padding: 12px;
            text-align: center;
            border-radius: 8px;
            background-color: #fef2f2;
        }

        .warning-text-large {
            font-size: 2.6rem;
            font-weight: 900;
            color: #dc2626;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 2px;
        }

        .warning-subtext {
            font-size: 1.6rem;
            font-weight: 700;
            color: #991b1b;
            text-transform: uppercase;
            margin: 3px 0 0 0;
        }

        /* BAR NAVIGATION / TOMBOL KONTROL DI SCREEN */
        .action-bar {
            max-width: 210mm;
            margin: 0 auto 15px auto;
            padding: 10px 15mm 0 15mm;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .btn-print { background-color: #10b981; color: white; border: none; }
        .btn-close { background-color: #64748b; color: white; border: none; }

        /* ==========================================================================
           CRITICAL: CSS SPESIFIK UNTUK CETAK KERTAS / SAVE PDF
           ========================================================================== */
        @media print {
            body {
                background-color: #fff;
            }

            /* HILANGKAN TOMBOL KONTROL SAAT DI-PRINT */
            .no-print {
                display: none !important;
            }

            .page-container {
                padding: 10mm 15mm; /* Sesuaikan margin cetak internal */
            }

            .shipping-card {
                border-style: solid; /* Putus-putus diubah menjadi solid saat diprint agar tegas */
                box-shadow: none;
            }
        }
    </style>
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
                <h3 class="company-name" style="font-size: 1.4rem; margin-bottom: 8px;">{{ $customer->name }}</h3>
                
                <p class="address-detail" style="font-weight: 500; font-size: 1.1rem; margin-bottom: 12px;">
                    {{ request()->get('type') == 'dokumen' ? ($customer->document_address ?? $customer->address) : ($customer->shipping_address ?? $customer->address) }}
                </p>

                <p class="contact-info" style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 6px;">
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
                <div class="warning-container" style="border-color: #0284c7; background-color: #f0f9ff;">
                    <div class="warning-text-large" style="color: #0284c7;">📄 DOKUMEN</div>
                    <div class="warning-subtext" style="color: #0369a1;">PENTING / CONFIDENTIAL</div>
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