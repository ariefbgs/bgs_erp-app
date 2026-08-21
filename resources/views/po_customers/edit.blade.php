@extends('layouts.app')
@section('title', 'Edit PO Customer')
@section('content')
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
    
    /* Header dengan gradasi warna Oranye Terang khas Edit State */
    .card-header-custom {
        background: linear-gradient(135deg, #f97316, #c2410c);
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
        color: #f97316;
    }

    /* Info Box Area */
    .info-group-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        height: 100%;
    }

    /* Form Utilities */
    .form-control, .form-select {
        border-color: #cbd5e1;
        padding: 0.5rem 0.75rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
    }

    /* Row Item Produk Kontainer */
    .product-row {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 18px;
        transition: all 0.2s;
    }
    .product-row:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    }

    /* Aliran input kanan text */
    .price-input, .subtotal-display, .qty-input, .price-display, .summary-input-num {
        text-align: right;
    }
    .brand-display, .code2-display, .name2-display, .readonly-bg {
        background-color: #f1f5f9 !important;
        color: #475569;
        font-weight: 500;
    }

    /* Ringkasan Finansial Tabel */
    .table-summary th {
        font-size: 0.85rem;
        text-transform: uppercase;
        color: #475569;
        vertical-align: middle;
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h4><i class="bi bi-pencil-square me-2"></i>Edit PO Customer</h4>
            <a href="{{ route('po-customers.index') }}" class="btn btn-light btn-sm fw-bold px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
        
        <div class="card-body p-4">
            {{-- ALERT VALIDASI ERROR / SUCCESS --}}
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
                <div class="alert alert-success border-0 shadow-sm d-flex fade show mb-4" style="background-color: #f0fdf4; color: #166534; border-left: 4px solid #16a34a !important; border-radius: 6px;">
                    <div class="me-2"><i class="bi bi-check-circle-fill fs-5"></i></div>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size: 0.8rem;"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4" style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                </div>
            @endif  

            <form action="{{ route('po-customers.update', $poCustomer->id) }}" method="POST" id="po-form" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                {{-- FORM INFORMASI UTAMA & RELASI --}}
                <div class="row g-4 mb-4">
                    {{-- BLOK KIRI: ENTITAS RELASI CUSTOMER --}}
                    <div class="col-md-6">
                        <div class="info-group-box d-flex flex-column gap-3">
                            <div>
                                <div class="section-sub-title mb-3"><i class="bi bi-building me-2"></i>1. Client / Customer Relationship</div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Customer Corporation</label>
                                    <input type="text" class="form-control readonly-bg" value="{{ $poCustomer->customer->customer_code }} - {{ $poCustomer->customer->name }}" readonly>
                                    <input type="hidden" name="customer_id" id="customer_id" value="{{ $poCustomer->customer_id }}">
                                </div>

                                {{-- INFORMASI PIC CUSTOMER DYNAMIC BADGE --}}
                                <div id="pic-info" class="alert border-0 shadow-sm d-flex align-items-center mt-3 mb-0" style="background-color: #eff6ff; color: #1e40af;">
                                    <i class="bi bi-person-lines-fill me-2 fs-5"></i>
                                    <div>
                                        <span class="fw-bold">Active Contact Personnel:</span> <span id="pic-name" class="fw-semibold">{{ $poCustomer->customer->pic_quotation_name ?? '-' }}</span> 
                                        <span class="mx-1">|</span> <i class="bi bi-telephone-fill small me-1"></i><span id="pic-phone">{{ $poCustomer->customer->pic_quotation_phone ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- ALAMAT DELIVERY --}}
                            <div>
                                <label class="form-label">Shipping Address <span class="text-danger">*</span></label>
                                <textarea name="shipping_address" id="shipping_address" class="form-control mb-2" rows="3" placeholder="Input complete shipping destination address..." required>{{ $poCustomer->shipping_address ?? ($poCustomer->customer->shipping_address ?? '-') }}</textarea>
                                <div class="form-check form-switch fs-7">
                                    <input class="form-check-input" type="checkbox" name="update_customer_master_address" id="update_customer_master_address" value="1">
                                    <label class="form-check-label text-muted small fw-semibold" for="update_customer_master_address">
                                        Update / Save this address back to Customer Master Data
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- BLOK KANAN: DOKUMEN ADMINISTRASI PO --}}
                    <div class="col-md-6">
                        <div class="info-group-box">
                            <div class="section-sub-title"><i class="bi bi-file-earmark-text me-2"></i>2. Purchase Order (PO) & Terms Registry</div>
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">PO Reference Number</label>
                                    <input type="text" name="po_number" class="form-control readonly-bg" value="{{ $poCustomer->po_number }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">PO Date <span class="text-danger">*</span></label>
                                    <input type="date" name="po_date" id="po_date" class="form-control" value="{{ $poCustomer->po_date instanceof \Carbon\Carbon ? $poCustomer->po_date->format('Y-m-d') : date('Y-m-d', strtotime($poCustomer->po_date)) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Target Delivery Date <span class="text-danger">*</span></label>
                                    <input type="date" name="delivery_date" id="delivery_date" class="form-control" value="{{ $poCustomer->delivery_date ? ($poCustomer->delivery_date instanceof \Carbon\Carbon ? $poCustomer->delivery_date->format('Y-m-d') : date('Y-m-d', strtotime($poCustomer->delivery_date))) : '' }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Processing Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select fw-semibold" required>
                                        <option value="received" {{ $poCustomer->status == 'received' ? 'selected' : '' }}>Received</option>
                                        <option value="processed" {{ $poCustomer->status == 'processed' ? 'selected' : '' }}>Processed</option>
                                        <option value="delivered" {{ $poCustomer->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                        <option value="cancelled" {{ $poCustomer->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </div>

                                {{-- PAYMENT TERMS --}}
                                <div class="col-md-6">
                                    <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
                                    <select name="payment_terms" id="payment_terms" class="form-select select2-tags" required>
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
                                            if (!empty($poCustomer->payment_terms)) {
                                                $dbPayments[] = $poCustomer->payment_terms;
                                            }
                                            $mergedPayments = array_unique(array_filter(array_merge($defaultPayments, $dbPayments)));
                                        @endphp

                                        @foreach($mergedPayments as $payment)
                                            <option value="{{ $payment }}" {{ $poCustomer->payment_terms == $payment ? 'selected' : '' }}>
                                                {{ $payment }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                {{-- DELIVERY TIME --}}
                                <div class="col-md-6">
                                    <label class="form-label">Delivery Time</label>
                                    <select name="delivery_time" id="delivery_time" class="form-select select2-tags">
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
                                            if (!empty($poCustomer->delivery_time)) {
                                                $dbDelivery[] = $poCustomer->delivery_time;
                                            }
                                            $mergedDelivery = array_unique(array_filter(array_merge($defaultDelivery, $dbDelivery)));
                                        @endphp

                                        @foreach($mergedDelivery as $option)
                                            <option value="{{ $option }}" {{ $poCustomer->delivery_time == $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- DETAIL PRODUK DAN BARANG --}}
                <div class="info-group-box mb-4">
                    <div class="section-sub-title d-flex justify-content-between align-items-center text-dark w-100">
                        <span><i class="bi bi-box-seam-fill me-2"></i>3. Product Detail</span>
                        <button type="button" class="btn btn-success btn-sm fw-bold px-3 ms-auto" id="add-product">
                            <i class="bi bi-plus-circle-fill me-1"></i> Add Product Row
                        </button>
                    </div>
                    
                    {{-- WADAH BARIS DINAMIS --}}
                    <div id="products-container" class="d-flex flex-column gap-3 mb-3">
                        @foreach($poCustomer->details as $index => $detail)
                        <div class="product-row position-relative" id="row-{{ $index }}">
                            <div class="row g-2 text-start">
                                <div class="col-md-2">
                                    <label class="form-label small">Brand</label>
                                    <input type="text" class="form-control form-control-sm brand-display" id="brand-{{ $index }}" readonly value="{{ $detail->product->brand ?? '-' }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Select Product</label>
                                    <select name="items[{{ $index }}][product_id]" class="form-select form-select-sm product-select" data-row="{{ $index }}" required>
                                        <option value="">-- Choose Product --</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" 
                                                data-price="{{ $product->price }}" 
                                                data-brand="{{ $product->brand ?? '-' }}"
                                                data-product-code2="{{ $product->product_code2 ?? '' }}"
                                                data-name2="{{ $product->name2 ?? '' }}"
                                                {{ $detail->product_id == $product->id ? 'selected' : '' }}>
                                                {{ $product->name2 }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small">Qty</label>
                                    <input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-sm qty-input" data-row="{{ $index }}" value="{{ $detail->quantity }}" min="1" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Price (Rp)</label>
                                    <input type="text" class="form-control form-control-sm price-display" data-row="{{ $index }}" value="{{ number_format($detail->unit_price, 0, ',', '.') }}" required>
                                    <input type="hidden" name="items[{{ $index }}][unit_price]" class="price-hidden" data-row="{{ $index }}" value="{{ $detail->unit_price }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Subtotal (Rp)</label>
                                    <input type="text" class="form-control form-control-sm subtotal-display" id="subtotal-{{ $index }}" readonly value="{{ number_format($detail->subtotal, 0, ',', '.') }}" data-value="{{ $detail->subtotal }}">
                                    <input type="hidden" name="items[{{ $index }}][subtotal]" id="subtotal-hidden-{{ $index }}" class="subtotal-hidden" value="{{ $detail->subtotal }}">
                                </div>
                                <div class="col-md-1 d-flex align-items-end justify-content-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-product" data-row="{{ $index }}" title="Remove Item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                {{-- RINGKASAN FINANSIAL (SUMMARY) & ATTACHMENT --}}
                <div class="row g-4 text-start">
                    {{-- CATATAN & LAMPIRAN BERKAS --}}
                    <div class="col-md-5">
                        <div class="info-group-box">
                            <div class="section-sub-title"><i class="bi bi-chat-right-quote-fill me-2"></i>4. Additional Data</div>
                            
                            <div class="mb-3">
                                <label class="form-label">Internal Remarks / Terms Notes</label>
                                <textarea name="notes" class="form-control" rows="4" placeholder="Write payment terms, delivery notes or instructions...">{{ $poCustomer->notes }}</textarea>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Document Attachment (PDF/Image)</label>
                                
                                <input type="hidden" name="delete_attachment" id="delete_attachment" value="0">

                                @if($poCustomer->attachment)
                                    <div class="mb-2 p-2 border rounded bg-light d-flex align-items-center justify-content-between" id="attachment-container">
                                        <a href="{{ route('po-customers.view-image', $poCustomer->id) }}" target="_blank" class="btn btn-sm btn-outline-primary fw-semibold py-1">
                                            <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> Review Uploaded Document
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger fw-bold" id="btn-delete-attachment">
                                            <i class="bi bi-trash-fill me-1"></i> Delete File
                                        </button>
                                    </div>
                                @endif
                                
                                <input type="file" name="attachment" id="file-input" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted d-block mt-1">Maximum size: 5MB. Uploading a new file will automatically replace the old one.</small>
                            </div>
                        </div>
                    </div>

                    {{-- PERHITUNGAN AKUMULASI DUA ARAH (SUMMARY CARD) --}}
                    <div class="col-md-7">
                        <div class="border rounded-3 overflow-hidden shadow-sm">
                            <table class="table table-bordered table-summary m-0 bg-white align-middle">
                                <tr>
                                    <th width="45%" class="ps-3">Subtotal Items Amount</th>
                                    <td colspan="2" class="text-end pe-3 fw-bold text-dark" id="subtotal-text">Rp 0</td>
                                    <input type="hidden" name="subtotal" id="subtotal-value" value="0">
                                </tr>
                                <tr>
                                    <th class="ps-3">Discount Allowance</th>
                                    <td width="25%" class="px-2">
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="discount_percent" id="discount_percent" class="form-control text-end summary-input-num" step="0.01" min="0" max="100" value="{{ $poCustomer->discount_percent ?? 0 }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td width="30%" class="pe-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" id="discount_amount_display" class="form-control text-end summary-input-num" value="{{ number_format($poCustomer->discount_amount ?? 0, 0, ',', '.') }}">
                                            <input type="hidden" name="discount_amount" id="discount-value" value="{{ $poCustomer->discount_amount ?? 0 }}">
                                        </div>
                                    </td>
                                </tr>
                                <tr style="background-color: #f8fafc;">
                                    <th class="ps-3 fw-semibold text-secondary">Subtotal After Discount (DPP)</th>
                                    <td colspan="2" class="text-end pe-3 fw-bold text-secondary">
                                        <span id="subtotal-after-discount-text">Rp 0</span>
                                        <input type="hidden" name="dpp_amount" id="dpp-value" value="0">
                                    </td>
                                </tr>
                                <tr>
                                    <th class="ps-3">VAT Standard Tax</th>
                                    <td class="px-2">
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="tax_percent" id="tax_percent" class="form-control text-end summary-input-num" step="0.01" min="0" max="100" value="{{ $poCustomer->tax_percent ?? 11 }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td class="pe-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" id="tax_amount_display" class="form-control text-end summary-input-num" value="{{ number_format($poCustomer->tax_amount ?? 0, 0, ',', '.') }}">
                                            <input type="hidden" name="tax_amount" id="tax-value" value="{{ $taxAmount ?? 0 }}">
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="ps-3">PPh Income Tax</th>
                                    <td class="px-2">
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="pph_percent" id="pph_percent" class="form-control text-end summary-input-num" step="0.01" min="0" max="100" value="{{ $poCustomer->pph_percent ?? 0 }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td class="pe-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" id="pph_amount_display" class="form-control text-end summary-input-num" value="{{ number_format($poCustomer->pph_amount ?? 0, 0, ',', '.') }}">
                                            <input type="hidden" name="pph_amount" id="pph-value" value="{{ $poCustomer->pph_amount ?? 0 }}">
                                        </div>
                                    </td>
                                </tr>
                                <tr style="background-color: #f8fafc; border-top: 2px solid #cbd5e1;">
                                    <th class="ps-3 fs-6 text-dark"><strong>Grand Total Invoice</strong></th>
                                    <td colspan="2" class="text-end pe-3 fs-5 text-success fw-bold">
                                        <strong><span id="grand-total-text">Rp 0</span></strong>
                                        <input type="hidden" name="total" id="grand-total-value" value="{{ $poCustomer->total ?? 0 }}">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ACTION BUTTONS --}}
                <div class="col-12 mt-4 pt-3 border-top d-flex gap-2">
                    <button type="submit" class="btn btn-warning px-4 py-2 fw-bold text-white" style="background: linear-gradient(135deg, #f97316, #c2410c); border:none;">
                        <i class="bi bi-save-fill me-1"></i> Update Data PO Customer
                    </button>
                    <a href="{{ route('po-customers.index') }}" class="btn btn-outline-secondary px-4 py-2">Discard Changes</a>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<script>
let rowCount = {{ count($poCustomer->details) }};
let productsData = @json($products);
let isCalculating = false;

function formatRupiah(angka) {
    if(!angka || isNaN(angka)) return '0';
    return new Intl.NumberFormat('id-ID').format(Math.round(angka));
}

function parseRupiahToNumber(str) {
    if(!str) return 0;
    return parseFloat(str.toString().replace(/\./g, '').replace(/,/g, '.')) || 0;
}

function applyAutoFocusToSelect2($element) {
    $element.on('select2:open', function() {
        setTimeout(function() {
            const searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField) {
                searchField.focus();
            }
        }, 100);
    });
}

function initProductSelect2(selector) {
    $(selector).each(function() {
        if (!$(this).hasClass("select2-hidden-accessible")) {
            var $select = $(this).select2({
                theme: "bootstrap-5",
                placeholder: "-- Search Product --",
                allowClear: true,
                width: "100%",
                dropdownParent: $(this).parent()
            });
            applyAutoFocusToSelect2($select);
        }
    });
}

function calculateGrandTotal(triggerSource = 'all', isInit = false) {
    if (isCalculating) return;
    isCalculating = true;

    let subtotal = 0;
    $('.subtotal-display').each(function() {
        let val = $(this).data('value') || 0;
        subtotal += parseInt(val);
    });
    $('#subtotal-text').text('Rp ' + formatRupiah(subtotal));
    $('#subtotal-value').val(subtotal);

    let discountPercent = parseFloat($('#discount_percent').val()) || 0;
    let discountAmount = parseRupiahToNumber($('#discount_amount_display').val());

    if (isInit) {
        $('#discount_amount_display').val(formatRupiah(discountAmount));
    } else if (triggerSource === 'discount_amount') {
        discountPercent = subtotal > 0 ? (discountAmount / subtotal) * 100 : 0;
        $('#discount_percent').val(discountPercent.toFixed(2));
    } else {
        discountAmount = subtotal * (discountPercent / 100);
        $('#discount_amount_display').val(formatRupiah(discountAmount));
    }
    $('#discount-value').val(discountAmount);

    let dppAmount = subtotal - discountAmount;
    $('#subtotal-after-discount-text').text('Rp ' + formatRupiah(dppAmount));
    $('#dpp-value').val(dppAmount);

    let taxPercent = parseFloat($('#tax_percent').val()) || 0;
    let taxAmount = parseRupiahToNumber($('#tax_amount_display').val());

    if (isInit) {
        $('#tax_amount_display').val(formatRupiah(taxAmount));
    } else if (triggerSource === 'tax_amount') {
        taxPercent = dppAmount > 0 ? (taxAmount / dppAmount) * 100 : 0;
        $('#tax_percent').val(taxPercent.toFixed(2));
    } else {
        taxAmount = dppAmount * (taxPercent / 100);
        $('#tax_amount_display').val(formatRupiah(taxAmount));
    }
    $('#tax-value').val(taxAmount);

    let pphPercent = parseFloat($('#pph_percent').val()) || 0;
    let pphAmount = parseRupiahToNumber($('#pph_amount_display').val());

    if (isInit) {
        $('#pph_amount_display').val(formatRupiah(pphAmount));
    } else if (triggerSource === 'pph_amount') {
        pphPercent = dppAmount > 0 ? (pphAmount / dppAmount) * 100 : 0;
        $('#pph_percent').val(pphPercent.toFixed(2));
    } else {
        pphAmount = dppAmount * (pphPercent / 100);
        $('#pph_amount_display').val(formatRupiah(pphAmount));
    }
    $('#pph-value').val(pphAmount);

    let grandTotal = dppAmount + taxAmount - pphAmount;
    $('#grand-total-text').text('Rp ' + formatRupiah(grandTotal));
    $('#grand-total-value').val(grandTotal);

    isCalculating = false;
}

function calculateSubtotal(rowId) {
    let qty = parseInt($(`#row-${rowId} .qty-input`).val()) || 0;
    let priceText = $(`#row-${rowId} .price-display`).val();
    let price = parseRupiahToNumber(priceText);
    let subtotal = qty * price;
    
    $(`#row-${rowId} .subtotal-display`).val(formatRupiah(subtotal));
    $(`#row-${rowId} .subtotal-display`).data('value', subtotal);
    $(`#row-${rowId} .price-hidden`).val(price);
    $(`#row-${rowId} .subtotal-hidden`).val(subtotal);
    
    calculateGrandTotal('all');
}

function addNewRow() {
    let productOptions = '';
    productsData.forEach(function(product) {
        productOptions += `<option value="${product.id}" 
                            data-price="${product.price}" 
                            data-brand="${product.brand || '-'}"
                            data-product-code2="${product.product_code2 || ''}"
                            data-name2="${product.name2 || ''}">
                            ${product.product_code || ''} - ${product.name}
                        </option>`;
    });
    
    let newRow = `
        <div class="product-row position-relative" id="row-${rowCount}">
            <div class="row g-2 text-start">
                <div class="col-md-2">
                    <label class="form-label small">Brand</label>
                    <input type="text" class="form-control form-control-sm brand-display" id="brand-${rowCount}" readonly value="-">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Select Product</label>
                    <select name="items[${rowCount}][product_id]" class="form-select form-select-sm product-select" data-row="${rowCount}" required>
                        <option value="">-- Choose Product --</option>
                        ${productOptions}
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Qty</label>
                    <input type="number" name="items[${rowCount}][quantity]" class="form-control form-control-sm qty-input" data-row="${rowCount}" value="1" min="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Price (Rp)</label>
                    <input type="text" class="form-control form-control-sm price-display" data-row="${rowCount}" value="" required>
                    <input type="hidden" name="items[${rowCount}][unit_price]" class="price-hidden" data-row="${rowCount}" value="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Subtotal (Rp)</label>
                    <input type="text" class="form-control form-control-sm subtotal-display" id="subtotal-${rowCount}" readonly value="0" data-value="0">
                    <input type="hidden" name="items[${rowCount}][subtotal]" class="subtotal-hidden" id="subtotal-hidden-${rowCount}" value="0">
                </div>
                <div class="col-md-1 d-flex align-items-end justify-content-center">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-product w-100" data-row="${rowCount}" title="Remove Item">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    $('#products-container').append(newRow);
    initProductSelect2(`#row-${rowCount} .product-select`);
    rowCount++;
}

$(document).ready(function() {
    $('.select2-tags').select2({
        theme: 'bootstrap-5',
        tags: true,
        width: '100%'
    });
    applyAutoFocusToSelect2($('.select2-tags'));
    initProductSelect2('.product-select');

    setTimeout(function() {
        if ($('#payment_terms').val()) $('#payment_terms').trigger('change.select2');
        if ($('#delivery_time').val()) $('#delivery_time').trigger('change.select2');
    }, 150);

    $('.product-row').each(function(index) {
        $(this).attr('id', 'row-' + index);
        $(this).find('.product-select').attr('data-row', index);
        $(this).find('.qty-input').attr('data-row', index);
        $(this).find('.price-display').attr('data-row', index);
        $(this).find('.price-hidden').attr('data-row', index);
        $(this).find('.remove-product').attr('data-row', index);
        
        let initialSubtotal = $(this).find('.subtotal-display').attr('data-value') || 0;
        $(this).find('.subtotal-display').data('value', parseInt(initialSubtotal) || 0); 
    });

    calculateGrandTotal('all', true);

    $(document).on('change', '.product-select', function() {
        let rowId = $(this).data('row');
        let selected = $(this).find(':selected');
        let price = selected.data('price');
        let brand = selected.data('brand');
        
        $(`#row-${rowId} .brand-display`).val(brand ? brand : '-');
        
        if(price && price > 0) {
            $(`#row-${rowId} .price-display`).val(formatRupiah(price));
            $(`#row-${rowId} .price-hidden`).val(price);
        } else {
            $(`#row-${rowId} .price-display`).val('');
            $(`#row-${rowId} .price-hidden`).val(0);
        }
        calculateSubtotal(rowId);
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
    
    $('#add-product').click(function() {
        addNewRow();
    });
    
    $(document).on('click', '.remove-product', function() {
        let rowId = $(this).data('row');
        $(`#row-${rowId}`).remove();
        calculateGrandTotal('all');
    });

    $('#discount_percent').on('input change', function() { calculateGrandTotal('discount_percent'); });
    $('#discount_amount_display').on('keyup', function() {
        let numericValue = parseRupiahToNumber($(this).val());
        $(this).val(formatRupiah(numericValue));
        calculateGrandTotal('discount_amount');
    });

    $('#tax_percent').on('input change', function() { calculateGrandTotal('tax_percent'); });
    $('#tax_amount_display').on('keyup', function() {
        let numericValue = parseRupiahToNumber($(this).val());
        $(this).val(formatRupiah(numericValue));
        calculateGrandTotal('tax_amount');
    });

    $('#pph_percent').on('input change', function() { calculateGrandTotal('pph_percent'); });
    $('#pph_amount_display').on('keyup', function() {
        let numericValue = parseRupiahToNumber($(this).val());
        $(this).val(formatRupiah(numericValue));
        calculateGrandTotal('pph_amount');
    });

    // =====================================================
    // SKRIP SINKRONISASI HAPUS ATTACHMENT BERKAS
    // =====================================================
    $('#btn-delete-attachment').click(function() {
        if (confirm("Are you sure you want to delete this attachment? This change will be saved once you update the PO data.")) {
            $('#delete_attachment').val('1');
            $('#attachment-container').fadeOut(300, function() {
                $(this).remove();
            });
        }
    });

    $('#file-input').change(function() {
        if ($(this).val() !== '') {
            $('#delete_attachment').val('0');
        }
    });
});
</script>
@endsection