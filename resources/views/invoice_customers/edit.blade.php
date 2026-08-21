@extends('layouts.app')
@section('title', 'Edit Sales Invoice')
@section('content')

<style>
    .price-input,
    .qty-input {
        text-align: right;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .section-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 18px;
    }
    .section-title {
        font-size: 0.9rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #1e293b;
        margin-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 6px;
    }
    .section-title i {
        color: #0284c7;
        margin-right: 6px;
    }
    .summary-box {
        background: #1e293b;
        border-radius: 10px;
        padding: 18px 16px;
        color: #cbd5e1;
    }
    .summary-box .row-item {
        display: flex;
        justify-content: space-between;
        padding: 4px 0;
        border-bottom: 1px solid #334155;
    }
    .summary-box .row-item:last-child {
        border-bottom: none;
    }
    .summary-box .grand-total {
        font-size: 1.15rem;
        font-weight: 700;
        color: #38bdf8;
        border-top: 2px solid #475569;
        padding-top: 8px;
        margin-top: 4px;
    }
    .summary-box .label {
        font-weight: 500;
    }
    .summary-box .value {
        font-weight: 600;
        color: #e2e8f0;
    }
    .summary-box .grand-total .value {
        color: #38bdf8;
    }
    .product-card {
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 15px;
        background: #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .readonly-bg,
    .brand-display,
    .subtotal-display,
    .code2-display,
    .name2-display {
        background: #f8f9fa !important;
    }
    .btn-remove {
        width: 100%;
    }
    .card-header-custom {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border-radius: 10px 10px 0 0;
    }
    .page-title {
        font-size: 1.4rem;
        font-weight: 600;
    }
    .select2-container .select2-selection--single {
        min-height: 40px !important;
        border-color: #cbd5e1 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important;
        padding-left: 12px !important;
    }
</style>

<div class="card shadow-sm border-0">
    <div class="card-header card-header-custom py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="page-title">
                <i class="bi bi-pencil-square me-2"></i> Edit Sales Invoice
            </div>
            <a href="{{ route('invoice-customers.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body p-4">

        {{-- ALERT --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" style="border-left: 4px solid #dc2626 !important;">
                <div class="d-flex">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div>
                        <strong>Terjadi kesalahan validasi:</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" style="border-left: 4px solid #dc2626 !important;">
                <div class="d-flex">
                    <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('invoice-customers.update', $invoice->id) }}"
              method="POST"
              enctype="multipart/form-data"
              id="invoiceForm">
            @csrf
            @method('PUT')

            {{-- ========================================================= --}}
            {{-- BAGIAN 1: INVOICE HEADER & PO INFO --}}
            {{-- ========================================================= --}}
            <div class="section-card">
                <div class="section-title"><i class="bi bi-file-earmark-text-fill"></i> Invoice Header</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Invoice #</label>
                        <input type="text" name="invoice_number" class="form-control readonly-bg"
                            value="{{ old('invoice_number', $invoice->invoice_number) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer</label>
                        <input type="text" class="form-control readonly-bg"
                            value="{{ $invoice->poCustomer->customer->name ?? '' }}" readonly>
                        <input type="hidden" name="customer_id" value="{{ $invoice->poCustomer->customer_id ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PO Customer #</label>
                        <input type="text" class="form-control readonly-bg"
                            value="{{ $invoice->poCustomer->po_number ?? '-' }} - {{ $invoice->poCustomer->customer->name ?? '-' }}"
                            readonly>
                        <input type="hidden" name="po_customer_id" value="{{ $invoice->po_customer_id }}">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control"
                            value="{{ $invoice->invoice_date instanceof \Carbon\Carbon ? $invoice->invoice_date->format('Y-m-d') : date('Y-m-d', strtotime($invoice->invoice_date)) }}"
                            required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control"
                            value="{{ $invoice->due_date ? ($invoice->due_date instanceof \Carbon\Carbon ? $invoice->due_date->format('Y-m-d') : date('Y-m-d', strtotime($invoice->due_date))) : '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Invoice Type</label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="proforma" {{ $invoice->type == 'proforma' ? 'selected' : '' }}>Proforma Invoice</option>
                            <option value="sales" {{ $invoice->type == 'sales' ? 'selected' : '' }}>Sales Invoice</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- BAGIAN 2: PAYMENT & DELIVERY --}}
            {{-- ========================================================= --}}
            <div class="section-card">
                <div class="section-title"><i class="bi bi-credit-card-fill"></i> Payment & Delivery</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
                        <select name="payment_terms" id="payment_terms" class="form-select select2-tags" required>
                            <option value="">Select Payment Terms</option>
                            @php
                                $defaultPayments = [
                                    'Cash Before Delivery (CBD)', 'Cash on Delivery (COD)', '100% in advance',
                                    '25% DP after Received PO & 75% Final payment Before Delivery',
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
                                <option value="{{ $payment }}" {{ old('payment_terms', $invoice->payment_terms) == $payment ? 'selected' : '' }}>
                                    {{ $payment }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Delivery Time</label>
                        <select name="delivery_time" id="delivery_time" class="form-select select2-tags">
                            <option value="">Select Delivery Time</option>
                            @php
                                $defaultOptions = [
                                    'Mentioned Above', '1-1.5 Months',
                                    '2-3 weeks', '1-2 Working days',
                                    '3-5 Working days', '1 Week', '2 Weeks', '1 Month', '2 Months', '3 Months', '6 Months'
                                ];
                                $dbOptions = $existingDeliveryTimes ?? [];
                                $mergedOptions = array_unique(array_filter(array_merge($defaultOptions, $dbOptions)));
                            @endphp
                            @foreach($mergedOptions as $option)
                                <option value="{{ $option }}" {{ old('delivery_time', $invoice->delivery_time ?? '') == $option ? 'selected' : '' }}>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- BAGIAN 3: TAX INVOICE REFERENCE --}}
            {{-- ========================================================= --}}
            <div class="section-card">
                <div class="section-title"><i class="bi bi-file-earmark-text-fill"></i> Tax Invoice Reference</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tax Invoice Number</label>
                        <input type="text" name="tax_invoice_number" class="form-control" value="{{ old('tax_invoice_number', $invoice->tax_invoice_number) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tax Invoice Attachment</label>
                        <input type="file" name="tax_invoice_attachment" id="tax_invoice_attachment"
                               class="form-control @error('tax_invoice_attachment') is-invalid @enderror"
                               accept=".jpg,.jpeg,.png,.pdf">
                        <div class="form-text text-muted small">Format: JPG, JPEG, PNG, PDF (Max: 5MB)</div>
                        @error('tax_invoice_attachment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        @if(!empty($invoice->tax_invoice_attachment))
                            <div id="old-attachment-wrapper" class="mt-2 d-flex gap-2 align-items-center flex-wrap">
                                <a href="{{ route('tax-invoice.view', $invoice->id) }}" target="_blank" class="btn btn-sm btn-info text-white">
                                    <i class="bi bi-eye-fill"></i> View File
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" id="btn-delete-attachment">
                                    <i class="bi bi-trash-fill"></i> Delete Current File
                                </button>
                                <input type="hidden" name="delete_attachment" id="delete_attachment_input" value="0">
                            </div>
                            <div id="deleted-badge-msg" class="mt-2 text-danger small fw-bold d-none">
                                <i class="bi bi-info-circle"></i> File will be deleted upon save.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <hr>

            {{-- ========================================================= --}}
            {{-- BAGIAN 4: PPH23 CHECKBOX --}}
            {{-- ========================================================= --}}
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="has_pph23" {{ $invoice->pph23_percent > 0 ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="has_pph23">With PPH23</label>
            </div>

            {{-- ========================================================= --}}
            {{-- BAGIAN 5: PRODUCT DETAILS --}}
            {{-- ========================================================= --}}
            <div class="section-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0" style="border-bottom: none; padding-bottom: 0;">
                        <i class="bi bi-box-fill"></i> Product Details
                    </div>
                    <button type="button" class="btn btn-success btn-sm" id="add-product">
                        <i class="bi bi-plus-circle"></i> Add Product
                    </button>
                </div>

                <div id="products-container">
                    @foreach($invoice->details as $index => $detail)
                    <div class="product-card product-row" data-row-id="{{ $index }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Product</label>
                                <input type="text" class="form-control readonly-bg"
                                    value="{{ $detail->product->product_code ?? '' }} - {{ $detail->product->name ?? '' }}"
                                    readonly>
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $detail->product_id }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Brand</label>
                                <input type="text" class="form-control brand-display"
                                    value="{{ $detail->product->brand ?? '-' }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Qty</label>
                                <input type="number" name="items[{{ $index }}][quantity]"
                                    class="form-control qty-input" data-row-id="{{ $index }}"
                                    value="{{ $detail->quantity }}" min="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Price</label>
                                <input type="text" class="form-control price-display" data-row-id="{{ $index }}"
                                    value="{{ number_format($detail->unit_price, 0, ',', '.') }}">
                                <input type="hidden" name="items[{{ $index }}][unit_price]"
                                    class="price-hidden" value="{{ $detail->unit_price }}">
                                <input type="hidden" name="items[{{ $index }}][unit_price_numeric]"
                                    class="price-numeric-hidden" value="{{ $detail->unit_price }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Subtotal</label>
                                <input type="text" class="form-control subtotal-display"
                                    value="{{ number_format($detail->subtotal, 0, ',', '.') }}" readonly>
                                <input type="hidden" name="items[{{ $index }}][subtotal]"
                                    class="subtotal-hidden" value="{{ $detail->subtotal }}">
                                <button type="button" class="btn btn-danger btn-sm mt-2 remove-product btn-remove">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Product Code 2</label>
                                <input type="text" class="form-control code2-display"
                                    value="{{ $detail->product->product_code2 ?? '-' }}" readonly>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label fw-semibold">Product Name 2</label>
                                <input type="text" class="form-control name2-display"
                                    value="{{ $detail->product->name2 ?? '-' }}" readonly>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- BAGIAN 6: SUMMARY --}}
            {{-- ========================================================= --}}
            <div class="section-card">
                <div class="section-title"><i class="bi bi-calculator-fill"></i> Summary</div>
                <div class="row justify-content-end">
                    <div class="col-md-6">
                        <div class="summary-box">
                            {{-- Subtotal --}}
                            <div class="row-item">
                                <span class="label">Subtotal</span>
                                <span class="value" id="subtotal-text">Rp 0</span>
                                <input type="hidden" name="subtotal" id="subtotal-value">
                            </div>
                            {{-- Discount --}}
                            <div class="row-item">
                                <span class="label">Discount (%)</span>
                                <span class="value">
                                    <input type="number" name="discount_percent" id="discount_percent"
                                        class="form-control form-control-sm text-end"
                                        style="background: #334155; border: 1px solid #475569; color: #ffc107; width: 80px; display: inline-block;"
                                        value="{{ old('discount_percent', $invoice->discount_percent ?? 0) }}"
                                        min="0" max="100" step="0.5">
                                </span>
                            </div>
                            <div class="row-item">
                                <span class="label">Discount Amount</span>
                                <span class="value" id="discount-text">Rp 0</span>
                                <input type="hidden" name="discount_amount" id="discount-value">
                            </div>
                            {{-- Payment (DP) --}}
                            <div class="row-item">
                                <span class="label">Payment (%)</span>
                                <span class="value">
                                    <input type="number" name="dp_percent" id="dp_percent"
                                        class="form-control form-control-sm text-end"
                                        style="background: #334155; border: 1px solid #475569; color: #fff; width: 80px; display: inline-block;"
                                        value="{{ old('dp_percent', $invoice->dp_percent ?? 100) }}"
                                        min="0" max="100" step="0.5">
                                </span>
                            </div>
                            <div class="row-item">
                                <span class="label">Payment Amount</span>
                                <span class="value" id="dp-text">Rp 0</span>
                                <input type="hidden" name="dp_amount" id="dp-value">
                            </div>
                            {{-- DPP Lain --}}
                            <div class="row-item">
                                <span class="label">DPP Lain (11/12 × DP)</span>
                                <span class="value" id="dpplain-text">Rp 0</span>
                            </div>
                            {{-- PPN --}}
                            <div class="row-item">
                                <span class="label">PPN (%)</span>
                                <span class="value">
                                    <input type="number" name="tax_percent" id="tax_percent"
                                        class="form-control form-control-sm text-end"
                                        style="background: #334155; border: 1px solid #475569; color: #0dcaf0; width: 80px; display: inline-block;"
                                        value="{{ old('tax_percent', $invoice->tax_percent ?? 11) }}"
                                        min="0" max="100" step="0.5">
                                </span>
                            </div>
                            <div class="row-item">
                                <span class="label">PPN Amount</span>
                                <span class="value" id="tax-text">Rp 0</span>
                                <input type="hidden" name="tax_amount" id="tax-value">
                            </div>
                            {{-- PPH23 --}}
                            <div class="row-item" id="pph23_row" style="{{ $invoice->pph23_percent > 0 ? '' : 'display: none;' }}">
                                <span class="label">PPH23 (%)</span>
                                <span class="value">
                                    <input type="number" name="pph23_percent" id="pph23_percent"
                                        class="form-control form-control-sm text-end"
                                        style="background: #334155; border: 1px solid #475569; color: #dc3545; width: 80px; display: inline-block;"
                                        value="{{ old('pph23_percent', $invoice->pph23_percent ?? 0) }}"
                                        min="0" max="100" step="0.5">
                                </span>
                            </div>
                            <div class="row-item" id="pph23_amount_row" style="{{ $invoice->pph23_percent > 0 ? '' : 'display: none;' }}">
                                <span class="label">PPH23 Amount</span>
                                <span class="value" id="pph23-text">Rp 0</span>
                                <input type="hidden" name="pph23_amount" id="pph23-value">
                            </div>
                            {{-- Grand Total --}}
                            <div class="row-item grand-total">
                                <span class="label">Grand Total</span>
                                <span class="value" id="grand-total-text">Rp 0</span>
                                <input type="hidden" name="total" id="grand-total-value">
                            </div>
                            {{-- Remaining --}}
                            <div class="row-item">
                                <span class="label">Remaining Amount</span>
                                <span class="value" id="remaining-text">Rp 0</span>
                                <input type="hidden" name="remaining_amount" id="remaining-value">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- BAGIAN 7: REMARKS & STATUS --}}
            {{-- ========================================================= --}}
            <div class="section-card">
                <div class="section-title"><i class="bi bi-chat-left-text-fill"></i> Additional Info</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $invoice->notes) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="proforma" {{ $invoice->status == 'proforma' ? 'selected' : '' }}>Proforma</option>
                            <option value="partial" {{ $invoice->status == 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="completed" {{ $invoice->status == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $invoice->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select" required>
                            <option value="sent" {{ $invoice->payment_status == 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="paid" {{ $invoice->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- HIDDEN INPUTS & ACTION --}}
            {{-- ========================================================= --}}
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary btn-icon px-4 py-2 fw-bold">
                    <i class="bi bi-save-fill"></i> Update Invoice
                </button>
                <a href="{{ route('invoice-customers.index') }}" class="btn btn-secondary btn-icon px-4 py-2">
                    <i class="bi bi-x-circle-fill"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

{{-- ========================================================= --}}
{{-- SCRIPTS --}}
{{-- ========================================================= --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    // =========================================================
    // INIT SELECT2 TAGS
    // =========================================================
    $('.select2-tags').select2({
        tags: true,
        placeholder: "Select or Type Option",
        allowClear: true,
        width: '100%'
    });

    let rowCounter = {{ count($invoice->details) }};

    // =========================================================
    // ATTACHMENT DELETE HANDLER
    // =========================================================
    $('#btn-delete-attachment').on('click', function() {
        if (confirm('Are you sure you want to remove the current attachment?')) {
            $('#delete_attachment_input').val('1');
            $('#old-attachment-wrapper').addClass('d-none');
            $('#deleted-badge-msg').removeClass('d-none');
        }
    });

    $('#tax_invoice_attachment').on('change', function() {
        if ($(this).val()) {
            $('#delete_attachment_input').val('1');
            $('#old-attachment-wrapper').addClass('d-none');
            $('#deleted-badge-msg').addClass('d-none');
        }
    });

    // =========================================================
    // FUNCTIONS
    // =========================================================
    function formatRupiah(angka) {
        if (!angka || isNaN(angka)) return '0';
        return new Intl.NumberFormat('id-ID').format(Math.round(angka));
    }

    function parseRupiahToNumber(str) {
        if (!str) return 0;
        return parseInt(str.toString().replace(/\./g, '')) || 0;
    }

    function updateStatus(type, remaining) {
        if (type === 'proforma') {
            $('#status').val('proforma');
        } else if (type === 'sales') {
            if (remaining <= 1) {
                $('#status').val('completed');
            } else {
                $('#status').val('partial');
            }
        }
    }

    function calculateRowSubtotal(row) {
        let qty = parseInt(row.find('.qty-input').val()) || 0;
        let price = parseRupiahToNumber(row.find('.price-display').val());
        let subtotal = qty * price;
        row.find('.subtotal-display').val(formatRupiah(subtotal));
        row.find('.subtotal-hidden').val(subtotal);
        return subtotal;
    }

    function calculateGrandTotal() {
        let subtotal = 0;
        $('.subtotal-hidden').each(function () {
            subtotal += parseFloat($(this).val()) || 0;
        });

        let dpPercent = parseFloat($('#dp_percent').val()) || 0;
        let discountPercent = parseFloat($('#discount_percent').val()) || 0;
        let taxPercent = parseFloat($('#tax_percent').val()) || 0;
        let pph23Percent = parseFloat($('#pph23_percent').val()) || 0;

        let dpAmount = subtotal * dpPercent / 100;
        let baseAmount = (dpAmount > 0) ? dpAmount : subtotal;
        let discountAmount = baseAmount * discountPercent / 100;
        let dpp = baseAmount - discountAmount;
        let taxAmount = dpp * taxPercent / 100;
        let pph23Amount = dpp * pph23Percent / 100;
        let grandTotal = dpp + taxAmount - pph23Amount;
        let remaining = (dpAmount > 0) ? (subtotal - dpAmount) : 0;

        let dppLain = dpAmount * 11 / 12;
        $('#dpplain-text').text('Rp ' + formatRupiah(dppLain));

        $('#subtotal-text').text('Rp ' + formatRupiah(subtotal));
        $('#subtotal-value').val(subtotal);
        $('#dp-text').text('Rp ' + formatRupiah(dpAmount));
        $('#dp-value').val(dpAmount);
        $('#discount-text').text('Rp ' + formatRupiah(discountAmount));
        $('#discount-value').val(discountAmount);
        $('#tax-text').text('Rp ' + formatRupiah(taxAmount));
        $('#tax-value').val(taxAmount);
        $('#pph23-text').text('Rp ' + formatRupiah(pph23Amount));
        $('#pph23-value').val(pph23Amount);
        $('#grand-total-text').text('Rp ' + formatRupiah(grandTotal));
        $('#grand-total-value').val(grandTotal);
        $('#remaining-text').text('Rp ' + formatRupiah(remaining));
        $('#remaining-value').val(remaining);

        // Tampilkan/sembunyikan PPH23 row
        if (pph23Percent > 0) {
            $('#pph23_row, #pph23_amount_row').show();
        } else {
            $('#pph23_row, #pph23_amount_row').hide();
        }

        let invoiceType = $('#type').val();
        updateStatus(invoiceType, remaining);
    }

    // =========================================================
    // EVENT LISTENERS
    // =========================================================
    $('#type').change(calculateGrandTotal);

    $('#has_pph23').change(function () {
        if ($(this).is(':checked')) {
            $('#pph23_row, #pph23_amount_row').show();
            $('#pph23_percent').val(2);
        } else {
            $('#pph23_row, #pph23_amount_row').hide();
            $('#pph23_percent').val(0);
        }
        calculateGrandTotal();
    });

    $('#payment_terms').change(function () {
        let terms = $(this).val();
        let match = terms.match(/(\d+)%/);
        if (match) {
            $('#dp_percent').val(match[1]);
        } else {
            $('#dp_percent').val(100);
        }
        calculateGrandTotal();
    });

    $(document).on('input change', '.qty-input, .price-display', function () {
        let row = $(this).closest('.product-row');
        if ($(this).hasClass('price-display')) {
            let raw = parseRupiahToNumber($(this).val());
            $(this).val(formatRupiah(raw));
            row.find('.price-hidden').val(raw);
            row.find('.price-numeric-hidden').val(raw);
        }
        calculateRowSubtotal(row);
        calculateGrandTotal();
    });

    $('#dp_percent, #discount_percent, #tax_percent, #pph23_percent').on('input change', calculateGrandTotal);

    // =========================================================
    // REMOVE PRODUCT
    // =========================================================
    $(document).on('click', '.remove-product', function () {
        $(this).closest('.product-row').remove();
        calculateGrandTotal();
    });

    // =========================================================
    // ADD PRODUCT
    // =========================================================
    let productList = @json($products ?? []);
    $('#add-product').on('click', function () {
        let newIndex = Date.now();
        let productOptions = '<option value="">-- Pilih Produk --</option>';
        if (productList.length === 0) {
            productOptions = '<option value="">Tidak ada produk tersedia</option>';
        } else {
            $.each(productList, function (key, product) {
                productOptions += `<option value="${product.id}"
                    data-brand="${product.brand || '-'}"
                    data-price="${product.price || 0}"
                    data-product-code2="${product.product_code2 || ''}"
                    data-name2="${product.name2 || ''}">
                    ${product.product_code} - ${product.name}
                </option>`;
            });
        }

        let newRow = `
            <div class="product-card product-row" data-row-id="${newIndex}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Product</label>
                        <select name="items[${newIndex}][product_id]" class="form-select product-select" required>
                            ${productOptions}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Brand</label>
                        <input type="text" class="form-control brand-display" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Qty</label>
                        <input type="number" name="items[${newIndex}][quantity]" class="form-control qty-input" value="1" min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Price</label>
                        <input type="text" class="form-control price-display" value="0">
                        <input type="hidden" name="items[${newIndex}][unit_price]" class="price-hidden" value="0">
                        <input type="hidden" name="items[${newIndex}][unit_price_numeric]" class="price-numeric-hidden" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Subtotal</label>
                        <input type="text" class="form-control subtotal-display" readonly value="Rp 0">
                        <input type="hidden" name="items[${newIndex}][subtotal]" class="subtotal-hidden" value="0">
                        <button type="button" class="btn btn-danger btn-sm mt-2 remove-product btn-remove">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Product Code 2</label>
                        <input type="text" class="form-control code2-display" readonly>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label fw-semibold">Product Name 2</label>
                        <input type="text" class="form-control name2-display" readonly>
                    </div>
                </div>
            </div>
        `;
        $('#products-container').append(newRow);
        calculateGrandTotal();
    });

    // =========================================================
    // PRODUCT SELECT CHANGE
    // =========================================================
    $(document).on('change', '.product-select', function () {
        let row = $(this).closest('.product-row');
        let opt = $(this).find('option:selected');
        let brand = opt.data('brand') || '-';
        let price = opt.data('price') || 0;
        let code2 = opt.data('product-code2') || '';
        let name2 = opt.data('name2') || '';

        row.find('.brand-display').val(brand);
        row.find('.price-display').val(formatRupiah(price)).trigger('change');
        row.find('.price-hidden').val(price);
        row.find('.price-numeric-hidden').val(price);
        row.find('.code2-display').val(code2);
        row.find('.name2-display').val(name2);
        calculateRowSubtotal(row);
        calculateGrandTotal();
    });

    // =========================================================
    // INITIAL CALCULATION
    // =========================================================
    $('.product-row').each(function () {
        let row = $(this);
        let price = parseRupiahToNumber(row.find('.price-display').val());
        row.find('.price-hidden').val(price);
        row.find('.price-numeric-hidden').val(price);
        calculateRowSubtotal(row);
    });
    calculateGrandTotal();

    // Jika PPH23 > 0, tampilkan row
    if (parseFloat($('#pph23_percent').val()) > 0) {
        $('#pph23_row, #pph23_amount_row').show();
    }
});
</script>
@endsection