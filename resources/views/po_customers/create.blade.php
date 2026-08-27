@extends('layouts.app')
@section('title', 'Add Customer PO')
@section('content')
<style>
    body { background-color: #f1f5f9; }
    .main-card { border: none; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: #fff; overflow: hidden; }
    .card-header-custom { background: linear-gradient(135deg, #10b981, #047857); color: white; padding: 1.5rem; border: none; }
    .card-header-custom h4 { color: white; font-weight: 700; margin-bottom: 0; }
    .form-label { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700; margin-bottom: 6px; }
    .section-sub-title { font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px; color: #1e293b; font-weight: 700; margin-bottom: 1.25rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; }
    .section-sub-title i { color: #10b981; }
    .info-group-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; height: 100%; }
    .form-control, .form-select { border-color: #cbd5e1; padding: 0.5rem 0.75rem; }
    .form-control:focus, .form-select:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.15); }
    .source-option { cursor: pointer; padding: 20px; border: 2px solid #cbd5e1; border-radius: 10px; text-align: center; background-color: #fff; transition: all 0.25s ease; height: 100%; }
    .source-option:hover { border-color: #3b82f6; background-color: #f0f9ff; transform: translateY(-2px); }
    .source-option.active { border-color: #10b981; background-color: #f0fdf4; box-shadow: 0 4px 12px rgba(16,185,129,0.1); }
    .source-option i { color: #64748b; transition: color 0.25s; }
    .source-option.active i { color: #10b981; }
    .product-row { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; transition: all 0.2s; }
    .product-row:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
    .price-input, .subtotal-display, .qty-input, .price-display, .summary-input-num { text-align: right; }
    .brand-display, .code2-display, .name2-display { background-color: #f1f5f9 !important; color: #475569; font-weight: 500; }
    .table-summary th { font-size: 0.85rem; text-transform: uppercase; color: #475569; vertical-align: middle; }
    .select2-container--bootstrap-5 .select2-selection {
        min-height: calc(1.5em + 0.75rem + 2px);
        font-size: 0.875rem;
    }
    .select2-container--bootstrap-5 .select2-selection__rendered {
        line-height: 1.5;
    }
    .product-row .select2-container {
        width: 100% !important;
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h4><i class="bi bi-file-earmark-plus-fill me-2"></i>Add Customer PO</h4>
            <a href="{{ route('po-customers.index') }}" class="btn btn-light btn-sm fw-bold px-3"><i class="bi bi-arrow-left me-1"></i> Back to List</a>
        </div>
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm d-flex fade show mb-4" style="background-color:#fef2f2; color:#991b1b; border-left:4px solid #dc2626!important; border-radius:6px;">
                    <div class="me-2"><i class="bi bi-exclamation-triangle-fill fs-5"></i></div>
                    <div><strong class="d-block mb-1">Please fix the following validation errors:</strong>
                        <ul class="mb-0 ps-3 small">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
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

            <form action="{{ route('po-customers.store') }}" method="POST" id="po-form" enctype="multipart/form-data">
                @csrf
                <div class="section-sub-title d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-layers-half me-2"></i>1. Select Data Intake Source Method</span>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="source-option" id="option-quotation" data-source="quotation">
                            <i class="bi bi-file-earmark-check-fill d-block mb-2" style="font-size:28px;"></i>
                            <h5 class="fw-bold mb-1">Based on Approved Quotation</h5>
                            <p class="text-muted small mb-0">Fetch, lock, and synchronize items automatically from an approved quotation</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="source-option" id="option-manual" data-source="manual">
                            <i class="bi bi-pencil-square d-block mb-2" style="font-size:28px;"></i>
                            <h5 class="fw-bold mb-1">Direct Manual Input (Without Quotation)</h5>
                            <p class="text-muted small mb-0">Manually select products and input specific custom prices independently</p>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="source_type" id="source_type" value="">

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="info-group-box d-flex flex-column gap-3">
                            <div>
                                <div class="section-sub-title mb-3"><i class="bi bi-building me-2"></i>2. Client / Customer Relationship</div>
                                <div id="quotation-section" style="display: none;" class="mb-3">
                                    <label class="form-label fw-bold">Select Reference Quotation <span class="text-danger">*</span></label>
                                    <select name="quotation_id" id="quotation_id" class="form-select" style="width:100%;">
                                        <option value="">-- Type to search Approved Quotation --</option>
                                        @foreach($quotations as $quotation)
                                            <option value="{{ $quotation->id }}">{{ $quotation->quotation_number }} - {{ $quotation->customer->name }} (Rp {{ number_format($quotation->total,0,',','.') }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="manual-section" style="display: none;" class="mb-3">
                                    <label class="form-label">Customer Link <span class="text-danger">*</span></label>
                                    <select name="customer_id" id="customer_id" class="form-select" style="width:100%;">
                                        <option value="">-- Select Customer Destination --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" data-pic-name="{{ $customer->pic_quotation_name }}" data-pic-phone="{{ $customer->pic_quotation_phone }}" data-address="{{ $customer->shipping_address ?? $customer->address }}">{{ $customer->customer_code }} - {{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="pic-info" class="alert border-0 shadow-sm d-flex align-items-center mt-3 mb-0" style="background-color:#eff6ff; color:#1e40af; display:none!important;">
                                    <i class="bi bi-person-lines-fill me-2 fs-5"></i>
                                    <div><span class="fw-bold">Active Contact Personnel:</span> <span id="pic-name" class="fw-semibold"></span> <span class="mx-1">|</span> <i class="bi bi-telephone-fill small me-1"></i><span id="pic-phone"></span></div>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Delivery Address <span class="text-danger">*</span></label>
                                <textarea name="shipping_address" id="shipping_address" class="form-control mb-2" rows="3" placeholder="Input complete delivery destination address..." required></textarea>
                                <div class="form-check form-switch fs-7">
                                    <input class="form-check-input" type="checkbox" name="update_customer_master_address" id="update_customer_master_address" value="1">
                                    <label class="form-check-label text-muted small fw-semibold" for="update_customer_master_address">Update / Save this address back to Customer Master Data</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group-box">
                            <div class="section-sub-title"><i class="bi bi-file-earmark-text me-2"></i>3. Purchase Order (PO) & Terms Registry</div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">PO Reference Number <span class="text-danger">*</span></label>
                                    <input type="text" name="po_number" class="form-control @error('po_number') is-invalid @enderror" value="{{ old('po_number') }}" placeholder="e.g. PO/DEPT/VI/2026" required>
                                    @error('po_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">PO Date <span class="text-danger">*</span></label>
                                    <input type="date" name="po_date" id="po_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Target Delivery Date <span class="text-danger">*</span></label>
                                    <input type="date" name="delivery_date" id="delivery_date" class="form-control" value="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
                                    <select name="payment_terms" id="payment_terms" class="form-select select2-tags" required>
                                        <option value="">Select Payment Terms</option>
                                        @php
                                            $defaultPayments = ['Cash Before Delivery (CBD)','Cash on Delivery (COD)','100% in advance','30% DP after Received PO & 70% Final payment Before Delivery','40% DP after Received PO & 60% Final payment Before Delivery','50% DP after Received PO & 50% Final payment Before Delivery','60% DP after Received PO & 40% Final payment Before Delivery','70% DP after Received PO & 30% Final payment Before Delivery','14 Days after Delivery Date','30 Days after Delivery Date','45 Days after Delivery Date','60 Days after Delivery Date'];
                                            $dbPayments = $existingPaymentTerms ?? [];
                                            $mergedPayments = array_unique(array_filter(array_merge($defaultPayments, $dbPayments)));
                                        @endphp
                                        @foreach($mergedPayments as $payment)
                                            <option value="{{ $payment }}">{{ $payment }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Delivery Time</label>
                                    <select name="delivery_time" id="delivery_time" class="form-select select2-tags">
                                        <option value="">Select Delivery Time</option>
                                        @php
                                            $defaultDelivery = ['Mentioned Above','Around 1-1.5 Months after received your payment & P/O','1-2 Working days','3-5 Working days','1 Week','2 Weeks','1 Month','2 Months','3 Months','6 Months'];
                                            $dbDelivery = $existingDeliveryTimes ?? [];
                                            $mergedDelivery = array_unique(array_filter(array_merge($defaultDelivery, $dbDelivery)));
                                        @endphp
                                        @foreach($mergedDelivery as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12" id="wrapper-update-quotation" style="display:none;">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="update_quotation_master_terms" id="update_quotation_master_terms" value="1">
                                        <label class="form-check-label text-muted small fw-semibold" for="update_quotation_master_terms">Sync & update these Terms & Delivery back to reference Quotation</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-group-box mb-4">
                    <div class="section-sub-title d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-box-seam-fill me-2"></i>4. Component & Product Rincian</span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary btn-sm fw-bold px-3" data-bs-toggle="modal" data-bs-target="#quickAddProductModal"><i class="bi bi-plus-square-fill me-1"></i> New Master Product</button>
                            <button type="button" class="btn btn-success btn-sm fw-bold px-3" id="add-product"><i class="bi bi-plus-circle-fill me-1"></i> Add Product Row</button>
                        </div>
                    </div>
                    <div id="products-container" class="d-flex flex-column gap-3 mb-3"></div>
                </div>

                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="info-group-box">
                            <div class="section-sub-title"><i class="bi bi-chat-right-quote-fill me-2"></i>5. Additional Metadata</div>
                            <div class="mb-3"><label class="form-label">Internal Remarks / Terms Notes</label><textarea name="notes" class="form-control" rows="4"></textarea></div>
                            <div class="mb-0"><label class="form-label">Document Attachment (PDF/Image)</label><input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf"><small class="text-muted d-block mt-1">Maximum size: 5MB.</small></div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="border rounded-3 overflow-hidden shadow-sm">
                            <table class="table table-bordered table-summary m-0 bg-white align-middle">
                                <tr><th width="45%" class="ps-3">Subtotal</th><td colspan="2" class="text-end pe-3 fw-bold text-dark" id="subtotal-text">Rp 0</td><input type="hidden" name="subtotal" id="subtotal-value" value="0"></tr>
                                <tr><th class="ps-3">Discount</th><td width="25%" class="px-2"><div class="input-group input-group-sm"><input type="number" name="discount_percent" id="discount_percent" class="form-control text-end summary-input-num" step="0.01" min="0" max="100" value="0"><span class="input-group-text">%</span></div></td><td width="30%" class="pe-2"><div class="input-group input-group-sm"><span class="input-group-text">Rp</span><input type="text" id="discount_amount_display" class="form-control text-end summary-input-num" value="0"><input type="hidden" name="discount_amount" id="discount-value" value="0"></div></td></tr>
                                <tr style="background-color:#f8fafc;"><th class="ps-3 fw-semibold text-secondary">DPP Nilai Lain</th><td colspan="2" class="text-end pe-3 fw-bold text-secondary"><span id="subtotal-after-discount-text">Rp 0</span><input type="hidden" name="dpp_amount" id="dpp-value" value="0"></td></tr>
                                <tr><th class="ps-3">PPN</th><td class="px-2"><div class="input-group input-group-sm"><input type="number" name="tax_percent" id="tax_percent" class="form-control text-end summary-input-num" step="0.01" min="0" max="100" value="11"><span class="input-group-text">%</span></div></td><td class="pe-2"><div class="input-group input-group-sm"><span class="input-group-text">Rp</span><input type="text" id="tax_amount_display" class="form-control text-end summary-input-num" value="0"><input type="hidden" name="tax_amount" id="tax-value" value="0"></div></td></tr>
                                <tr><th class="ps-3">PPh 23</th><td class="px-2"><div class="input-group input-group-sm"><input type="number" name="pph_percent" id="pph_percent" class="form-control text-end summary-input-num" step="0.01" min="0" max="100" value="0"><span class="input-group-text">%</span></div></td><td class="pe-2"><div class="input-group input-group-sm"><span class="input-group-text">Rp</span><input type="text" id="pph_amount_display" class="form-control text-end summary-input-num" value="0"><input type="hidden" name="pph_amount" id="pph-value" value="0"></div></td></tr>
                                <tr style="background-color:#f8fafc; border-top:2px solid #cbd5e1;"><th class="ps-3 fs-6 text-dark"><strong>Grand Total Invoice</strong></th><td colspan="2" class="text-end pe-3 fs-5 text-success fw-bold"><strong><span id="grand-total-text">Rp 0</span></strong><input type="hidden" name="total" id="grand-total-value" value="0"></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-12 mt-4 pt-3 border-top d-flex gap-2">
                    <button type="submit" class="btn btn-success px-4 py-2 fw-bold text-white" style="background: linear-gradient(135deg, #10b981, #047857); border:none;"><i class="bi bi-save-fill me-1"></i> Save Customer PO</button>
                    <a href="{{ route('po-customers.index') }}" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL QUICK ADD PRODUCT --}}
<div class="modal fade" id="quickAddProductModal" tabindex="-1" aria-labelledby="quickAddProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="quickAddProductModalLabel"><i class="bi bi-box-seam me-2"></i>Quick Add Master Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="quick-product-form">
                @csrf
                <div class="modal-body text-start">
                    <div class="mb-3"><label class="form-label small">Product Name <span class="text-danger">*</span></label><input type="text" name="name" id="modal_product_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small">Brand</label><input type="text" name="brand" id="modal_product_brand" class="form-control"></div>
                    <div class="mb-3"><label class="form-label small">Standard Price (Rp) <span class="text-danger">*</span></label><input type="text" id="modal_product_price_display" class="form-control text-end" required><input type="hidden" name="price" id="modal_product_price" value="0"></div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3"><label class="form-label small">Alt Product Code</label><input type="text" name="product_code2" id="modal_product_code2" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label small">Alt Description Name</label><input type="text" name="name2" id="modal_product_name2" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold" id="btn-save-quick-product"><i class="bi bi-save-fill me-1"></i> Save Product</button>
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
let rowCount = 0;
let productsData = @json($products);
let activeTriggers = { discount: 'percent', tax: 'percent', pph: 'percent' };

function formatRupiah(angka) {
    if(!angka || isNaN(angka)) return '0';
    return new Intl.NumberFormat('id-ID').format(Math.round(angka));
}
function parseRupiahToNumber(str) {
    if(!str) return 0;
    return parseFloat(str.toString().replace(/\./g, '').replace(/,/g, '.')) || 0;
}

function calculateGrandTotal() {
    let subtotal = 0;
    $('.subtotal-display').each(function() { let val = $(this).data('value') || 0; subtotal += parseInt(val); });
    $('#subtotal-text').text('Rp ' + formatRupiah(subtotal));
    $('#subtotal-value').val(subtotal);
    let discountPercent = parseFloat($('#discount_percent').val()) || 0;
    let discountAmount = parseRupiahToNumber($('#discount_amount_display').val());
    if (subtotal > 0) {
        if (activeTriggers.discount === 'percent') {
            discountAmount = subtotal * (discountPercent / 100);
            $('#discount_amount_display').val(discountAmount > 0 ? formatRupiah(discountAmount) : '0');
        } else {
            discountPercent = (discountAmount / subtotal) * 100;
            $('#discount_percent').val(discountPercent > 0 ? discountPercent.toFixed(2) : 0);
        }
    }
    $('#discount-value').val(Math.round(discountAmount));
    let dppAmount = Math.max(0, subtotal - discountAmount);
    $('#subtotal-after-discount-text').text('Rp ' + formatRupiah(dppAmount));
    $('#dpp-value').val(Math.round(dppAmount));
    let taxPercent = parseFloat($('#tax_percent').val()) || 0;
    let taxAmount = parseRupiahToNumber($('#tax_amount_display').val());
    if (dppAmount > 0) {
        if (activeTriggers.tax === 'percent') {
            taxAmount = dppAmount * (taxPercent / 100);
            $('#tax_amount_display').val(taxAmount > 0 ? formatRupiah(taxAmount) : '0');
        } else {
            taxPercent = (taxAmount / dppAmount) * 100;
            $('#tax_percent').val(taxPercent > 0 ? taxPercent.toFixed(2) : 0);
        }
    }
    $('#tax-value').val(Math.round(taxAmount));
    let pphPercent = parseFloat($('#pph_percent').val()) || 0;
    let pphAmount = parseRupiahToNumber($('#pph_amount_display').val());
    if (dppAmount > 0) {
        if (activeTriggers.pph === 'percent') {
            pphAmount = dppAmount * (pphPercent / 100);
            $('#pph_amount_display').val(pphAmount > 0 ? formatRupiah(pphAmount) : '0');
        } else {
            pphPercent = (pphAmount / dppAmount) * 100;
            $('#pph_percent').val(pphPercent > 0 ? pphPercent.toFixed(2) : 0);
        }
    }
    $('#pph-value').val(Math.round(pphAmount));
    let grandTotal = Math.max(0, dppAmount + taxAmount - pphAmount);
    $('#grand-total-text').text('Rp ' + formatRupiah(grandTotal));
    $('#grand-total-value').val(Math.round(grandTotal));
}

function calculateSubtotal(rowId) {
    let qty = parseInt($(`#row-${rowId} .qty-input`).val()) || 0;
    let price = parseRupiahToNumber($(`#row-${rowId} .price-display`).val());
    let subtotal = qty * price;
    $(`#row-${rowId} .subtotal-display`).val(formatRupiah(subtotal));
    $(`#row-${rowId} .subtotal-display`).data('value', subtotal);
    $(`#row-${rowId} .price-hidden`).val(price);
    $(`#row-${rowId} #subtotal-hidden-${rowId}`).val(subtotal);
    calculateGrandTotal();
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

function addNewRow(item = null) {
    let productOptions = '';
    productsData.forEach(function(product) {
        let selected = (item && item.product_id == product.id) ? 'selected' : '';
        productOptions += `<option value="${product.id}" data-price="${product.price}" data-brand="${product.brand || '-'}" data-product-code2="${product.product_code2 || ''}" data-name2="${product.name2 || ''}" ${selected}>${product.product_code || ''} - ${product.name}</option>`;
    });
    
    let qtyValue = (item && item.quantity) ? item.quantity : 1;
    let priceValue = (item && item.unit_price) ? formatRupiah(item.unit_price) : '';
    let priceNumeric = (item && item.unit_price) ? item.unit_price : 0;
    let subtotalValue = (item && item.subtotal) ? formatRupiah(item.subtotal) : '';
    let subtotalNumeric = (item && item.subtotal) ? item.subtotal : 0;
    let brandValue = (item && item.brand) ? item.brand : '';
    let productCode2Value = (item && item.product_code2) ? item.product_code2 : '';
    let name2Value = (item && item.name2) ? item.name2 : '';
    
    let newRow = `<div class="product-row position-relative" id="row-${rowCount}">
        <div class="row g-2">
            <div class="col-md-2"><label class="form-label small">Brand</label><input type="text" class="form-control form-control-sm brand-display" id="brand-${rowCount}" readonly value="${brandValue}"></div>
            <div class="col-md-4"><label class="form-label small">Select Product</label><select name="items[${rowCount}][product_id]" class="form-select form-select-sm product-select" data-row="${rowCount}" required><option value="">-- Choose Product --</option>${productOptions}</select></div>
            <div class="col-md-1"><label class="form-label small">Qty</label><input type="number" name="items[${rowCount}][quantity]" class="form-control form-control-sm qty-input" data-row="${rowCount}" value="${qtyValue}" min="1" required></div>
            <div class="col-md-2"><label class="form-label small">Price (Rp)</label><input type="text" class="form-control form-control-sm price-display" data-row="${rowCount}" value="${priceValue}" required><input type="hidden" name="items[${rowCount}][unit_price]" class="price-hidden" data-row="${rowCount}" value="${priceNumeric}"></div>
            <div class="col-md-2"><label class="form-label small">Subtotal (Rp)</label><input type="text" class="form-control form-control-sm subtotal-display" id="subtotal-${rowCount}" readonly value="${subtotalValue}" data-value="${subtotalNumeric}"><input type="hidden" name="items[${rowCount}][subtotal]" id="subtotal-hidden-${rowCount}" value="${subtotalNumeric}"></div>
            <div class="col-md-1 d-flex align-items-end justify-content-center"><button type="button" class="btn btn-outline-danger btn-sm remove-product w-100" data-row="${rowCount}" title="Remove Item"><i class="bi bi-trash"></i></button></div>
        </div>
        <div class="row g-2 mt-2 pt-2 border-top border-dashed">
            <div class="col-md-3"><label class="form-label small text-muted">Alt Product Code</label><input type="text" class="form-control form-control-sm code2-display" id="code2-${rowCount}" readonly value="${productCode2Value}"></div>
            <div class="col-md-9"><label class="form-label small text-muted">Alt Product Description Name</label><input type="text" class="form-control form-control-sm name2-display" id="name2-${rowCount}" readonly value="${name2Value}"></div>
        </div>
    </div>`;
    
    $('#products-container').append(newRow);
    initProductSelect2(`#row-${rowCount} .product-select`);
    rowCount++;
}

function initSelect2Quotation() {
    if ($('#quotation_id').data('select2')) $('#quotation_id').select2('destroy');
    var $quotation = $('#quotation_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Type to search Approved Quotation --',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#quotation-section')
    });
    applyAutoFocusToSelect2($quotation);
}

function initSelect2Customer() {
    if ($('#customer_id').data('select2')) $('#customer_id').select2('destroy');
    var $customer = $('#customer_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Select Customer Destination --',
        allowClear: true,
        width: '100%'
    });
    applyAutoFocusToSelect2($customer);
}

function resetPaymentAndDelivery() {
    $('#payment_terms').val('').trigger('change');
    $('#delivery_time').val('').trigger('change');
}

$(document).ready(function() {
    // Inisialisasi Select2 untuk payment_terms dan delivery_time
    var $selectTags = $('.select2-tags').select2({ theme: 'bootstrap-5', tags: true, width: '100%' });
    applyAutoFocusToSelect2($selectTags);
    
    $('#modal_product_price_display').on('keyup input', function() {
        let num = parseRupiahToNumber($(this).val());
        if(num > 0) { $(this).val(formatRupiah(num)); $('#modal_product_price').val(num); }
        else { $(this).val(''); $('#modal_product_price').val(0); }
    });
    
    $('#quick-product-form').on('submit', function(e) {
        e.preventDefault();
        let btn = $('#btn-save-quick-product');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');
        $.ajax({
            url: '{{ route("products.store") }}', method: 'POST', data: $(this).serialize(),
            success: function(resp) {
                let p = resp.product;
                if(p) {
                    productsData.push({ id: p.id, price: p.price, product_code: p.product_code, name: p.name, brand: p.brand, product_code2: p.product_code2, name2: p.name2 });
                    let opt = new Option((p.product_code ? p.product_code+' - ' : '')+p.name, p.id);
                    $(opt).attr('data-price', p.price).attr('data-brand', p.brand||'-').attr('data-product-code2', p.product_code2||'').attr('data-name2', p.name2||'');
                    $('.product-select').append(opt);
                    alert('Product "'+p.name+'" successfully added!');
                    $('#quick-product-form')[0].reset();
                    $('#modal_product_price').val(0);
                    $('#quickAddProductModal').modal('hide');
                    initProductSelect2('.product-select');
                }
            },
            error: function() { alert('Failed to save product.'); },
            complete: function() { btn.prop('disabled', false).html('<i class="bi bi-save-fill me-1"></i> Save Product'); }
        });
    });
    
    // Mode Quotation
    $('#option-quotation').click(function() {
        $('.source-option').removeClass('active'); $(this).addClass('active');
        $('#source_type').val('quotation');
        $('#quotation-section').show(); $('#manual-section').hide();
        $('#wrapper-update-quotation').show();
        $('#products-container').empty();
        // Tombol Add Product TETAP MUNCUL dan bisa digunakan untuk menambah baris kosong
        $('#add-product').show();   // <-- PERUBAHAN: tombol add product selalu tampil
        resetPaymentAndDelivery();
        rowCount = 0;
        initSelect2Quotation();
        // Tidak perlu menambah baris otomatis, user bisa klik tombol Add Product
        calculateGrandTotal();
    });
    
    // Mode Manual
    $('#option-manual').click(function() {
        $('.source-option').removeClass('active'); $(this).addClass('active');
        $('#source_type').val('manual');
        $('#quotation-section').hide(); $('#manual-section').show();
        $('#wrapper-update-quotation').hide();
        $('#products-container').empty();
        $('#add-product').show();   // <-- sudah show
        resetPaymentAndDelivery();
        rowCount = 0;
        initSelect2Customer();
        addNewRow(); // tambah satu baris default
        calculateGrandTotal();
        if ($('#customer_id').val()) {
            $('#customer_id').trigger('change');
        } else {
            $('#shipping_address').val('');
        }
    });
    
    // Event untuk product select (manual & quotation)
    $(document).on('change', '.product-select', function() {
        let rowId = $(this).data('row'), sel = $(this).find(':selected');
        let price = sel.data('price'), brand = sel.data('brand'), code2 = sel.data('product-code2'), name2 = sel.data('name2');
        $(`#row-${rowId} .brand-display`).val(brand || '-');
        $(`#row-${rowId} .code2-display`).val(code2 || '-');
        $(`#row-${rowId} .name2-display`).val(name2 || '-');
        if(price > 0) { $(`#row-${rowId} .price-display`).val(formatRupiah(price)); $(`#row-${rowId} .price-hidden`).val(price); }
        else { $(`#row-${rowId} .price-display`).val(''); $(`#row-${rowId} .price-hidden`).val(0); }
        calculateSubtotal(rowId);
    });
    
    $(document).on('keyup change', '.price-display', function() {
        let rowId = $(this).data('row'), val = $(this).val(), num = parseRupiahToNumber(val);
        if(num > 0) { $(this).val(formatRupiah(num)); $(`#row-${rowId} .price-hidden`).val(num); }
        else { $(this).val(''); $(`#row-${rowId} .price-hidden`).val(0); }
        calculateSubtotal(rowId);
    });
    
    $(document).on('keyup change', '.qty-input', function() { calculateSubtotal($(this).data('row')); });
    
    // Customer change (manual mode)
    $('#customer_id').on('change', function() {
        if ($(this).data('locked')) return;
        let sel = $(this).find(':selected');
        let picName = sel.data('pic-name'), picPhone = sel.data('pic-phone'), addr = sel.data('address');
        if(addr) $('#shipping_address').val(addr);
        else $('#shipping_address').val('');
        if(picName) { $('#pic-name').text(picName); $('#pic-phone').text(picPhone||'-'); $('#pic-info').css('display','flex!important').show(); }
        else $('#pic-info').hide();
    });
    
    // Quotation change - AMBIL DATA QUOTATION LANGSUNG TANPA PERHITUNGAN ULANG
    $('#quotation_id').on('change', function() {
        let qid = $(this).val();
        if (qid) {
            $.ajax({
                url: @json(route('po-customers.get-quotation', ['id' => '__ID__'])).replace('__ID__', encodeURIComponent(qid)),
                method: 'GET',
                success: function(resp) {
                    // 1. Customer & Alamat
                    $('#customer_id').data('locked', true);
                    if (resp.customer_id) $('#customer_id').val(resp.customer_id).trigger('change');
                    if (resp.shipping_address) $('#shipping_address').val(resp.shipping_address);
                    else $('#shipping_address').val('');
                    $('#customer_id').data('locked', false);

                    // 2. Payment Terms & Delivery Time
                    if (resp.payment_terms) {
                        if ($('#payment_terms option[value="' + resp.payment_terms + '"]').length === 0)
                            $('#payment_terms').append(new Option(resp.payment_terms, resp.payment_terms));
                        $('#payment_terms').val(resp.payment_terms).trigger('change');
                    } else $('#payment_terms').val('').trigger('change');

                    if (resp.delivery_time) {
                        if ($('#delivery_time option[value="' + resp.delivery_time + '"]').length === 0)
                            $('#delivery_time').append(new Option(resp.delivery_time, resp.delivery_time));
                        $('#delivery_time').val(resp.delivery_time).trigger('change');
                    } else $('#delivery_time').val('').trigger('change');

                    // 3. Kosongkan container & tambahkan item dari quotation
                    $('#products-container').empty();
                    rowCount = 0;
                    if (resp.items && resp.items.length) {
                        resp.items.forEach(item => addNewRow(item));
                    }

                    // 4. HITUNG SUBTOTAL TOTAL (dari item yang sudah ditambahkan)
                    let subtotalTotal = 0;
                    $('.subtotal-display').each(function() {
                        let val = $(this).data('value') || 0;
                        subtotalTotal += parseInt(val);
                    });

                    // 5. ISI SEMUA FIELD SUMMARY MANUAL SESUAI DATA QUOTATION
                    $('#subtotal-text').text('Rp ' + formatRupiah(subtotalTotal));
                    $('#subtotal-value').val(subtotalTotal);

                    let discountPercent = resp.discount_percent || 0;
                    let discountAmount = resp.discount_amount || 0;
                    $('#discount_percent').val(discountPercent);
                    $('#discount_amount_display').val(formatRupiah(discountAmount));
                    $('#discount-value').val(discountAmount);

                    let dpp = subtotalTotal - discountAmount;
                    $('#subtotal-after-discount-text').text('Rp ' + formatRupiah(dpp));
                    $('#dpp-value').val(dpp);

                    let taxPercent = resp.tax_percent || 0;
                    let taxAmount = resp.tax_amount || 0;
                    $('#tax_percent').val(taxPercent);
                    $('#tax_amount_display').val(formatRupiah(taxAmount));
                    $('#tax-value').val(taxAmount);

                    let pphPercent = resp.pph23_percent || 0;
                    let pphAmount = resp.pph23_amount || 0;
                    $('#pph_percent').val(pphPercent);
                    $('#pph_amount_display').val(formatRupiah(pphAmount));
                    $('#pph-value').val(pphAmount);

                    let grandTotal = resp.total || (dpp + taxAmount - pphAmount);
                    $('#grand-total-text').text('Rp ' + formatRupiah(grandTotal));
                    $('#grand-total-value').val(grandTotal);

                    // 6. Tidak ada panggilan calculateGrandTotal() !!!
                    initProductSelect2('.product-select');
                },
                error: function() {
                    alert('Gagal mengambil data quotation.');
                }
            });
        } else {
            $('#products-container').empty();
            $('#shipping_address').val('');
            resetPaymentAndDelivery();
            rowCount = 0;
            // Reset summary ke 0
            $('#subtotal-text').text('Rp 0');
            $('#subtotal-value').val(0);
            $('#discount_percent').val(0);
            $('#discount_amount_display').val('0');
            $('#discount-value').val(0);
            $('#subtotal-after-discount-text').text('Rp 0');
            $('#dpp-value').val(0);
            $('#tax_percent').val(11);
            $('#tax_amount_display').val('0');
            $('#tax-value').val(0);
            $('#pph_percent').val(0);
            $('#pph_amount_display').val('0');
            $('#pph-value').val(0);
            $('#grand-total-text').text('Rp 0');
            $('#grand-total-value').val(0);
        }
    });
    
    // Add product button (untuk menambah baris baru, baik mode manual maupun quotation)
    $('#add-product').click(function() {
        addNewRow();  // tambah baris kosong
    });
    
    $(document).on('click', '.remove-product', function() { $(`#row-${$(this).data('row')}`).remove(); calculateGrandTotal(); });
    
    $('#discount_percent').on('input change', function() { activeTriggers.discount = 'percent'; calculateGrandTotal(); });
    $('#discount_amount_display').on('keyup input', function() { activeTriggers.discount = 'amount'; let val = parseRupiahToNumber($(this).val()); $(this).val(val>0?formatRupiah(val):'0'); calculateGrandTotal(); });
    $('#tax_percent').on('input change', function() { activeTriggers.tax = 'percent'; calculateGrandTotal(); });
    $('#tax_amount_display').on('keyup input', function() { activeTriggers.tax = 'amount'; let val = parseRupiahToNumber($(this).val()); $(this).val(val>0?formatRupiah(val):'0'); calculateGrandTotal(); });
    $('#pph_percent').on('input change', function() { activeTriggers.pph = 'percent'; calculateGrandTotal(); });
    $('#pph_amount_display').on('keyup input', function() { activeTriggers.pph = 'amount'; let val = parseRupiahToNumber($(this).val()); $(this).val(val>0?formatRupiah(val):'0'); calculateGrandTotal(); });
});
</script>
@endsection
