@extends('layouts.app')
@section('title', 'Edit Product')
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
    
    /* Header dengan gradasi warna Amber/Orange khas Edit State */
    .card-header-custom {
        background: linear-gradient(135deg, #d97706, #b45309);
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
        color: #d97706;
    }

    /* Info Box Area (Customer & PIC) */
    .info-box-bg {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 15px;
    }

    /* Kotak Item Produk Dinamis */
    .qty-input, .price-display, .subtotal-display {
        text-align: right;
    }
    .brand-display, .subtotal-display {
        background-color: #f8fafc !important;
        font-weight: 600;
        color: #475569;
    }

    /* Kustomisasi Textarea Inner Specs & Description */
    .spec-display {
        background-color: #fff7ed;
        border-color: #ffedd5;
        font-size: 0.88rem;
    }
    .spec-display:focus {
        background-color: #fff;
    }
    .desc-display {
        background-color: #f8fafc;
        border-color: #f1f5f9;
        font-size: 0.88rem;
    }
    .desc-display:focus {
        background-color: #fff;
    }

    /* Kustomisasi Gaya Select2 Kompatibel dengan Bootstrap 5 */
    .select2-container .select2-selection--single {
        height: 40px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 0.375rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
        padding-left: 12px !important;
        color: #1e293b !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px !important;
    }

    /* Form Utilities */
    .form-control, .form-select {
        border-color: #cbd5e1;
        padding: 0.5rem 0.75rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
    }
    
    /* Bungkus khusus preview gambar */
    .product-image-preview-wrapper {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 8px;
        background: #fff;
        display: inline-block;
        max-width: 150px;
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h4><i class="bi bi-pencil-square me-2"></i>Edit Product Data</h4>
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

            <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data" id="product-form">
                @csrf
                @method('PUT')
                
                <div class="row g-3 mb-4 p-3 rounded-3 info-box-bg">
                    <div class="col-12">
                        <div class="section-sub-title mb-2" style="border:none;"><i class="bi bi-building-fill me-2"></i>Primary Identification</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Product Code</label>
                        <input type="text"
                           name="product_code"
                           id="product_code"
                           class="form-control bg-light @error('product_code') is-invalid @enderror"
                           value="{{ old('product_code', $product->product_code) }}"
                           readonly
                           required>
                        @error('product_code')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="name">
                            Product Name <span class="text-danger">*</span>
                        </label>
                        <textarea name="name"
                                id="name"
                                rows="1"
                                class="form-control auto-row-textarea @error('name') is-invalid @enderror"
                                style="resize: none; overflow-y: hidden;"
                                required>{{ old('name', $product->name) }}</textarea>
                        @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="name2">
                            Product Name Customer <span class="text-danger">*</span>
                        </label>
                        <textarea name="name2"
                                id="name2"
                                rows="1"
                                class="form-control auto-row-textarea @error('name2') is-invalid @enderror"
                                style="resize: none; overflow-y: hidden;"
                                required>{{ old('name2', $product->name2) }}</textarea>
                        @error('name2')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Specification
                        </label>
                        <textarea name="specification"
                                class="form-control auto-row-textarea @error('specification') is-invalid @enderror"
                                rows="1"
                                style="resize: none; overflow-y: hidden;">{{ old('specification', $product->specification ?? '') }}</textarea>
                        @error('specification')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Brand
                        </label>
                        <input type="text"
                            name="brand"
                            class="form-control @error('brand') is-invalid @enderror"
                            value="{{ old('brand', $product->brand) }}">
                        @error('brand')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Unit<span class="text-danger">*</span>
                        </label>
                        <select name="unit"
                            class="form-control @error('unit') is-invalid @enderror"
                            required>
                            <option value="">Select Unit</option>
                            <option value="pcs" {{ old('unit', 'pcs') == 'pcs' ? 'selected' : '' }}>Pcs</option>
                            <option value="set" {{ old('unit') == 'set' ? 'selected' : '' }}>Set</option>
                            <option value="box" {{ old('unit') == 'box' ? 'selected' : '' }}>Box</option>
                            <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>Kg</option>
                            <option value="liter" {{ old('unit') == 'liter' ? 'selected' : '' }}>Liter</option>
                            <option value="unit" {{ old('unit') == 'unit' ? 'selected' : '' }}>Unit</option>
                            <option value="lot" {{ old('unit') == 'lot' ? 'selected' : '' }}>Lot</option>

                            <!--<option value="">Select Unit</option>
                            <option value="pcs" {{ old('unit', $product->unit) == 'pcs' ? 'selected' : '' }}>Pcs</option>
                            <option value="set" {{ old('unit', $product->unit) == 'set' ? 'selected' : '' }}>Set</option>
                            <option value="box" {{ old('unit', $product->unit) == 'box' ? 'selected' : '' }}>Box</option>
                            <option value="kg" {{ old('unit', $product->unit) == 'kg' ? 'selected' : '' }}>Kg</option>
                            <option value="liter" {{ old('unit', $product->unit) == 'liter' ? 'selected' : '' }}>Liter</option>
                            <option value="unit" {{ old('unit', $product->unit) == 'unit' ? 'selected' : '' }}>Unit</option>
                            <option value="lot" {{ old('unit', $product->unit) == 'lot' ? 'selected' : '' }}>Lot</option>-->

                        </select>
                        @error('unit')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">
                            Stock
                        </label>
                        <input type="text"
                            name="stock"
                            class="form-control @error('stock') is-invalid @enderror"
                            value="{{ old('stock', $product->stock) }}">
                        @error('stock')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Selling Price (Rp) <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            name="price"
                            id="price"
                            class="form-control text-end currency-input"
                            value="{{ old('price', number_format($product->price, 0, ',', '.')) }}">
                        @error('price')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Purchase Price (Rp) <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            name="purchase_price"
                            id="purchase_price"
                            class="form-control text-end currency-input"
                            value="{{ old('purchase_price', number_format($product->purchase_price, 0, ',', '.')) }}">
                        @error('purchase_price')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- REPARASI SELESAI: Mengikuti sistem bypass route milik Invoice Customer --}}
                    <div class="col-md-12 mb-3">
                        <label for="image" class="form-label">Product Image Attachment</label>
                        <input type="file"
                            name="image"
                            id="image"
                            class="form-control @error('image') is-invalid @enderror"
                            accept="image/*">
                        <div class="form-text">Format: JPEG, PNG, JPG, GIF. Max: 2MB. Kosongkan jika tidak ingin mengubah gambar.</div>
                        @error('image')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        {{-- Tampilkan gambar yang sudah ada (jika ada) --}}
                        @if(!empty($product->image))
                            @php
                                $imagePath = $product->image ?? '';
                                $imageUrl = null;

                                if (!empty($imagePath)) {
                                    // Normalisasi path
                                    $cleanPath = ltrim($imagePath, '/');
                                    $cleanPath = str_replace('\\', '/', $cleanPath);

                                    // Pastikan path mengandung 'uploads/products/'
                                    if (!str_contains($cleanPath, 'uploads/products/')) {
                                        $cleanPath = 'uploads/products/' . basename($cleanPath);
                                    }

                                    // Cek file di public_path
                                    $fullPath = public_path($cleanPath);
                                    if (file_exists($fullPath)) {
                                        // Root web = folder proyek, tambahkan prefix 'public/'
                                        $imageUrl = asset('public/' . $cleanPath);
                                    } else {
                                        // Fallback tanpa prefix (untuk local)
                                        $imageUrl = asset($cleanPath);
                                    }

                                    // Jika masih tidak ditemukan, coba Storage (opsional)
                                    if (!$imageUrl || !file_exists($fullPath)) {
                                        try {
                                            if (Storage::disk('public')->exists($cleanPath)) {
                                                $imageUrl = Storage::url($cleanPath);
                                            }
                                        } catch (\Exception $e) {
                                            // Abaikan
                                        }
                                    }
                                }
                            @endphp

                            @if($imageUrl)
                                <div class="mt-2">
                                    <div class="product-image-preview-wrapper mb-2 d-block">
                                        <img src="{{ $imageUrl }}"
                                            alt="Product Image"
                                            style="max-width: 100%; max-height: 300px; border-radius: 4px; object-fit: contain; border: 1px solid #ddd; padding: 4px; background: #fff;">
                                    </div>
                                    <a href="{{ $imageUrl }}" target="_blank" class="btn btn-sm btn-info text-white">
                                        <i class="bi bi-eye-fill"></i> View Current Image
                                    </a>
                                </div>
                            @else
                                <div class="mt-2 text-muted">
                                    <i class="bi bi-exclamation-triangle"></i> Gambar tidak ditemukan di server.
                                </div>
                            @endif
                        @endif
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label class="form-label">
                            Remarks
                        </label>
                        <textarea name="description"
                                class="form-control auto-row-textarea"
                                rows="3">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-warning px-4 py-2 fw-bold text-dark" style="background-color: #d97706; border-color: #d97706; color: #fff !important;">
                            <i class="bi bi-save-fill me-1"></i> Update Product
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
    // 1. FUNGSI UTAMA (FLEKSIBEL TEXTAREA)
    function adjustTextareaRows(textarea) {
        if (!textarea) return;
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }

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

    // 2. INITIALIZATION DAN EVENT HANDLERS
    $(document).ready(function () {
        let name2Edited = false;

        // --- GLOBAL AUTO-ROW TEXTAREA EVENT ---
        $('.auto-row-textarea').each(function() {
            adjustTextareaRows(this);
        });

        $(document).on('input', '.auto-row-textarea', function() {
            adjustTextareaRows(this);
        });
        // --------------------------------------

        // AUTO COPY PRODUCT NAME
        $('#name').on('keyup change input', function () {
            if (!name2Edited) {
                $('#name2').val($(this).val());
                adjustTextareaRows($('#name2')[0]);
            }
        });

        $('#name2').on('keyup change input', function () {
            name2Edited = true;
        });

        // FORMAT RUPIAH REAL-TIME
        $('.currency-input').on('keyup change input', function () {
            let value = $(this).val();
            let numericValue = parseRupiahToNumber(value);
            if (numericValue > 0) {
                $(this).val(formatRupiah(numericValue));
            } else {
                $(this).val('');
            }
        });

        // SUBMIT => CONVERT TO NUMERIC MURNI UNTUK DATABASE
        $('#product-form').on('submit', function () {
            $('.currency-input').each(function () {
                let value = $(this).val();
                let numericValue = parseRupiahToNumber(value);
                $(this).val(numericValue);
            });
        });
    });
</script>
@endsection