@extends('layouts.app')
@section('title', 'Create Sales Invoice')
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
    .form-control-sm-custom {
        font-size: 0.9rem;
        padding: 0.35rem 0.65rem;
    }
    .btn-icon i {
        margin-right: 6px;
    }
    .card-header-custom {
        background: linear-gradient(135deg, #0284c7, #075985);
        color: white;
        padding: 1rem 1.5rem;
        border: none;
        border-radius: 12px 12px 0 0;
    }
    .card-header-custom h4 {
        margin: 0;
        font-weight: 700;
    }
    .card-header-custom h4 i {
        margin-right: 8px;
    }
    .form-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: #334155;
        margin-bottom: 4px;
    }
    .input-group-text-custom {
        background: white;
        border-right: none;
    }
    .select2-container .select2-selection--single {
        min-height: 40px !important;
        border-color: #cbd5e1 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important;
        padding-left: 12px !important;
    }
    .text-end-custom {
        text-align: right;
    }
    .info-box {
        background: #f1f5f9;
        border-left: 4px solid #0284c7;
        padding: 10px 14px;
        border-radius: 6px;
        margin-bottom: 12px;
    }
    .info-box strong {
        color: #0f172a;
    }
    .info-box .highlight {
        color: #0284c7;
        font-weight: 700;
    }
    @media print {
        .no-print { display: none !important; }
    }
</style>

