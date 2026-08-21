@extends('layouts.app')

@section('title', 'Add New Quotation')

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
    
    /* Header dengan gradasi warna Ocean Blue */
    .card-header-custom {
        background: linear-gradient(135deg, #0284c7, #075985);
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
    .filter-label, .form-label {
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
        color: #0284c7;
    }

    /* Info Box Area (Customer & PIC) */
    .info-box-bg {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 15px;
    }
    .pic-info {
        background-color: #f0f9ff;
        padding: 12px;
        border-left: 4px solid #0ea5e9;
        border-radius: 0 8px 8px 0;
        font-size: 0.9rem;
        color: #0369a1;
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

    /* Kustomisasi Textarea Inner Specs & Description */
    .spec-display {
        background-color: #f0f9ff;
        border-color: #e0f2fe;
        font-size: 0.88rem;
        resize: none;
    }
    .spec-display:focus {
        background-color: #fff;
    }
    .desc-display {
        background-color: #f8fafc;
        border-color: #f1f5f9;
        font-size: 0.88rem;
        resize: none;
    }
    .desc-display:focus {
        background-color: #fff;
    }

    /* Select2 Style Sync */
    .select2-container .select2-selection--single {
        height: auto !important;
        min-height: 40px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 0.375rem !important;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        white-space: normal !important;
        word-break: break-word !important;
        line-height: 1.4 !important;
        padding-left: 12px !important;
        padding-right: 25px !important;
        padding-top: 8px !important;
        padding-bottom: 8px !important;
        color: #1e293b !important;
        width: 100%;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
        top: 0 !important;
        right: 8px !important;
    }
    .select2-container--default .select2-results__option {
        white-space: normal !important;
        word-break: break-word !important;
        line-height: 1.5 !important;
        padding: 8px 12px !important;
    }

    /* Summary Card Dark Side Layout */
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
        padding: 8px 6px;
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
        color: #38bdf8;
    }
    .summary-table-custom input.form-control-summary {
        background: #334155;
        border: 1px solid #475569;
        color: #fff;
        text-align: right;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        width: 100%;
    }
    .summary-table-custom input.form-control-summary:focus {
        border-color: #38bdf8;
        outline: none;
        box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.3);
    }

    /* Form Utilities */
    .form-control, .form-select {
        border-color: #cbd5e1;
        padding: 0.5rem 0.75rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom">
            <h4><i class="bi bi-file-earmark-plus-fill me-2"></i>Create New Quotation</h4>
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

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4" style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                </div>
            @endif  

            <form action="{{ route('quotations.store') }}" method="POST" id="quotation-form">
                @csrf
                
                {{-- INPUT HIDDEN TRIGGER DISKON --}}
                <input type="hidden" name="discount_trigger" id="discount_trigger" value="percent">

                <div class="row g-3 mb-4 p-3 rounded-3 info-box-bg">
                    <div class="col-12">
                        <div class="section-sub-title mb-2" style="border:none;"><i class="bi bi-building-fill me-2"></i>Primary Identification</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Customer / Perusahaan <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" class="form-control select2-enable" data-placeholder="Search customer name or code..." required>
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" 
                                        data-pic-quotation="{{ $customer->pic_quotation_name }}"
                                        data-pic-quotation-phone="{{ $customer->pic_quotation_phone }}">
                                    {{ $customer->customer_code }} - {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        
                        <div class="mt-3" id="pic-quotation-container" style="display: none;">
                            <div class="pic-info shadow-sm">
                                <div class="fw-bold mb-1"><i class="bi bi-person-badge-fill me-1"></i> Attention Person (PIC):</div>
                                <span id="pic-quotation-name" class="fw-semibold"></span>
                                <span id="pic-quotation-phone" class="d-block text-muted small mt-1"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Quotation Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-calendar3"></i></span>
                            <input type="date" name="date" id="quotation_date" class="form-control border-start-0" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Valid Until</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-calendar-check"></i></span>
                            <input type="date" name="valid_until" id="valid_until" class="form-control border-start-0" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                        </div>
                        <small class="text-muted" style="font-size: 0.78rem;">Auto-generated +7 days</small>
                    </div>

                    <div class="col-12 mt-2">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="show_image_on_print" class="form-check-input" id="show_image" value="1">
                            <label class="form-check-label fw-semibold text-dark small" for="show_image" style="cursor: pointer;">
                                Display Product Images on Quotation PDF Printout
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="section-sub-title"><i class="bi bi-box-seam-fill me-2"></i>Line Items / Product Details</div>
                    
                    <div id="products-container">
                        <div class="product-row product-box mb-3" id="row-0">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label text-dark">Select Product <span class="text-danger">*</span></label>
                                    <select name="items[0][product_id]" class="form-control product-select select2-enable" data-row="0" data-placeholder="Type code or product name..." required>
                                        <option value="">Select Product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" 
                                                    data-price="{{ $product->price }}" 
                                                    data-code="{{ $product->product_code }}"
                                                    data-name="{{ $product->name }}" 
                                                    data-brand="{{ $product->brand }}"
                                                    data-specification="{{ $product->specification }}"
                                                    data-description="{{ $product->description }}">
                                                {{ $product->product_code }} - {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Brand</label>
                                    <input type="text" class="form-control brand-display" id="brand-0" readonly placeholder="">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-dark">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="items[0][quantity]" class="form-control qty-input fw-bold" data-row="0" value="1" min="1" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-dark">Unit Price (Rp) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control price-display fw-semibold text-dark" data-row="0" required placeholder="0">
                                    <input type="hidden" name="items[0][unit_price]" class="price-hidden" data-row="0" value="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Subtotal (Rp)</label>
                                    <input type="text" class="form-control subtotal-display" id="subtotal-0" readonly placeholder="0">
                                    <input type="hidden" name="items[0][subtotal]" id="subtotal-hidden-0" value="0">
                                </div>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small mb-1" style="color: #0284c7;"><i class="bi bi-gear-fill me-1"></i>Specification (Editable)</label>
                                    <textarea name="items[0][specification]" class="form-control spec-display auto-row-textarea" id="specification-0" rows="1" placeholder="Product specification snapshot from database...">{{ old('items.0.specification', '') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1" style="color: #64748b;"><i class="bi bi-info-circle-fill me-1"></i>Description / Item Remarks</label>
                                    <textarea name="items[0][description]" class="form-control desc-display auto-row-textarea" id="description-0" rows="1" placeholder="Add specific remarks for this line item if needed...">{{ old('items.0.description', '') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-bold mt-1" id="add-product">
                        <i class="bi bi-plus-circle-fill me-1"></i> Add Item Row
                    </button>
                </div>
                
                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-white h-100">
                            <div class="section-sub-title mb-3"><i class="bi bi-credit-card-fill me-2"></i>Commercial Terms & Conditions</div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
                                    <select name="payment_terms" class="form-select select2-tags" required>
                                        <option value="">Select Payment Terms</option>
                                        @php
                                            $defaultPayments = [
                                                'Cash Before Delivery (CBD)', 'Cash on Delivery (COD)', '100% in advance',
                                                '30% DP after Received PO & 70% Final payment Before Delivery',
                                                '40% DP after Received PO & 60% Final payment Before Delivery',
                                                '50% DP after Received PO & 50% Final payment Before Delivery',
                                                '60% DP after Received PO & 40% Final payment Before Delivery',
                                                '70% DP after Received PO & 30% Final payment Before Delivery',
                                                '14 Days after Delivery Date', '30 Days after Delivery Date',
                                                '45 Days after Delivery Date', '60 Days after Delivery Date'
                                            ];
                                            $dbPayments = $existingPaymentTerms ?? [];
                                            $mergedPayments = array_unique(array_filter(array_merge($defaultPayments, $dbPayments)));
                                        @endphp
                                        @foreach($mergedPayments as $payment)
                                            <option value="{{ $payment }}" {{ old('payment_terms', '100% in advance') == $payment ? 'selected' : '' }}>
                                                {{ $payment }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Delivery Time</label>
                                    <select name="delivery_time" class="form-select select2-tags">
                                        <option value="">Select Delivery Time</option>
                                        @php
                                            $defaultOptions = [
                                                'Mentioned Above', 'Around 1-1.5 Months after received your payment & P/O',
                                                'Around 2-3 weeks after received your DP & PO', '1-2 Working days',
                                                '3-5 Working days', '1 Week', '2 Weeks', '1 Month', '2 Months', '3 Months', '6 Months'
                                            ];
                                            $dbOptions = $existingDeliveryTimes ?? [];
                                            $mergedOptions = array_unique(array_filter(array_merge($defaultOptions, $dbOptions)));
                                        @endphp
                                        @foreach($mergedOptions as $option)
                                            <option value="{{ $option }}" {{ old('delivery_time', 'Mentioned Above') == $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="form-label">General Remarks / Quotation Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Enter general notes..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    {{-- DUAL INTERACTIVE SUMMARY CARD (CLEAN & STREAMLINED VARIANT) --}}
                    <div class="col-md-6">
                        <div class="summary-table-container">
                            <table class="summary-table-custom">
                                {{-- 1. Subtotal Gross --}}
                                <tr>
                                    <th width="45%">Subtotal Item Gross</th>
                                    <td width="55%">
                                        <input type="text" id="subtotal_display" class="form-control-summary" style="background:transparent; border:none; padding-right:0;" readonly value="Rp 0">
                                        <input type="hidden" name="subtotal" id="subtotal-value" value="0">
                                    </td>
                                </tr>

                                {{-- 2. Discount Rate & Amount (1 Baris Rapi) --}}
                                <tr>
                                    <th>Discount</th>
                                    <td>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div style="width: 75px; position: relative;">
                                                <input type="number" name="discount_percent" id="discount_percent" class="form-control form-control-summary text-warning text-end pe-3" step="0.01" min="0" max="100" value="0" style="padding-left: 5px;">
                                                <span style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: #ffc107; pointer-events: none;">%</span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="text" id="discount_amount_display" class="form-control-summary text-warning" placeholder="Rp 0">
                                                <input type="hidden" name="discount_amount" id="discount-value" value="0">
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- 3. DPP --}}
                                <tr>
                                    <th>Subtotal After Discount (DPP)</th>
                                    <td>
                                        <input type="text" id="after_discount_display" class="form-control-summary" style="background:transparent; border:none; padding-right:0;" readonly value="Rp 0">
                                        <input type="hidden" name="dpp" id="dpp-value" value="0">
                                    </td>
                                </tr>

                                {{-- 4. Tax / PPN Rate & Amount (1 Baris Rapi) --}}
                                <tr>
                                    <th>VAT PPN</th>
                                    <td>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div style="width: 75px; position: relative;">
                                                <input type="number" name="tax_percent" id="tax_percent" class="form-control form-control-summary text-info text-end pe-3" step="0.01" min="0" max="100" value="11" style="padding-left: 5px;">
                                                <span style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: #0dcaf0; pointer-events: none;">%</span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="text" id="tax_amount_display" class="form-control-summary text-info" placeholder="Rp 0">
                                                <input type="hidden" name="tax_amount" id="tax-value" value="0">
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- 5. PPh 23 Rate & Amount (1 Baris Rapi) --}}
                                <tr>
                                    <th>PPh Income Tax 23</th>
                                    <td>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div style="width: 75px; position: relative;">
                                                <input type="number" name="pph23_percent" id="pph23_percent" class="form-control form-control-summary text-danger text-end pe-3" step="0.01" min="0" max="100" value="0" style="padding-left: 5px;">
                                                <span style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: #dc3545; pointer-events: none;">%</span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="text" id="pph23_amount_display" class="form-control-summary text-danger" placeholder="Rp 0">
                                                <input type="hidden" name="pph23_amount" id="pph23-value" value="0">
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- 6. Grand Total --}}
                                <tr class="grand-total-row">
                                    <th>Grand Total Due</th>
                                    <td>
                                        <input type="text" id="grand_total_display" class="form-control-summary" style="background:transparent; border:none; padding-right:0; color:#38bdf8; font-size:1.2rem; font-weight:700;" readonly value="Rp 0">
                                        <input type="hidden" name="total" id="grand-total-value" value="0">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 pt-3 border-top d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold" style="background-color: #0284c7; border-color: #0284c7;">
                        <i class="bi bi-save-fill me-1"></i> Save Quotation
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
let rowCount = 1;
let activeTriggers = { discount: 'percent', tax: 'percent', pph23: 'percent' };

function adjustTextareaRows(textarea) {
    if (!textarea) return;
    let value = textarea.value.trim();
    if (value === '' || value === '-') {
        textarea.rows = 1;
        return;
    }
    let rows = value.split(/\r\n|\r|\n/).length;
    textarea.rows = Math.max(rows, 1);
}

function formatRupiah(angka) {
    if(!angka || isNaN(angka)) return '0';
    return new Intl.NumberFormat('id-ID').format(Math.round(angka));
}

function parseRupiahToNumber(rupiahString) {
    if(!rupiahString) return 0;
    return parseInt(rupiahString.toString().replace(/\./g, '')) || 0;
}

function initSelect2() {
    $('.select2-enable').select2({
        placeholder: function() { return $(this).data('placeholder') || "Select Option"; },
        allowClear: true,
        width: '100%'
    });
}

function calculateSubtotal(rowId) {
    let qty = parseInt($(`#row-${rowId} .qty-input`).val()) || 0;
    let priceDisplay = $(`#row-${rowId} .price-display`).val();
    let price = parseRupiahToNumber(priceDisplay);
    
    let subtotal = qty * price;
    $(`#row-${rowId} .subtotal-display`).val(formatRupiah(subtotal));
    $(`#row-${rowId} .subtotal-display`).attr('data-value', subtotal);
    $(`#row-${rowId} .price-hidden`).val(price);
    $(`#row-${rowId} #subtotal-hidden-${rowId}`).val(subtotal);
    
    calculateGrandTotal();
}

// FUNGSI UTAMA HITUNG MANDIRI - BERJALAN SAAT INPUT DI KETIK SAJA
function calculateGrandTotal() {
    let subtotal = 0;
    $('.subtotal-display').each(function() {
        subtotal += parseInt($(this).attr('data-value') || 0);
    });

    $('#subtotal_display').val('Rp ' + formatRupiah(subtotal));
    $('#subtotal-value').val(subtotal);

    // 1. Perhitungan Diskon Dua Arah murni
    let discPercent = parseFloat($('#discount_percent').val()) || 0;
    let discAmount = parseRupiahToNumber($('#discount_amount_display').val());

    if (subtotal > 0) {
        if (activeTriggers.discount === 'percent') {
            discAmount = subtotal * (discPercent / 100);
            $('#discount_amount_display').val(discAmount > 0 ? formatRupiah(discAmount) : '');
        } else if (activeTriggers.discount === 'amount') {
            discPercent = (discAmount / subtotal) * 100;
            $('#discount_percent').val(discPercent > 0 ? discPercent.toFixed(2) : 0);
        }
    }
    $('#discount-value').val(Math.round(discAmount));

    let afterDiscount = subtotal - discAmount;
    if(afterDiscount < 0) afterDiscount = 0;
    $('#after_discount_display').val('Rp ' + formatRupiah(afterDiscount));
    $('#dpp-value').val(Math.round(afterDiscount));

    // 2. Perhitungan PPN Dua Arah murni
    let taxPercent = parseFloat($('#tax_percent').val()) || 0;
    let taxAmount = parseRupiahToNumber($('#tax_amount_display').val());

    if (afterDiscount > 0) {
        if (activeTriggers.tax === 'percent') {
            taxAmount = afterDiscount * (taxPercent / 100);
            $('#tax_amount_display').val(taxAmount > 0 ? formatRupiah(taxAmount) : '');
        } else if (activeTriggers.tax === 'amount') {
            taxPercent = (taxAmount / afterDiscount) * 100;
            $('#tax_percent').val(taxPercent > 0 ? taxPercent.toFixed(2) : 0);
        }
    }
    $('#tax-value').val(Math.round(taxAmount));

    // 3. Perhitungan PPh 23 Dua Arah murni
    let pph23Percent = parseFloat($('#pph23_percent').val()) || 0;
    let pph23Amount = parseRupiahToNumber($('#pph23_amount_display').val());

    if (afterDiscount > 0) {
        if (activeTriggers.pph23 === 'percent') {
            pph23Amount = afterDiscount * (pph23Percent / 100);
            $('#pph23_amount_display').val(pph23Amount > 0 ? formatRupiah(pph23Amount) : '');
        } else if (activeTriggers.pph23 === 'amount') {
            pph23Percent = (pph23Amount / afterDiscount) * 100;
            $('#pph23_percent').val(pph23Percent > 0 ? pph23Percent.toFixed(2) : 0);
        }
    }
    $('#pph23-value').val(Math.round(pph23Amount));

    // 4. Grand Total Akhir (Rumus Baru: DPP + PPN - PPh23)
    let grandTotal = afterDiscount + taxAmount - pph23Amount;
    if(grandTotal < 0) grandTotal = 0;

    $('#grand_total_display').val('Rp ' + formatRupiah(grandTotal));
    $('#grand-total-value').val(Math.round(grandTotal));
}

$(document).ready(function() {
    initSelect2();

    $('.select2-tags').select2({
        tags: true,
        placeholder: "Select or Type Option",
        allowClear: true,
        width: '100%'
    });

    // Mencegah Form melakukan recalculate run-time tambahan saat di submit
    $('#quotation-form').on('submit', function() {
        return true; 
    });

    // Event Listener Deteksi Ketikan Aktif User
    $('#discount_percent').on('keyup input', function() { 
        activeTriggers.discount = 'percent'; 
        $('#discount_trigger').val('percent'); 
        calculateGrandTotal(); 
    });
    $('#discount_amount_display').on('keyup input', function() {
        activeTriggers.discount = 'amount';
        $('#discount_trigger').val('amount');
        let val = parseRupiahToNumber($(this).val());
        $(this).val(val > 0 ? formatRupiah(val) : '');
        calculateGrandTotal();
    });

    $('#tax_percent').on('keyup input', function() { activeTriggers.tax = 'percent'; calculateGrandTotal(); });
    $('#tax_amount_display').on('keyup input', function() {
        activeTriggers.tax = 'amount';
        let val = parseRupiahToNumber($(this).val());
        $(this).val(val > 0 ? formatRupiah(val) : '');
        calculateGrandTotal();
    });

    $('#pph23_percent').on('keyup input', function() { activeTriggers.pph23 = 'percent'; calculateGrandTotal(); });
    $('#pph23_amount_display').on('keyup input', function() {
        activeTriggers.pph23 = 'amount';
        let val = parseRupiahToNumber($(this).val());
        $(this).val(val > 0 ? formatRupiah(val) : '');
        calculateGrandTotal();
    });

    // Event autofill master produk saat dipilih
    $(document).on('change select2:select', '.product-select', function() {
        let rowId = $(this).data('row');
        let selectedOption = $(this).find(':selected');

        $(`#row-${rowId} .brand-display`).val(selectedOption.data('brand') || '-');
        $(`#specification-${rowId}`).val(selectedOption.data('specification') || '');
        $(`#description-${rowId}`).val(selectedOption.data('description') || '');

        adjustTextareaRows(document.getElementById(`specification-${rowId}`));
        adjustTextareaRows(document.getElementById(`description-${rowId}`));

        let price = selectedOption.data('price');
        $(`#row-${rowId} .price-display`).val(price ? formatRupiah(price) : '');
        calculateSubtotal(rowId);
    });

    $(document).on('keyup change', '.price-display', function() {
        let rowId = $(this).data('row');
        let numericValue = parseRupiahToNumber($(this).val());
        $(this).val(numericValue > 0 ? formatRupiah(numericValue) : '');
        calculateSubtotal(rowId);
    });

    $(document).on('keyup change', '.qty-input', function() {
        calculateSubtotal($(this).data('row'));
    });

    $('#customer_id').on('change select2:select', function() {
        let selected = $(this).find(':selected');
        if (selected.val() !== '' && selected.data('pic-quotation')) {
            $('#pic-quotation-name').html('<i class="bi bi-person-fill"></i> ' + selected.data('pic-quotation'));
            $('#pic-quotation-phone').html('<i class="bi bi-telephone-fill"></i> ' + (selected.data('pic-quotation-phone') || '-'));
            $('#pic-quotation-container').fadeIn(200);
        } else {
            $('#pic-quotation-container').fadeOut(150);
        }
    });

    $('#quotation_date').on('change', function() {
        let date = $(this).val();
        if(date) {
            let newDate = new Date(date);
            newDate.setDate(newDate.getDate() + 7);
            $('#valid_until').val(newDate.toISOString().slice(0,10));
        }
    }).trigger('change');

    $('#add-product').click(function() {
        let newRow = `
            <div class="product-row product-box mb-3" id="row-${rowCount}">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label text-dark">Select Product <span class="text-danger">*</span></label>
                        <select name="items[${rowCount}][product_id]" class="form-control product-select select2-enable" data-row="${rowCount}" required>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-brand="{{ $product->brand }}" data-specification="{{ $product->specification }}" data-description="{{ $product->description }}">
                                    {{ $product->product_code }} - {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Brand</label><input type="text" class="form-control brand-display" readonly></div>
                    <div class="col-md-2"><label class="form-label text-dark">Quantity *</label><input type="number" name="items[${rowCount}][quantity]" class="form-control qty-input fw-bold" data-row="${rowCount}" value="1" min="1" required></div>
                    <div class="col-md-2"><label class="form-label text-dark">Unit Price (Rp) *</label><input type="text" class="form-control price-display fw-semibold text-dark" data-row="${rowCount}" required placeholder="0"><input type="hidden" name="items[${rowCount}][unit_price]" class="price-hidden" data-row="${rowCount}" value="0"></div>
                    <div class="col-md-2"><label class="form-label">Subtotal (Rp)</label><input type="text" class="form-control subtotal-display" id="subtotal-${rowCount}" readonly placeholder="0"><input type="hidden" name="items[${rowCount}][subtotal]" id="subtotal-hidden-${rowCount}" value="0"></div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small mb-1" style="color: #0284c7;"><i class="bi bi-gear-fill me-1"></i>Specification</label><textarea name="items[${rowCount}][specification]" class="form-control spec-display auto-row-textarea" id="specification-${rowCount}" rows="1"></textarea></div>
                    <div class="col-md-6"><label class="form-label small mb-1" style="color: #64748b;"><i class="bi bi-info-circle-fill me-1"></i>Description</label><textarea name="items[${rowCount}][description]" class="form-control desc-display auto-row-textarea" id="description-${rowCount}" rows="1"></textarea>
                    <div class="text-end mt-2"><button type="button" class="btn btn-sm btn-outline-danger remove-product" data-row="${rowCount}"><i class="bi bi-trash3-fill"></i> Remove</button></div></div>
                </div>
            </div>`;
        $('#products-container').append(newRow);
        initSelect2();
        rowCount++;
    });

    $(document).on('click', '.remove-product', function() {
        $(`#row-${$(this).data('row')}`).remove();
        calculateGrandTotal();
    });
});
</script>
@endsection