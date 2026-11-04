@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-customers-create bgs-mode-create')
@section('title', 'Add Customer')
@section('content')


<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
            <h4><i class="bi bi-person-plus-fill me-2"></i>Add New Customer</h4>
            <a href="{{ route('customers.index') }}" class="btn btn-light btn-sm fw-bold px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
        
        <div class="card-body p-4">
            {{-- ALERT VALIDASI ERROR --}}
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm d-flex fade show mb-4 bgs-u-e6478a190de4">
                    <div class="me-2"><i class="bi bi-exclamation-triangle-fill fs-5"></i></div>
                    <div>
                        <strong class="d-block mb-1">Please fix the following validation errors:</strong>
                        <ul class="mb-0 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="btn-close ms-auto bgs-u-aae30f4ce161" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4 bgs-u-be237583235b">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4 bgs-u-f812d16479e9">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                </div>
            @endif  

            <form action="{{ route('customers.store') }}" method="POST" id="customer-form">
                @csrf
                
                <div class="row g-4">
                    {{-- KIRI: DATA DASAR CUSTOMER & LEGALITAS --}}
                    <div class="col-md-6">
                        <div class="info-group-box">
                            <div class="section-sub-title"><i class="bi bi-building me-2"></i>Company General Info</div>
                            
                            {{-- CUSTOMER CODE --}}
                            <div class="mb-3">
                                <label for="customer_code" class="form-label">Customer Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('customer_code') is-invalid @enderror" 
                                       id="customer_code" name="customer_code" value="{{ old('customer_code', $autoCode ?? '') }}" required>
                                @error('customer_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- CUSTOMER NAME --}}
                            <div class="mb-3">
                                <label for="name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- EMAIL ADDRESS (Ubah type dari email ke text agar bisa menerima "-") --}}
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="text" class="form-control @error('email') is-invalid @enderror" 
                                    id="email" name="email" value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- PHONE --}}
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone / Telephone</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- TAX NUMBER (NPWP) --}}
                            <div class="mb-0">
                                <label for="tax_number" class="form-label">Tax Number (NPWP)</label>
                                <input type="text" class="form-control @error('tax_number') is-invalid @enderror" 
                                       id="tax_number" name="tax_number" value="{{ old('tax_number') }}">
                                @error('tax_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- KANAN: DIREKTORI ALAMAT LENGKAP --}}
                    <div class="col-md-6">
                        <div class="info-group-box">
                            <div class="section-sub-title"><i class="bi bi-geo-alt-fill me-2"></i>Address Directory</div>
                            
                            {{-- OFFICE ADDRESS --}}
                            <div class="mb-3">
                                <label for="address" class="form-label">Office Address</label>
                                <textarea class="form-control auto-row-textarea @error('address') is-invalid @enderror bgs-u-023bfb47d76b"
                                          id="address" name="address" rows="1">{{ old('address') }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- SHIPPING ADDRESS --}}
                            <div class="mb-3">
                                <label for="shipping_address" class="form-label text-primary">Shipping Address (Pengiriman Barang)</label>
                                <textarea class="form-control auto-row-textarea @error('shipping_address') is-invalid @enderror bgs-u-023bfb47d76b"
                                          id="shipping_address" name="shipping_address" rows="1">{{ old('shipping_address') }}</textarea>
                                @error('shipping_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- DOCUMENT SHIPPING ADDRESS --}}
                            <div class="mb-0">
                                <label for="document_address" class="form-label text-success">Document Shipping Address (Invoice/Kwitansi)</label>
                                <textarea class="form-control auto-row-textarea @error('document_address') is-invalid @enderror bgs-u-023bfb47d76b"
                                          id="document_address" name="document_address" rows="1">{{ old('document_address') }}</textarea>
                                @error('document_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- BAWAH: DATA PERSON IN CHARGE (PIC) SEGMENTASI --}}
                    <div class="col-12">
                        <div class="info-group-box">
                            <div class="section-sub-title"><i class="bi bi-people-fill me-2"></i>Authorized Personnel (PIC) By Department</div>
                            
                            <div class="row g-3">
                                {{-- PIC QUOTATION --}}
                                <div class="col-md-4">
                                    <div class="pic-sub-card border-start border-warning border-3">
                                        <span class="pic-title-badge bg-warning text-dark">PIC Quotation</span>
                                        <div class="mb-3">
                                            <label for="pic_quotation_name" class="form-label">PIC Name</label>
                                            <input type="text" class="form-control @error('pic_quotation_name') is-invalid @enderror" 
                                                   id="pic_quotation_name" name="pic_quotation_name" value="{{ old('pic_quotation_name') }}">
                                            @error('pic_quotation_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-0">
                                            <label for="pic_quotation_phone" class="form-label">Phone Contact</label>
                                            <input type="text" class="form-control @error('pic_quotation_phone') is-invalid @enderror" 
                                                   id="pic_quotation_phone" name="pic_quotation_phone" value="{{ old('pic_quotation_phone') }}">
                                            @error('pic_quotation_phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                {{-- PIC DELIVERY ORDER --}}
                                <div class="col-md-4">
                                    <div class="pic-sub-card border-start border-primary border-3">
                                        <span class="pic-title-badge bg-primary text-white">PIC Delivery Order (DO)</span>
                                        <div class="mb-3">
                                            <label for="pic_do_name" class="form-label">PIC Name</label>
                                            <input type="text" class="form-control @error('pic_do_name') is-invalid @enderror" 
                                                   id="pic_do_name" name="pic_do_name" value="{{ old('pic_do_name') }}">
                                            @error('pic_do_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-0">
                                            <label for="pic_do_phone" class="form-label">Phone Contact</label>
                                            <input type="text" class="form-control @error('pic_do_phone') is-invalid @enderror" 
                                                   id="pic_do_phone" name="pic_do_phone" value="{{ old('pic_do_phone') }}">
                                            @error('pic_do_phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                {{-- PIC INVOICE / FINANCE --}}
                                <div class="col-md-4">
                                    <div class="pic-sub-card border-start border-success border-3">
                                        <span class="pic-title-badge bg-success text-white">PIC Invoice / Finance</span>
                                        <div class="mb-3">
                                            <label for="pic_invoice_name" class="form-label">PIC Name</label>
                                            <input type="text" class="form-control @error('pic_invoice_name') is-invalid @enderror" 
                                                   id="pic_invoice_name" name="pic_invoice_name" value="{{ old('pic_invoice_name') }}">
                                            @error('pic_invoice_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-0">
                                            <label for="pic_invoice_phone" class="form-label">Phone Contact</label>
                                            <input type="text" class="form-control @error('pic_invoice_phone') is-invalid @enderror" 
                                                   id="pic_invoice_phone" name="pic_invoice_phone" value="{{ old('pic_invoice_phone') }}">
                                            @error('pic_invoice_phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ACTION BUTTONS --}}
                    <div class="col-12 mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
                            <i class="bi bi-save-fill me-1"></i> Save Customer
                        </button>
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // FUNGSI UTAMA (FLEKSIBEL TEXTAREA)
    function adjustTextareaRows(textarea) {
        if (!textarea) return;
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }

    $(document).ready(function () {
        // --- GLOBAL AUTO-ROW TEXTAREA EVENT ---
        $('.auto-row-textarea').each(function() {
            adjustTextareaRows(this);
        });

        $(document).on('input', '.auto-row-textarea', function() {
            adjustTextareaRows(this);
        });
    });
</script>
@endsection