<div class="card shadow-sm">
    <div class="card-header-custom">
        <h4><i class="bi bi-file-earmark-plus-fill"></i> Create Sales Invoice</h4>
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

        <form action="{{ route('invoice-customers.store') }}" method="POST" id="invoiceForm" enctype="multipart/form-data">
            @csrf

            {{-- ========================================================= --}}
            {{-- BAGIAN 1: PO CUSTOMER & HEADER --}}
            {{-- ========================================================= --}}
            <div class="section-card">
                <div class="section-title"><i class="bi bi-box-seam-fill"></i> PO & Invoice Header</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Select PO Customer <span class="text-danger">*</span></label>
                        <select name="po_customer_id" id="po_customer_id" class="form-select" required>
                            <option value="">-- Select PO Customer --</option>
                            @foreach($poCustomers as $po)
                                <option value="{{ $po->id }}" data-remaining="{{ $po->remaining_total ?? 0 }}" data-remaining-base="{{ $po->remaining_subtotal_before_tax ?? 0 }}" {{ old('po_customer_id') == $po->id ? 'selected' : '' }}>
                                    {{ $po->po_number }} - {{ $po->customer->name }}
                                    (sisa: Rp {{ number_format($po->remaining_total ?? 0, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type Invoice <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="proforma">Proforma Invoice</option>
                            <option value="sales" selected>Sales Invoice</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <div class="info-box" id="parentInvoiceInfo" style="display:none;">
                            <strong>Parent invoice:</strong>
                            <span id="parentInvoiceValue" class="highlight">Ditentukan otomatis oleh sistem</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box" id="poRemainingInfo" style="display: none;">
                            <strong>Sisa PO Amount sebelum pajak:</strong> <span id="poRemainingBaseValue" class="highlight">Rp 0</span>
                            <span id="poRemainingWarning" class="text-danger ms-2" style="display: none;">(melebihi sisa!)</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- BAGIAN 2: INVOICE DETAILS (muncul setelah PO dipilih)     --}}
            {{-- ========================================================= --}}
            <div id="invoiceDetails" style="display: none;">

                {{-- 2a. Payment & Delivery Info --}}
                <div class="section-card">
                    <div class="section-title"><i class="bi bi-credit-card-fill"></i> Payment & Delivery</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                        </div>
                        <div class="col-md-4">
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
                                    <option value="{{ $payment }}" {{ old('payment_terms', '100% in advance') == $payment ? 'selected' : '' }}>
                                        {{ $payment }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
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
                </div>

                {{-- 2b. Tax Invoice Number & Attachment --}}
                <div class="section-card">
                    <div class="section-title"><i class="bi bi-file-earmark-text-fill"></i> Tax Invoice Reference</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tax Invoice Number</label>
                            <input type="text" name="tax_invoice_number" class="form-control" value="{{ old('tax_invoice_number') }}">
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
                        </div>
                    </div>
                </div>

                <hr>

                {{-- 2c. PPH23 Checkbox --}}
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="has_pph23" {{ old('has_pph23') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="has_pph23">With PPH23</label>
                </div>

                {{-- 2d. Product Details Table --}}
                <div class="section-card">
                    <div class="section-title"><i class="bi bi-box-fill"></i> Product Details</div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="35%">Product</th>
                                    <th width="15%">Qty PO</th>
                                    <th width="15%">Qty Invoice</th>
                                    <th width="20%">Unit Price</th>
                                    <th width="15%">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Select PO Customer first</td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">Subtotal</th>
                                    <th class="text-end" id="subtotal_display">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Discount (%)</th>
                                    <td>
                                        <input type="number" name="discount_percent" id="discount_percent"
                                               class="form-control text-end" step="0.5" min="0" max="100" value="{{ old('discount_percent', 0) }}">
                                    </td>
                                    <th class="text-end" id="discount_amount_display">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Payment Percentage (%)</th>
                                    <td>
                                        <input type="number" name="dp_percent" id="dp_percent"
                                               class="form-control text-end" step="0.5" min="0" max="100" value="{{ old('dp_percent', 0) }}">
                                    </td>
                                    <th class="text-end" id="dp_amount_display">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">DPP Lain (11/12 × DP)</th>
                                    <th class="text-end" id="dpplain_display">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">PPN (%)</th>
                                    <td>
                                        <input type="number" name="tax_percent" id="tax_percent"
                                               class="form-control text-end" step="0.5" min="0" max="100" value="{{ old('tax_percent', 11) }}">
                                    </td>
                                    <th class="text-end" id="tax_amount_display">Rp 0</th>
                                </tr>
                                <tr id="pph23_row" style="display: none;">
                                    <th colspan="3" class="text-end">PPH23 (%)</th>
                                    <td>
                                        <input type="number" name="pph23_percent" id="pph23_percent"
                                               class="form-control text-end" step="0.5" min="0" max="100" value="{{ old('pph23_percent', 0) }}">
                                    </td>
                                    <th class="text-end" id="pph23_amount_display">Rp 0</th>
                                </tr>
                                <tr class="table-primary">
                                    <th colspan="4" class="text-end">Grand Total</th>
                                    <th class="text-end"><strong id="grand_total_display">Rp 0</strong></th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Remaining PO Amount Before Tax</th>
                                    <th class="text-end" id="remaining_display">Rp 0</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- 2e. Remarks & Status --}}
                <div class="section-card">
                    <div class="section-title"><i class="bi bi-chat-left-text-fill"></i> Additional Info</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="proforma">Proforma</option>
                                <option value="partial" selected>Partial</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" class="form-select" required>
                                <option value="sent" selected>Sent</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- ========================================================= --}}
                {{-- HIDDEN INPUTS UNTUK KALKULASI --}}
                {{-- ========================================================= --}}
                <div class="text-end mt-3">
                    <input type="hidden" id="subtotal_value" name="subtotal">
                    <input type="hidden" id="dp_amount_value" name="dp_amount">
                    <input type="hidden" id="discount_amount_value" name="discount_amount">
                    <input type="hidden" id="tax_amount_value" name="tax_amount">
                    <input type="hidden" id="pph23_amount_value" name="pph23_amount">
                    <input type="hidden" id="grand_total_value" name="total">
                    <input type="hidden" id="remaining_amount_value" name="remaining_amount">

                    <button type="submit" class="btn btn-primary btn-icon px-4 py-2 fw-bold">
                        <i class="bi bi-save-fill"></i> Save Invoice
                    </button>
                    <a href="{{ route('invoice-customers.index') }}" class="btn btn-secondary btn-icon px-4 py-2">
                        <i class="bi bi-x-circle-fill"></i> Cancel
                    </a>
                </div>
            </div>
            {{-- end #invoiceDetails --}}

        </form>
    </div>
</div>

{{-- ========================================================= --}}
{{-- SCRIPTS --}}
{{-- ========================================================= --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
{{-- CDN SELECT2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function () {
    // =========================================================
    // INIT SELECT2 TAGS
    // =========================================================
    $('.select2-tags').select2({
        tags: true,
        placeholder: function() {
            return $(this).data('placeholder') || "Select or type new option";
        },
        allowClear: true,
        width: '100%',
        createTag: function (params) {
            var term = $.trim(params.term);
            if (term === '') {
                return null;
            }
            return {
                id: term,
                text: term,
                newTag: true
            };
        }
    });

    let rowIndex = 0;

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID').format(Math.round(number || 0));
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

    function parseNumber(value) {
        if (!value) return 0;
        let cleaned = value.toString().replace(/[^0-9,.]/g, '');
        cleaned = cleaned.replace(/,/g, '.');
        return parseFloat(cleaned) || 0;
    }

    function calculateSubtotal(rowId) {
        let qty = parseFloat($('#qty_' + rowId).val()) || 0;
        let price = parseFloat($('#price_hidden_' + rowId).val()) || 0;
        let subtotal = qty * price;
        $('#subtotal_' + rowId)
            .attr('data-value', subtotal)
            .text('Rp ' + formatRupiah(subtotal));
        $('#subtotal_hidden_' + rowId).val(subtotal);
        calculateAll();
    }

    function calculateAll() {
        let subtotal = 0;
        $('[id^="subtotal_"]').each(function () {
            subtotal += parseFloat($(this).attr('data-value')) || 0;
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
        let poRemainingBase = parseFloat($('#po_customer_id option:selected').data('remaining-base')) || 0;
        let remaining = Math.max(0, poRemainingBase - baseAmount);

        let dppLain = dpAmount * 11 / 12;
        $('#dpplain_display').html('Rp ' + formatRupiah(dppLain));

        $('#subtotal_display').html('Rp ' + formatRupiah(subtotal));
        $('#dp_amount_display').html('Rp ' + formatRupiah(dpAmount));
        $('#discount_amount_display').html('Rp ' + formatRupiah(discountAmount));
        $('#tax_amount_display').html('Rp ' + formatRupiah(taxAmount));
        $('#pph23_amount_display').html('Rp ' + formatRupiah(pph23Amount));
        $('#grand_total_display').html('Rp ' + formatRupiah(grandTotal));
        $('#remaining_display').html('Rp ' + formatRupiah(remaining));

        $('#subtotal_value').val(subtotal);
        $('#dp_amount_value').val(dpAmount);
        $('#discount_amount_value').val(discountAmount);
        $('#tax_amount_value').val(taxAmount);
        $('#pph23_amount_value').val(pph23Amount);
        $('#grand_total_value').val(grandTotal);
        $('#remaining_amount_value').val(remaining);

        // Payment Amount dialokasikan sebelum diskon dan pajak.
        const invoiceBaseRupiah = Math.round(baseAmount + Number.EPSILON);
        const poRemainingBaseRupiah = Math.round(poRemainingBase + Number.EPSILON);
        if (invoiceBaseRupiah > poRemainingBaseRupiah && poRemainingBaseRupiah > 0) {
            $('#poRemainingWarning').show();
        } else {
            $('#poRemainingWarning').hide();
        }

        let invoiceType = $('#type').val();
        updateStatus(invoiceType, remaining);
    }

    // =========================================================
    // EVENT LISTENERS
    // =========================================================
    $('#type').change(calculateAll);

    $('#has_pph23').change(function () {
        if ($(this).is(':checked')) {
            $('#pph23_row').show();
        } else {
            $('#pph23_row').hide();
            $('#pph23_percent').val(0);
        }
        calculateAll();
    });

    $('#payment_terms').on('change', function () {
        let terms = $(this).val();
        let match = terms.match(/(\d+)%/);
        if (match) {
            $('#dp_percent').val(match[1]);
        } else {
            $('#dp_percent').val(0);
        }
        calculateAll();
    });

    // =========================================================
    // LOAD PO CUSTOMER DATA
    // =========================================================
    $('#po_customer_id').change(function () {
        let poId = $(this).val();
        if (!poId) {
            $('#invoiceDetails').hide();
            $('#poRemainingInfo').hide();
            return;
        }

        $('#invoiceDetails').show();

        let poRemainingBase = parseFloat($(this).find('option:selected').data('remaining-base')) || 0;
        $('#poRemainingBaseValue').text('Rp ' + formatRupiah(poRemainingBase));
        $('#poRemainingInfo').show();
        $('#poRemainingWarning').hide();

        $.ajax({
            url: @json(route('invoice-customers.get-po-customer-details', ['id' => '__ID__'])).replace('__ID__', encodeURIComponent(poId)),
            type: 'GET',
            success: function (res) {
                let rows = '';
                rowIndex = 0;

                $.each(res.items, function (i, item) {
                    rows += `
                    <tr id="row_${rowIndex}">
                        <td>
                            ${item.product_name}
                            <br>
                            <small class="text-muted">${item.product_code} -${item.brand || '-'}</small>
                        </td>
                        <td class="text-center">${item.quantity}${item.unit}</td>
                        <td>
                            <input type="number"
                                name="items[${rowIndex}][quantity]"
                                id="qty_${rowIndex}"
                                class="form-control qty-input"
                                data-row="${rowIndex}"
                                value="${item.quantity}"
                                min="1" required>
                        </td>
                        <td>
                            <input type="text"
                                id="price_${rowIndex}"
                                class="form-control price-input"
                                data-row="${rowIndex}"
                                value="${formatRupiah(item.unit_price)}" required>
                            <input type="hidden"
                                name="items[${rowIndex}][unit_price_numeric]"
                                id="price_hidden_${rowIndex}"
                                value="${item.unit_price}">
                        </td>
                        <td class="text-end" id="subtotal_${rowIndex}" data-value="0">Rp 0</td>
                        <input type="hidden" name="items[${rowIndex}][product_id]" value="${item.product_id}">
                        <input type="hidden" id="subtotal_hidden_${rowIndex}" name="items[${rowIndex}][subtotal]" value="0">
                    </tr>`;
                    rowIndex++;
                });

                $('#itemsBody').html(rows);

                var $paymentTerms = $('select[name="payment_terms"]');
                var $deliveryTime = $('select[name="delivery_time"]');

                if (res.payment_terms) {
                    if (!$paymentTerms.find('option[value="' + res.payment_terms + '"]').length) {
                        $paymentTerms.append(new Option(res.payment_terms, res.payment_terms, true, true));
                    }
                    $paymentTerms.val(res.payment_terms).trigger('change.select2');
                }

                if (res.delivery_time) {
                    if (!$deliveryTime.find('option[value="' + res.delivery_time + '"]').length) {
                        $deliveryTime.append(new Option(res.delivery_time, res.delivery_time, true, true));
                    }
                    $deliveryTime.val(res.delivery_time).trigger('change.select2');
                }

                if (res.has_previous_invoice) {
                    $('#parentInvoiceValue').text(res.root_invoice_number + ' (otomatis)');
                    $('#parentInvoiceInfo').show();
                    // Hilangkan nol desimal yang membingungkan (75.0000 berarti 75%, bukan Rp75 juta).
                    const recommendedPercent = Number(Number(res.recommended_percent || 0).toFixed(4));
                    $('#dp_percent').val(recommendedPercent);
                    $('#discount_percent').val(0);
                    $('#tax_percent').val(res.tax_percent || 0);
                    const poPphPercent = Number(Number(res.pph23_percent || 0).toFixed(4));
                    $('#pph23_percent').val(poPphPercent);
                    $('#has_pph23').prop('checked', poPphPercent > 0);
                    $('#pph23_row').toggle(poPphPercent > 0);
                } else {
                    $('#parentInvoiceValue').text('Invoice ini akan menjadi parent/root');
                    $('#parentInvoiceInfo').show();
                }

                setTimeout(function () {
                    for (let i = 0; i < rowIndex; i++) {
                        calculateSubtotal(i);
                    }
                }, 100);
            },
            error: function (xhr) {
                alert('Gagal mengambil data PO Customer. Periksa koneksi atau data PO.');
                console.error(xhr.responseText);
            }
        });
    });

    // =========================================================
    // INPUT QUANTITY & PRICE
    // =========================================================
    $(document).on('keyup change', '.qty-input, .price-input', function () {
        let rowId = $(this).data('row');
        if ($(this).hasClass('price-input')) {
            let value = parseNumber($(this).val());
            $(this).val(formatRupiah(value));
            $('#price_hidden_' + rowId).val(value);
        }
        calculateSubtotal(rowId);
    });

    // =========================================================
    // REKALKULASI SAAT DISKON / PAJAK BERUBAH
    // =========================================================
    $('#dp_percent, #discount_percent, #tax_percent, #pph23_percent')
        .on('keyup change', calculateAll);

    // =========================================================
    // RESTORE OLD VALUE (jika validasi gagal)
    // =========================================================
    var oldPoId = "{{ old('po_customer_id') }}";
    if (oldPoId) {
        $('#po_customer_id').val(oldPoId).trigger('change');
    }

    var oldPaymentTerms = "{{ old('payment_terms') }}";
    if (oldPaymentTerms) {
        $('select[name="payment_terms"]').val(oldPaymentTerms).trigger('change.select2');
    }

    var oldDeliveryTime = "{{ old('delivery_time') }}";
    if (oldDeliveryTime) {
        $('select[name="delivery_time"]').val(oldDeliveryTime).trigger('change.select2');
    }

    var oldStatus = "{{ old('status') }}";
    if (oldStatus) {
        $('#status').val(oldStatus);
    }

    var oldPaymentStatus = "{{ old('payment_status') }}";
    if (oldPaymentStatus) {
        $('select[name="payment_status"]').val(oldPaymentStatus);
    }
});
</script>
@endsection
