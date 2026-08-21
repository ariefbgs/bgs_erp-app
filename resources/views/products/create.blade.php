@extends('layouts.app')
@section('title', 'Add Product')
@section('content')
{{-- CDN SELECT2 CSS --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Mengubah Background dasar halaman agar senada */
    body {
        background-color: #f1f5f9;
    }

    /* Desain Card Utama */
    .main-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        background: #fff;
        overflow: hidden;
    }
    
    /* Header dengan gradasi warna Hijau khas Create State */
    .card-header-custom {
        background: linear-gradient(135deg, #10b981, #047857);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    .card-header-custom h4 {
        color: white;
        font-weight: 700;
        margin-bottom: 0;
    }

    /* Styling Label & Bagian Sub-title */
    .form-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #475569;
        font-weight: 700;
        margin-bottom: 6px;
    }
    .section-sub-title {
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #1e293b;
        font-weight: 700;
        margin-bottom: 1.25rem;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 6px;
    }
    .section-sub-title i {
        color: #10b981;
    }

    /* Info Box Area */
    .info-box-bg {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 15px;
    }

    .price-input {
        text-align: right;
    }

    /* Form Utilities */
    .form-control, .form-select {
        border-color: #cbd5e1;
        padding: 0.5rem 0.75rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h4><i class="bi bi-plus-circle me-2"></i>Add New Product</h4>
            <a href="{{ route('products.index') }}" class="btn btn-light btn-sm fw-bold px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
        
        <div class="card-body p-4">
            {{-- ALERT VALIDASI ERROR --}} 
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm d-flex fade show mb-4" style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important; border-radius: 6px;">
                    <div class="me-2"><i class="bi bi-exclamation-triangle-fill fs-5"></i></div>
                    <div>
                        <strong class="d-block mb-1">Please fix the following validation errors:</strong>
                        <ul class="mb-0 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size: 0.8rem;"></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4" style="background-color: #f0fdf4; color: #15803d; border-left: 4px solid #16a34a !important;">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4" style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                </div>
            @endif  

            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="product-form">
                @csrf
                
                <div class="row g-3 mb-4 p-3 rounded-3 info-box-bg">
                    <div class="col-12">
                        <div class="section-sub-title mb-2" style="border:none;"><i class="bi bi-building-fill me-2"></i>Primary Identification</div>
                    </div>
                    
                    {{-- PRODUCT CODE --}}
                    <div class="col-md-6">
                        <label class="form-label">Product Code <span class="text-danger">*</span></label>
                        <input type="text"
                           name="product_code"
                           id="product_code"
                           class="form-control bg-light @error('product_code') is-invalid @enderror"
                           value="{{ old('product_code', $autoCode) }}"
                           readonly
                           required>
                        @error('product_code')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- PRODUCT CODE CUSTOMER --}}
                    <div class="col-md-6">
                        <label class="form-label">Product Code Customer <span class="text-danger">*</span></label>
                        <input type="text"
                           name="product_code2"
                           id="product_code2"
                           class="form-control bg-light @error('product_code2') is-invalid @enderror"
                           value="{{ old('product_code2', $autoCode) }}"
                           readonly
                           required>
                        <small class="text-muted small">
                            Automatically generated by system based on sequence.
                        </small>
                        @error('product_code2')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- PRODUCT NAME --}}
                    <div class="col-md-6">
                        <label class="form-label" for="name">
                            Product Name <span class="text-danger">*</span>
                        </label>
                        <textarea name="name"
                                id="name"
                                rows="1"
                                class="form-control auto-row-textarea @error('name') is-invalid @enderror"
                                style="resize: none; overflow-y: hidden;"
                                required>{{ old('name') }}</textarea>
                        @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- PRODUCT NAME CUSTOMER --}}
                    <div class="col-md-6">
                        <label class="form-label" for="name2">
                            Product Name Customer <span class="text-danger">*</span>
                        </label>
                        <textarea name="name2"
                                id="name2"
                                rows="1"
                                class="form-control auto-row-textarea @error('name2') is-invalid @enderror"
                                style="resize: none; overflow-y: hidden;"
                                required>{{ old('name2') }}</textarea>     
                        <small class="text-muted small d-block mt-1">
                            Default follows Product Name, but can be changed manually.
                        </small>
                        @error('name2')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- SPECIFICATION --}}
                    <div class="col-md-6">
                        <label class="form-label">Specification</label>
                        <textarea name="specification"
                                class="form-control auto-row-textarea @error('specification') is-invalid @enderror"
                                rows="1"
                                style="resize: none; overflow-y: hidden;">{{ old('specification') }}</textarea>
                        @error('specification')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- BRAND --}}
                    <div class="col-md-6">
                        <label class="form-label">Brand</label>
                        <input type="text"
                            name="brand"
                            class="form-control @error('brand') is-invalid @enderror"
                            value="{{ old('brand') }}">
                        @error('brand')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- UNIT --}}
                    <div class="col-md-4">
                        <label class="form-label">Unit <span class="text-danger">*</span></label>
                        <select name="unit"
                            class="form-select @error('unit') is-invalid @enderror"
                            required>
                            <option value="">Select Unit</option>
                            <option value="pcs" {{ old('unit', 'pcs') == 'pcs' ? 'selected' : '' }}>Pcs</option>
                            <option value="set" {{ old('unit') == 'set' ? 'selected' : '' }}>Set</option>
                            <option value="box" {{ old('unit') == 'box' ? 'selected' : '' }}>Box</option>
                            <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>Kg</option>
                            <option value="liter" {{ old('unit') == 'liter' ? 'selected' : '' }}>Liter</option>
                            <option value="unit" {{ old('unit') == 'unit' ? 'selected' : '' }}>Unit</option>
                            <option value="lot" {{ old('unit') == 'lot' ? 'selected' : '' }}>Lot</option>
                        </select>
                        @error('unit')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- STOCK --}}
                    <div class="col-md-4">
                        <label class="form-label">Stock <span class="text-danger">*</span></label>
                        <input type="number"
                            name="stock"
                            class="form-control @error('stock') is-invalid @enderror"
                            value="{{ old('stock', 0) }}"
                            required>
                        @error('stock')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- PRODUCT IMAGE --}}
                    <div class="col-md-4">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file"
                            name="image"
                            id="image"
                            class="form-control @error('image') is-invalid @enderror"
                            accept="image/*"
                            onchange="previewImage(event)">
                        <small class="text-muted small">
                            Format: JPEG, PNG, JPG, GIF. Max: 2MB
                        </small>
                        @error('image')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        {{-- Preview gambar yang akan diupload --}}
                        <div id="image-preview-container" class="mt-2" style="display: none;">
                            <div class="product-image-preview-wrapper mb-2 d-block">
                                <img id="image-preview"
                                    src="#"
                                    alt="Preview Gambar"
                                    style="max-width: 100%; max-height: 200px; border-radius: 4px; object-fit: contain; border: 1px solid #ddd; padding: 4px; background: #fff;">
                            </div>
                        </div>
                    </div>

                    {{-- SELLING PRICE --}}
                    <div class="col-md-6">
                        <label class="form-label">Selling Price (Rp) <span class="text-danger">*</span></label>
                        <input type="text"
                            name="price"
                            id="price"
                            class="form-control price-input rupiah-format @error('price') is-invalid @enderror"
                            value="{{ old('price') }}"
                            required>
                        @error('price')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    
                    {{-- PURCHASE PRICE --}}
                    <div class="col-md-6">
                        <label class="form-label">Purchase Price (Rp) </label>
                        <input type="text"
                            name="purchase_price"
                            id="purchase_price"
                            class="form-control price-input rupiah-format @error('purchase_price') is-invalid @enderror"
                            value="{{ old('purchase_price') }}">
                        @error('purchase_price')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- DESCRIPTION / REMARKS --}}
                    <div class="col-12 mb-2">
                        <label class="form-label">Remarks</label>
                        <textarea name="description"
                                class="form-control auto-row-textarea"
                                rows="3">{{ old('description') }}</textarea>
                    </div>

                    {{-- ACTION BUTTONS --}}
                    <div class="mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-success px-4 py-2 fw-bold text-white" style="background-gradient: linear-gradient(135deg, #10b981, #047857);">
                            <i class="bi bi-save-fill me-1"></i> Save Product
                        </button>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // =========================================================
    // 1. FUNGSI PREVIEW GAMBAR (UNTUK CREATE & EDIT)
    // =========================================================
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('image-preview');
            if (output) {
                output.src = reader.result;
                var container = document.getElementById('image-preview-container');
                if (container) {
                    container.style.display = 'block';
                }
            }
        };
        if (event.target.files && event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        }
    }

    // =========================================================
    // 2. FUNGSI FLEKSIBEL TEXTAREA
    // =========================================================
    function adjustTextareaRows(textarea) {
        if (!textarea) return;
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }

    // =========================================================
    // 3. FUNGSI FORMAT DATA
    // =========================================================
    function formatRupiah(angka) {
        if (!angka && angka !== 0) return '';
        let number = angka.toString().replace(/[^,\d]/g, '');
        let split = number.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return rupiah;
    }

    function parseRupiahToNumber(rupiahString) {
        if (!rupiahString) return 0;
        return parseInt(rupiahString.toString().replace(/\./g, '').replace(/[^0-9]/g, '')) || 0;
    }

    // =========================================================
    // 4. INITIALIZATION DAN EVENT HANDLERS
    // =========================================================
    $(document).ready(function () {
        let name2Edited = false;

        // --- AUTO-ROW TEXTAREA ---
        $('.auto-row-textarea').each(function() {
            adjustTextareaRows(this);
        });

        $(document).on('input', '.auto-row-textarea', function() {
            adjustTextareaRows(this);
        });

        // --- AUTO COPY PRODUCT NAME ---
        $('#name').on('keyup change input', function () {
            if (!name2Edited) {
                $('#name2').val($(this).val());
                adjustTextareaRows($('#name2')[0]);
            }
        });

        // --- DETECT MANUAL EDIT NAME2 ---
        $('#name2').on('keyup change input', function () {
            name2Edited = true;
        });

        // --- FORMAT RUPIAH REAL-TIME ---
        $('.rupiah-format').on('keyup change input', function () {
            let value = $(this).val();
            let numericValue = parseRupiahToNumber(value);
            if (numericValue > 0) {
                $(this).val(formatRupiah(numericValue));
            } else {
                $(this).val('');
            }
        });

        // --- SEBELUM SUBMIT FORM => Hapus Titik (Ubah ke Angka Murni) ---
        $('#product-form').on('submit', function () {
            $('.rupiah-format').each(function () {
                let value = $(this).val();
                let numericValue = parseRupiahToNumber(value);
                $(this).val(numericValue);
            });
        });

        // =========================================================
        // 5. FITUR PREVIEW GAMBAR OTOMATIS (JIKA ADA FILE INPUT)
        // =========================================================
        $('#image').on('change', function() {
            previewImage(event);
        });
    });
</script>
@endsection