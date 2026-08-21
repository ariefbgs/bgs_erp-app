@extends('layouts.app')
@section('title', 'Detail Customer - ' . $customer->name)
@section('content')
<style>
    body {
        background-color: #f1f5f9;
    }
    .main-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        background: #fff;
    }
    .card-header-custom {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        padding: 1.25rem 1.5rem;
        border-radius: 12px 12px 0 0 !important;
    }
    .card-header-custom h4 {
        color: white;
        font-weight: 700;
        margin-bottom: 0;
    }
    .section-sub-title {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #1e293b;
        font-weight: 700;
        margin-bottom: 1rem;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 6px;
    }
    .section-sub-title i {
        color: #0284c7;
    }
    .detail-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 2px;
    }
    .detail-value {
        color: #1e293b;
        font-weight: 600;
        font-size: 0.95rem;
        margin-bottom: 15px;
    }
    .info-group-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 18px;
        height: 100%;
    }
    .badge-code {
        background-color: #e0f2fe;
        color: #0369a1;
        padding: 5px 12px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.9rem;
    }
    /* Sub-card khusus untuk membedakan kategori PIC */
    .pic-sub-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 12px 15px;
    }
    .pic-title-badge {
        font-size: 0.75rem;
        font-weight: bold;
        padding: 3px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        display: inline-block;
        margin-bottom: 10px;
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <h4><i class="bi bi-person-lines-fill me-2"></i>Customer Profile</h4>
                <span class="badge-code">{{ $customer->customer_code }}</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('customers.index') }}" class="btn btn-light btn-sm fw-bold px-3 text-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                {{-- GANTI TOMBOL CETAK LAMA DENGAN DROPDOWN INI --}}
                <div class="btn-group">
                    <button type="button" class="btn btn-info btn-sm fw-bold px-3 text-white dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-printer-fill me-1"></i> Print Label
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" style="border-radius: 8px; border: 1px solid #e2e8f0;">
                        <li>
                            <a class="dropdown-item fw-bold text-primary py-2" href="{{ route('customers.print', $customer->id) }}?type=barang" target="_blank">
                                <i class="bi bi-box-seam me-2"></i> Label Paket Barang
                            </a>
                        </li>
                        <li>
                            <div class="dropdown-divider my-1"></div>
                        </li>
                        <li>
                            <a class="dropdown-item fw-bold text-success py-2" href="{{ route('customers.print', $customer->id) }}?type=dokumen" target="_blank">
                                <i class="bi bi-file-earmark-text me-2"></i> Label Paket Dokumen
                            </a>
                        </li>
                    </ul>
                </div>
                <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-warning btn-sm fw-bold px-3 text-dark">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                </a>
            </div>
        </div>
        
        <div class="card-body p-4">
            <div class="row g-4">
                {{-- KIRI: INFORMASI UTAMA & LEGALITAS --}}
                <div class="col-md-6">
                    <div class="info-group-box">
                        <div class="section-sub-title"><i class="bi bi-building me-2"></i>Company General Info</div>
                        
                        <div class="detail-label">Customer Name</div>
                        <div class="detail-value">{{ $customer->name }}</div>
                        
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value">{{ $customer->email ?? '-' }}</div>
                        
                        <div class="detail-label">Phone / Telephone</div>
                        <div class="detail-value">{{ $customer->phone ?? '-' }}</div>
                        
                        <div class="detail-label">Tax Number (NPWP)</div>
                        <div class="detail-value">{{ $customer->tax_number ?? '-' }}</div>

                        <div class="detail-label">Registered Date</div>
                        <div class="detail-value text-muted small">{{ $customer->created_at->format('d F Y - H:i') }}</div>
                    </div>
                </div>

                {{-- KANAN: DIREKTORI ALAMAT LENGKAP --}}
                <div class="col-md-6">
                    <div class="info-group-box">
                        <div class="section-sub-title"><i class="bi bi-geo-alt-fill me-2"></i>Address Directory</div>
                        
                        <div class="detail-label">Office Address</div>
                        <div class="detail-value">{{ $customer->address ?? '-' }}</div>
                        
                        <div class="detail-label">Shipping Address (Pengiriman Barang)</div>
                        <div class="detail-value text-primary">{{ $customer->shipping_address ?? '-' }}</div>
                        
                        <div class="detail-label">Document Address (Pengiriman Invoice/Kwitansi)</div>
                        <div class="detail-value text-success">{{ $customer->document_address ?? '-' }}</div>
                    </div>
                </div>

                {{-- BAWAH: DATA PERSON IN CHARGE (PIC) SEGMENTASI --}}
                <div class="col-12">
                    <div class="info-group-box">
                        <div class="section-sub-title"><i class="bi bi-people-fill me-2"></i>Authorized Personnel (PIC) By Department</div>
                        
                        <div class="row g-3">
                            {{-- PIC QUOTATION --}}
                            <div class="col-md-4">
                                <div class="pic-sub-card border-warning">
                                    <span class="pic-title-badge bg-warning text-dark">PIC Quotation</span>
                                    <div class="detail-label">Name</div>
                                    <div class="detail-value mb-2">{{ $customer->pic_quotation_name ?? '-' }}</div>
                                    <div class="detail-label">Phone Contact</div>
                                    <div class="detail-value mb-0 text-muted">{{ $customer->pic_quotation_phone ?? '-' }}</div>
                                </div>
                            </div>

                            {{-- PIC DELIVERY ORDER --}}
                            <div class="col-md-4">
                                <div class="pic-sub-card border-primary">
                                    <span class="pic-title-badge bg-primary text-white">PIC Delivery Order (DO)</span>
                                    <div class="detail-label">Name</div>
                                    <div class="detail-value mb-2">{{ $customer->pic_do_name ?? '-' }}</div>
                                    <div class="detail-label">Phone Contact</div>
                                    <div class="detail-value mb-0 text-muted">{{ $customer->pic_do_phone ?? '-' }}</div>
                                </div>
                            </div>

                            {{-- PIC INVOICE / FINANCE --}}
                            <div class="col-md-4">
                                <div class="pic-sub-card border-success">
                                    <span class="pic-title-badge bg-success text-white">PIC Invoice / Finance</span>
                                    <div class="detail-label">Name</div>
                                    <div class="detail-value mb-2">{{ $customer->pic_invoice_name ?? '-' }}</div>
                                    <div class="detail-label">Phone Contact</div>
                                    <div class="detail-value mb-0 text-muted">{{ $customer->pic_invoice_phone ?? '-' }}</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
@endsection