@extends('layouts.app')
@section('title', 'Edit Quotation')
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
    .pic-info-alert {
        background-color: #fef3c7;
        padding: 12px;
        border-left: 4px solid #f59e0b;
        border-radius: 0 8px 8px 0;
        font-size: 0.9rem;
        color: #92400e;
    }

    /* Kotak Item Produk Dinamis */
    .product-box {
        border: 1px solid #e2e8f0;
        padding: 20px;
        border-radius: 10px;
        background-color: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        transition: border-color 0.2s;
    }
    .product-box:hover {
        border-color: #cbd5e1;
    }
    .qty-input, .price-display, .subtotal-display {
        text-align: right;
    }
    .brand-display, .subtotal-display {
        background-color: #f8fafc !important;
        font-weight: 600;
        color: #475569;
    }
    .readonly-bg {
        background-color: #f1f5f9 !important;
        font-weight: 600;
        color: #334155;
    }

    /* Kustomisasi Textarea Inner Specs & Description - Dibuat Auto Resize */
    .spec-display, .desc-display {
        font-size: 0.88rem;
        resize: none; /* Mematikan manual resize grip bawaan browser agar tetap rapi */
        overflow-y: hidden; /* Menyembunyikan scrollbar vertical */
        min-height: 120px; /* Batas tinggi minimal agar tidak terlalu tipis saat kosong */
    }
    .spec-display {
        background-color: #fff7ed;
        border-color: #ffedd5;
    }
    .spec-display:focus {
        background-color: #fff;
    }
    .desc-display {
        background-color: #f8fafc;
        border-color: #f1f5f9;
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
    
    /* Memperbaiki tampilan teks panjang / tags hasil custom typing pada Select2 */
    .select2-container .select2-selection--single .select2-selection__rendered {
        white-space: normal !important;
        word-break: break-all !important;
        line-height: 1.4 !important;
        padding-top: 8px !important;
        padding-bottom: 8px !important;
    }
    .select2-container .select2-selection--single {
        height: auto !important;
        min-height: 40px !important;
    }

    /* Summary Card / Kotak Hitung Finansial (Sisi Kanan Gelap) */
    .summary-table-container {
        background-color: #1e293b;
        border-radius: 10px;
        padding: 1.25rem;
        color: #cbd5e1;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .summary-table-custom {
        width: 100%;
        margin-bottom: 0;
    }
    .summary-table-custom th, .summary-table-custom td {
        padding: 10px 6px;
        border-bottom: 1px solid #334155;
        font-size: 0.92rem;
        color: #cbd5e1;
        vertical-align: middle;
    }
    .summary-table-custom th {
        font-weight: 500;
    }
    .summary-table-custom tr.grand-total-row th, 
    .summary-table-custom tr.grand-total-row td {
        border-top: 2px solid #334155;
        border-bottom: none;
        font-size: 1.2rem;
        font-weight: 700;
        color: #fff;
        padding-top: 15px;
    }
    .summary-table-custom tr.grand-total-row td {
        color: #38bdf8; /* Highlight Grand Total Blue */
    }
    .summary-table-custom input.form-control-summary {
        background: #334155;
        border: 1px solid #475569;
        color: #fff;
        text-align: right;
        font-weight: 600;
        padding: 6px 10px;
        border-radius: 6px;
        width: 100%;
    }
    .summary-table-custom input.form-control-summary:focus {
        border-color: #f59e0b;
        outline: none;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.3);
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
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h4><i class="bi bi-pencil-square me-2"></i>Edit Quotation Data</h4>
            <a href="{{ route('quotations.index') }}" class="btn btn-light btn-sm fw-bold px-3">
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

            <form action="{{ route('quotations.update', $quotation->id) }}" method="POST" enctype="multipart/form-data" id="quotation-form">
                @csrf
                @method('PUT')
                
                <div class="row g-3 mb-4 p-3 rounded-3 info-box-bg">
                    <div class="col-12">
                        <div class="section-sub-title mb-2" style="border:none;"><i class="bi bi-building-fill me-2"></i>Primary Identification</div>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Quotation #</label>
                        <input type="text" class="form-control readonly-bg" name="quotation_number" value="{{ $quotation->quotation_number }}">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Customer Name</label>
                        <input type="text" class="form-control readonly-bg" name="customer_name" value="{{ $quotation->customer->name }}" readonly>
                        <input type="hidden" name="customer_id" id="customer_id" value="{{ $quotation->customer_id }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status Document</label>
<div class="form-control bg-light">
    {{ ucfirst($quotation->status) }}
</div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Quotation Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-calendar3"></i></span>
                            <input type="date" name="date" id="quotation_date" class="form-control border-start-0" value="{{ $quotation->date instanceof \Carbon\Carbon ? $quotation->date->format('Y-m-d') : date('Y-m-d', strtotime($quotation->date)) }}" required>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Valid Until</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-calendar-check"></i></span>
                            <input type="date" name="valid_until" id="valid_until" class="form-control border-start-0" value="{{ $quotation->valid_until ? ($quotation->valid_until instanceof \Carbon\Carbon ? $quotation->valid_until->format('Y-m-d') : date('Y-m-d', strtotime($quotation->valid_until))) : '' }}">
                        </div>
                        <small class="text-muted" style="font-size: 0.78rem;">Leave blank to follow +7 days rule</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Attention Person (PIC)</label>
                        <div class="pic-info-alert shadow-sm py-2">
                            <i class="bi bi-person-badge-fill me-1"></i> <strong>{{ $quotation->customer->pic_quotation_name ?? '-' }}</strong> 
                            <span class="d-block text-muted small mt-1"><i class="bi bi-telephone-fill me-1"></i>{{ $quotation->customer->pic_quotation_phone ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="show_image_on_print" class="form-check-input" id="show_image" value="1" {{ $quotation->show_image_on_print ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark small" for="show_image" style="cursor: pointer;">
                                Display Product Images on Quotation PDF Printout
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 section-sub-title">
                        <div><i class="bi bi-box-seam-fill me-2"></i>Line Items / Product Details</div>
                        <button type="button" class="btn btn-success btn-sm px-3 fw-bold mb-1" id="add-product">
                            <i class="bi bi-plus-circle-fill me-1"></i> Add Product
                        </button>
                    </div>
                    
                    <div id="products-container">
                        @foreach($quotation->details as $index => $detail)
                        <div class="product-row product-box mb-3" id="row-{{ $index }}" data-row-id="{{ $index }}">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label text-dark">Select Product <span class="text-danger">*</span></label>
                                    <select name="items[{{ $index }}][product_id]" class="form-control product-select select2-enable" data-row="{{ $index }}" required>
                                        <option value="">Select Product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" 
                                                    data-price="{{ $product->price }}" 
                                                    data-name="{{ $product->name }}" 
                                                    data-brand="{{ $product->brand }}"
                                                    data-specification="{{ $product->specification }}"
                                                    data-description="{{ $product->description }}"
                                                    {{ $detail->product_id == $product->id ? 'selected' : '' }}>
                                                {{ $product->product_code }} - {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control brand-display" id="brand-{{ $index }}" value="{{ $detail->product->brand ?? '-' }}" readonly placeholder="System Auto">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-dark">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="items[{{ $index }}][quantity]" class="form-control qty-input fw-bold" data-row="{{ $index }}" value="{{ $detail->quantity }}" min="1" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-dark">Unit Price (Rp) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control price-display fw-semibold text-dark" data-row="{{ $index }}" value="{{ number_format($detail->unit_price,0,',','.') }}" required>
                                    <input type="hidden" name="items[{{ $index }}][unit_price]" class="price-hidden" value="{{ $detail->unit_price }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Subtotal (Rp)</label>
                                    <input type="text" class="form-control subtotal-display" id="subtotal-{{ $index }}" value="{{ number_format($detail->subtotal,0,',','.') }}" readonly placeholder="0">
                                    <input type="hidden" name="items[{{ $index }}][subtotal]" id="subtotal-hidden-{{ $index }}" class="subtotal-hidden" value="{{ $detail->subtotal }}">
                                </div>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small mb-1" style="color: #d97706;"><i class="bi bi-gear-fill me-1"></i>Specification (Editable)</label>
                                    <textarea name="items[{{ $index }}][specification]" class="form-control spec-display auto-resize-textarea" id="specification-{{ $index }}" placeholder="Product specification snapshot...">{{ $detail->specification }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1" style="color: #64748b;"><i class="bi bi-info-circle-fill me-1"></i>Description / Item Remarks</label>
                                    <textarea name="items[{{ $index }}][description]" class="form-control desc-display auto-resize-textarea" id="description-{{ $index }}" placeholder="Add specific remarks for this line item if needed...">{{ $detail->description }}</textarea>
                                    <div class="text-end mt-3">
                                        <button type="button" class="btn btn-sm btn-outline-danger px-3 fw-bold remove-product" data-row="{{ $index }}">
                                            <i class="bi bi-trash3-fill me-1"></i> Remove Item
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-white h-100">
                            <div class="section-sub-title mb-3"><i class="bi bi-credit-card-fill me-2"></i>Commercial Terms & Conditions</div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
                                    <select name="payment_terms" class="form-select select2-tags" required>
                                        <option value="">Select Payment Terms</option>
                                        @php
                                            $defaultPayments = [
                                                'Cash Before Delivery (CBD)',
                                                'Cash on Delivery (COD)',
                                                '100% in advance',
                                                '30% DP after Received PO & 70% Final payment Before Delivery',
                                                '40% DP after Received PO & 60% Final payment Before Delivery',
                                                '50% DP after Received PO & 50% Final payment Before Delivery',
                                                '60% DP after Received PO & 40% Final payment Before Delivery',
                                                '70% DP after Received PO & 30% Final payment Before Delivery',
                                                '14 Days after Delivery Date',
                                                '30 Days after Delivery Date',
                                                '45 Days after Delivery Date',
                                                '60 Days after Delivery Date'
                                            ];
                                            $dbPayments = $existingPaymentTerms ?? [];
                                            if (!empty($quotation->payment_terms)) {
                                                $dbPayments[] = $quotation->payment_terms;
                                            }
                                            $mergedPayments = array_unique(array_filter(array_merge($defaultPayments, $dbPayments)));
                                        @endphp
                                        @foreach($mergedPayments as $payment)
                                            <option value="{{ $payment }}" {{ $quotation->payment_terms == $payment ? 'selected' : '' }}>
                                                {{ $payment }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Delivery Time</label>
                                    <select name="delivery_time" class="form-select select2-tags">
                                        <option value="">Select Delivery Time</option>
                                        @php
                                            $defaultDelivery = [
                                                'Mentioned Above',
                                                'Around 1-1.5 Months after received your payment & P/O',
                                                '1-2 Working days',
                                                '3-5 Working days',
                                                '1 Week',
                                                '2 Weeks',
                                                '1 Month',
                                                '2 Months',
                                                '3 Months',
                                                '6 Months'
                                            ];
                                            $dbDelivery = $existingDeliveryTimes ?? [];
                                            if (!empty($quotation->delivery_time)) {
                                                $dbDelivery[] = $quotation->delivery_time;
                                            }
                                            $mergedDelivery = array_unique(array_filter(array_merge($defaultDelivery, $dbDelivery)));
                                        @endphp
                                        @foreach($mergedDelivery as $option)
                                            <option value="{{ $option }}" {{ $quotation->delivery_time == $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="form-label">General Remarks / Quotation Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Enter notes to be displayed at the bottom of the PDF...">{{ $quotation->notes }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    {{-- IMPLEMENTASI BARU: DUAL INTERACTIVE SUMMARY CARD (STREAMLINED 1-LINE VARIANT) --}}
                    <div class="col-md-6">
                        <div class="summary-table-container">
                            <table class="summary-table-custom">
                                {{-- 1. Subtotal Gross --}}
                                <tr>
                                    <th width="45%">Subtotal Item Gross</th>
                                    <td width="55%">
                                        <input type="text" id="subtotal_display" class="form-control-summary" style="background:transparent; border:none; padding-right:0;" readonly value="Rp {{ number_format($quotation->subtotal, 0, ',', '.') }}">
                                        <input type="hidden" name="subtotal" id="subtotal-value" value="{{ $quotation->subtotal }}">
                                    </td>
                                </tr>

                                {{-- 2. Discount Rate & Amount (1 Baris Rapi) --}}
                                <tr>
                                    <th>Discount</th>
                                    <td>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div style="width: 80px; position: relative;">
                                                <input type="number" name="discount_percent" id="discount_percent" class="form-control form-control-summary text-warning text-end pe-3" step="0.01" min="0" max="100" value="{{ $quotation->discount_percent ?? 0 }}" style="padding-left: 5px;">
                                                <span style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: #ffc107; pointer-events: none;">%</span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="text" id="discount_amount_display" class="form-control-summary text-warning" placeholder="Rp 0" value="{{ number_format($quotation->discount_amount ?? 0, 0, ',', '.') }}">
                                                <input type="hidden" name="discount_amount" id="discount-value" value="{{ $quotation->discount_amount ?? 0 }}">
                                                <input type="hidden" name="discount_trigger" id="discount_trigger" value="amount">
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- 3. DPP (Subtotal After Discount) --}}
                                <tr>
                                    <th>Subtotal After Discount (DPP)</th>
                                    <td>
                                        <input type="text" id="after_discount_display" class="form-control-summary" style="background:transparent; border:none; padding-right:0;" readonly value="Rp {{ number_format($quotation->dpp ?? ($quotation->subtotal - $quotation->discount_amount), 0, ',', '.') }}">
                                        <input type="hidden" name="dpp" id="dpp-value" value="{{ $quotation->dpp ?? ($quotation->subtotal - $quotation->discount_amount) }}">
                                    </td>
                                </tr>

                                {{-- 4. Tax / PPN Rate & Amount (1 Baris Rapi) --}}
                                <tr>
                                    <th>VAT PPN</th>
                                    <td>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div style="width: 80px; position: relative;">
                                                <input type="number" name="tax_percent" id="tax_percent" class="form-control form-control-summary text-info text-end pe-3" step="0.01" min="0" max="100" value="{{ $quotation->tax_percent ?? 11 }}" style="padding-left: 5px;">
                                                <span style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: #0dcaf0; pointer-events: none;">%</span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="text" id="tax_amount_display" class="form-control-summary text-info" placeholder="Rp 0" value="{{ number_format($quotation->tax_amount ?? 0, 0, ',', '.') }}">
                                                <input type="hidden" name="tax_amount" id="tax-value" value="{{ $quotation->tax_amount ?? 0 }}">
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- 5. PPh 23 Rate & Amount (1 Baris Rapi) --}}
                                <tr>
                                    <th>PPh Income Tax 23</th>
                                    <td>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div style="width: 80px; position: relative;">
                                                <input type="number" name="pph23_percent" id="pph23_percent" class="form-control form-control-summary text-danger text-end pe-3" step="0.01" min="0" max="100" value="{{ $quotation->pph23_percent ?? 0 }}" style="padding-left: 5px;">
                                                <span style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: #dc3545; pointer-events: none;">%</span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="text" id="pph23_amount_display" class="form-control-summary text-danger" placeholder="Rp 0" value="{{ number_format($quotation->pph23_amount ?? 0, 0, ',', '.') }}">
                                                <input type="hidden" name="pph23_amount" id="pph23-value" value="{{ $quotation->pph23_amount ?? 0 }}">
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- 6. Grand Total --}}
                                <tr class="grand-total-row">
                                    <th>Grand Total Due</th>
                                    <td>
                                        <input type="text" id="grand_total_display" class="form-control-summary" style="background:transparent; border:none; padding-right:0; color:#38bdf8; font-size:1.2rem; font-weight:700;" readonly value="Rp {{ number_format($quotation->total, 0, ',', '.') }}">
                                        <input type="hidden" name="total" id="grand-total-value" value="{{ $quotation->total }}">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 pt-3 border-top d-flex gap-2">
                    <button type="submit" class="btn btn-warning px-4 py-2 fw-bold text-dark" style="background-color: #d97706; border-color: #d97706; color: #fff !important;">
                        <i class="bi bi-save-fill me-1"></i> Update Quotation
                    </button>
                    <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
{{-- CDN SELECT2 JS --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let rowCount = {{ count($quotation->details) }};
let lastDiscountTrigger = 'percent'; // Default
let isInitialLoad = true; // Flag awal

function formatRupiah(angka) {
    if(!angka || isNaN(angka)) return '0';
    return new Intl.NumberFormat('id-ID').format(Math.round(angka));
}

function parseRupiahToNumber(rupiahString) {
    if(!rupiahString) return 0;
    return parseInt(rupiahString.toString().replace(/[^0-9]/g, '')) || 0;
}

function initSelect2() {
    $('.select2-enable').select2({
        placeholder: function() { return $(this).data('placeholder') || "Select Option"; },
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 0 // 0 berarti fitur search box akan selalu dipaksa muncul
    });
}

// Fungsi utama penyesuai tinggi otomatis textarea
function autoResizeTextarea() {
    $('.auto-resize-textarea').each(function() {
        this.style.height = 'auto'; 
        this.style.height = this.scrollHeight + 'px';
    });
}

function updateProductPrice(rowId) {
    let priceDisplay = $(`#row-${rowId} .price-display`).val();
    let numericPrice = parseRupiahToNumber(priceDisplay);
    $(`#row-${rowId} .price-hidden`).val(numericPrice);
    return numericPrice;
}

function calculateSubtotal(rowId) {
    let qty = parseInt($(`#row-${rowId} .qty-input`).val()) || 0;
    let price = updateProductPrice(rowId);
    let subtotal = qty * price;
    
    $(`#row-${rowId} .subtotal-display`).val(formatRupiah(subtotal));
    $(`#row-${rowId} .subtotal-display`).attr('data-value', subtotal);
    $(`#row-${rowId} #subtotal-hidden-${rowId}`).val(subtotal);
    
    calculateGrandTotal();
}

function calculateGrandTotal() {
    if (isInitialLoad) return;

    let subtotal = 0;
    $('.subtotal-display').each(function() {
        let val = $(this).attr('data-value') || 0;
        subtotal += parseInt(val);
    });
    
    let discountPercent = parseFloat($('#discount_percent').val()) || 0;
    let discountAmount = parseRupiahToNumber($('#discount_amount_display').val());
    
    if (subtotal > 0) {
        if (lastDiscountTrigger === 'percent') {
            discountAmount = subtotal * (discountPercent / 100);
            $('#discount_amount_display').val(discountAmount > 0 ? formatRupiah(discountAmount) : '');
        } else {
            discountPercent = (discountAmount / subtotal) * 100;
            $('#discount_percent').val(discountPercent > 0 ? discountPercent.toFixed(2) : 0);
        }
    } else {
        discountAmount = 0;
        discountPercent = 0;
    }
    
    let afterDiscount = subtotal - discountAmount;
    if (afterDiscount < 0) afterDiscount = 0;
    
    let taxPercent = parseFloat($('#tax_percent').val()) || 0;
    let taxAmount = afterDiscount * (taxPercent / 100);

    let pph23Percent = parseFloat($('#pph23_percent').val()) || 0;
    let pph23Amount = afterDiscount * (pph23Percent / 100);
    
    let grandTotal = afterDiscount + taxAmount - pph23Amount;
    
    // Update Input Fields & Texts
    $('#subtotal_display').val('Rp ' + formatRupiah(subtotal));
    $('#subtotal-value').val(subtotal);
    $('#discount-value').val(discountAmount);
    $('#after_discount_display').val('Rp ' + formatRupiah(afterDiscount));
    $('#dpp-value').val(afterDiscount);
    $('#tax_amount_display').val(taxAmount > 0 ? formatRupiah(taxAmount) : '');
    $('#tax-value').val(taxAmount);
    $('#pph23_amount_display').val(pph23Amount > 0 ? formatRupiah(pph23Amount) : '');
    $('#pph23-value').val(pph23Amount);
    $('#grand_total_display').val('Rp ' + formatRupiah(grandTotal));
    $('#grand-total-value').val(grandTotal);
}

$(document).ready(function() {
    initSelect2();

    $(document).on('select2:open', function(e) {
        window.setTimeout(function () {
            let searchField = document.querySelector('.select2-container--open .select2-search__field');
            if(searchField) {
                searchField.focus();
            }
        }, 50);
    });

    $('.select2-tags').select2({ tags: true, placeholder: "Select or Type Option", allowClear: true, width: '100%' });
    
    // Jalankan auto-resize text area pertama kali saat data ter-load dari DB
    autoResizeTextarea();
    
    // Event listener saat user sedang mengetik di textarea spesifikasi/deskripsi
    $(document).on('input', '.auto-resize-textarea', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });

    // Set data-value dari nilai awal subtotal (dari database)
    $('.subtotal-display').each(function() {
        let text = $(this).val();
        let number = parseRupiahToNumber(text);
        $(this).attr('data-value', number);
    });
    
    // Format tampilan diskon amount awal
    let initialDiscAmount = $('#discount-value').val() || 0;
    if (parseInt(initialDiscAmount) > 0) {
        $('#discount_amount_display').val(formatRupiah(initialDiscAmount));
    }

    // Format tax amount dan pph amount awal
    let initialTaxAmount = $('#tax-value').val() || 0;
    if (parseInt(initialTaxAmount) > 0) {
        $('#tax_amount_display').val(formatRupiah(initialTaxAmount));
    }
    
    let initialPphAmount = $('#pph23-value').val() || 0;
    if (parseInt(initialPphAmount) > 0) {
        $('#pph23_amount_display').val(formatRupiah(initialPphAmount));
    }
    
    // Tentukan lastDiscountTrigger berdasarkan database
    let initialPercent = parseFloat($('#discount_percent').val()) || 0;
    if (initialPercent > 0) {
        lastDiscountTrigger = 'percent';
    } else if (parseInt(initialDiscAmount) > 0) {
        lastDiscountTrigger = 'amount';
    }
    
    // Matikan flag initial load agar runtime calculation aktif saat di-edit user
    isInitialLoad = false;
    
    // Event valid until automatic rule
    $('#quotation_date').on('change', function() {
        let date = $(this).val();
        if(date) {
            let newDate = new Date(date);
            newDate.setDate(newDate.getDate() + 7);
            let year = newDate.getFullYear();
            let month = String(newDate.getMonth() + 1).padStart(2, '0');
            let day = String(newDate.getDate()).padStart(2, '0');
            let validUntil = $('#valid_until');
            if(!validUntil.val()) validUntil.val(year + '-' + month + '-' + day);
        }
    });
    
    $(document).on('change select2:select', '.product-select', function() {
        let rowId = $(this).data('row');
        let selectedOption = $(this).find(':selected');
        let price = selectedOption.data('price');
        let brand = selectedOption.data('brand');
        let spec = selectedOption.data('specification');
        let desc = selectedOption.data('description');
        
        $(`#row-${rowId} .brand-display`).val(brand ? brand : '-');
        $(`#row-${rowId} .spec-display`).val(spec ? spec : '');
        $(`#row-${rowId} .desc-display`).val(desc ? desc : '');
        
        if(price) {
            $(`#row-${rowId} .price-display`).val(formatRupiah(price));
            $(`#row-${rowId} .price-hidden`).val(price);
        }
        calculateSubtotal(rowId);
        
        // Resize kembali setelah sistem otomatis mengisi teks spesifikasi baru
        autoResizeTextarea();
    });
    
    $(document).on('keyup change', '.price-display', function() {
        let rowId = $(this).data('row');
        let rawValue = $(this).val();
        let numericValue = parseRupiahToNumber(rawValue);
        if(numericValue > 0) {
            $(this).val(formatRupiah(numericValue));
            $(`#row-${rowId} .price-hidden`).val(numericValue);
        } else if(rawValue === '') {
            $(this).val('');
            $(`#row-${rowId} .price-hidden`).val(0);
        }
        calculateSubtotal(rowId);
    });
    
    $(document).on('keyup change', '.qty-input', function() {
        let rowId = $(this).data('row');
        calculateSubtotal(rowId);
    });
    
    $('#discount_percent').on('keyup change', function() {
        lastDiscountTrigger = 'percent';
        calculateGrandTotal();
    });
    
    $('#discount_amount_display').on('keyup', function() {
        lastDiscountTrigger = 'amount';
        let rawVal = $(this).val();
        let numVal = parseRupiahToNumber(rawVal);
        if(numVal > 0) $(this).val(formatRupiah(numVal));
        else if(rawVal === '') $(this).val('');
        calculateGrandTotal();
    });
    
    $('#tax_percent').on('keyup change', function() {
        calculateGrandTotal();
    });

    $('#pph23_percent').on('keyup change', function() {
        calculateGrandTotal();
    });
    
    $('#add-product').click(function() {
        let rowId = rowCount;
        let productOptions = `@foreach($products as $product)
            <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-name="{{ $product->name }}" data-brand="{{ $product->brand }}" data-specification="{{ $product->specification }}" data-description="{{ $product->description }}">{{ $product->product_code }} - {{ $product->name }}</option>
        @endforeach`;

        let newRow = `
        <div class="product-row product-box mb-3" id="row-${rowId}" data-row-id="${rowId}">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label text-dark">Select Product <span class="text-danger">*</span></label>
                    <select name="items[${rowId}][product_id]" class="form-control product-select select2-enable" data-row="${rowId}" required>
                        <option value="">Select Product</option>
                        \\${productOptions}
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Brand</label>
                    <input type="text" class="form-control brand-display" id="brand-${rowId}" readonly placeholder="System Auto">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-dark">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="items[${rowId}][quantity]" class="form-control qty-input fw-bold" data-row="${rowId}" value="1" min="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-dark">Unit Price (Rp) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control price-display fw-semibold text-dark" data-row="${rowId}" required>
                    <input type="hidden" name="items[${rowId}][unit_price]" class="price-hidden" value="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Subtotal (Rp)</label>
                    <input type="text" class="form-control subtotal-display" id="subtotal-${rowId}" readonly placeholder="0" data-value="0">
                    <input type="hidden" name="items[${rowId}][subtotal]" id="subtotal-hidden-${rowId}" class="subtotal-hidden" value="0">
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small mb-1" style="color: #d97706;"><i class="bi bi-gear-fill me-1"></i>Specification (Editable)</label>
                    <textarea name="items[${rowId}][specification]" class="form-control spec-display auto-resize-textarea" id="specification-${rowId}" placeholder="Product specification snapshot..."></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small mb-1" style="color: #64748b;"><i class="bi bi-info-circle-fill me-1"></i>Description / Item Remarks</label>
                    <textarea name="items[${rowId}][description]" class="form-control desc-display auto-resize-textarea" id="description-${rowId}" placeholder="Add specific remarks for this line item if needed..."></textarea>
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-sm btn-outline-danger px-3 fw-bold remove-product" data-row="${rowId}">
                            <i class="bi bi-trash3-fill me-1"></i> Remove Item
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
        
        $('#products-container').append(newRow);
        initSelect2();
        rowCount++;
        calculateGrandTotal();
        
        // Trigger resize untuk row baru
        autoResizeTextarea();
    });
    
    $(document).on('click', '.remove-product', function() {
        let rowId = $(this).data('row');
        $(`#row-${rowId}`).remove();
        calculateGrandTotal();
    });
});
</script>
@endsection