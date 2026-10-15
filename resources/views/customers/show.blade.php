@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-customers-show bgs-mode-screen')
@section('title', 'Detail Customer - ' . $customer->name)
@section('content')


<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
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
                    <ul class="dropdown-menu dropdown-menu-end shadow bgs-u-0b1ff9ceff4f">
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