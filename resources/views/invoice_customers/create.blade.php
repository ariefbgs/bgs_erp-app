@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-invoice-customers-create bgs-mode-create')
@section('title', 'Create Sales Invoice')
@section('content')



<div class="card shadow-sm">
    <div class="card-header-custom bgs-page-header">
        <h4><i class="bi bi-file-earmark-plus-fill"></i> Create Sales Invoice</h4>
    </div>
    <div class="card-body p-4">

        {{-- ALERT --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm bgs-u-336700042f4a">
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
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm bgs-u-336700042f4a">
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
                                <option value="{{ $po->id }}" data-remaining="{{ $po->remaining_total ?? 0 }}" data-remaining-base="{{ $po->remaining_subtotal_before_tax ?? 0 }}" data-original-base="{{ $po->subtotal ?? 0 }}" {{ old('po_customer_id') == $po->id ? 'selected' : '' }}>
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
                        <div class="info-box bgs-u-d0466aa33fa8" id="parentInvoiceInfo">
                            <strong>Parent invoice:</strong>
                            <span id="parentInvoiceValue" class="highlight">Ditentukan otomatis oleh sistem</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bgs-u-6e22c58a7a6a" id="poRemainingInfo">
                            <strong>Sisa PO Amount sebelum pajak:</strong> <span id="poRemainingBaseValue" class="highlight">Rp 0</span>
                            <span id="poRemainingWarning" class="text-danger ms-2 bgs-u-6e22c58a7a6a">(melebihi sisa!)</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- BAGIAN 2: INVOICE DETAILS (muncul setelah PO dipilih)     --}}
            {{-- ========================================================= --}}
            <div id="invoiceDetails" class="bgs-u-6e22c58a7a6a">

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

                    <div class="col-12">
                        <small id="termsInheritanceInfo"
                               class="text-muted bgs-u-d0466aa33fa8"
                              >
                            <i class="bi bi-arrow-return-right me-1"></i>
                            Payment Terms & Delivery Time inherited from
                            <strong id="termsInheritanceSource"></strong>.
                            You may adjust them for this invoice.
                        </small>
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
                    <input type="checkbox"
                           class="form-check-input"
                           id="has_pph23"
                           name="has_pph23"
                           value="1"
                           {{ old('has_pph23') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="has_pph23">With PPH23</label>
                </div>

                {{-- 2d. Invoice Allocation Strategy --}}
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-diagram-3-fill"></i> Invoice Allocation
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">
                                Allocation Method <span class="text-danger">*</span>
                            </label>

                            <select name="allocation_mode"
                                    id="allocation_mode"
                                    class="form-select"
                                    required>
                                <option value="PERCENTAGE"
                                    {{ old('allocation_mode', 'PERCENTAGE') === 'PERCENTAGE' ? 'selected' : '' }}>
                                    By Payment Percentage
                                </option>
                                <option value="ITEM_QTY"
                                    {{ old('allocation_mode') === 'ITEM_QTY' ? 'selected' : '' }}>
                                    By Item / Quantity
                                </option>
                            </select>

                            <div class="form-text">
                                A PO Customer uses one active normal allocation method.
                            </div>
                        </div>

                        <div class="col-md-6" id="percentageStrategyInfo">
                            <div class="info-box mb-0">
                                Payment Percentage is calculated from the original PO commercial amount.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2e. Product Details Table --}}
                <div class="section-card" id="productDetailsCard">
                    <div class="section-title"><i class="bi bi-box-fill"></i> Product Details</div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center bgs-u-24789f0cb7a8">Select</th>
                                    <th>Product</th>
                                    <th class="text-center bgs-u-2612b3574623">Qty PO</th>
                                    <th class="text-center bgs-u-2945a75e3ed0">Qty Invoice</th>
                                    <th class="text-end bgs-u-a40e53d8ec68">Unit Price</th>
                                    <th class="text-end text-nowrap bgs-u-76b49776b3e0">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Select PO Customer first</td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end">Subtotal</th>
                                    <th class="text-end" id="subtotal_display">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Discount (%)</th>
                                    <td>
                                        <input type="number" name="discount_percent" id="discount_percent"
                                               class="form-control text-end" step="0.5" min="0" max="100" value="{{ old('discount_percent', 0) }}">
                                    </td>
                                    <th class="text-end" id="discount_amount_display">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Payment Percentage (%)</th>
                                    <td>
                                        <input type="number" name="dp_percent" id="dp_percent"
                                               class="form-control text-end" step="0.0001" min="0.0001" max="100" value="{{ old('dp_percent', 100) }}">
                                    </td>
                                    <th class="text-end" id="dp_amount_display">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="5" class="text-end">DPP Lain (11/12 &times; DP)</th>
                                    <th class="text-end" id="dpplain_display">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">PPN (%)</th>
                                    <td>
                                        <input type="number" name="tax_percent" id="tax_percent"
                                               class="form-control text-end" step="0.5" min="0" max="100" value="{{ old('tax_percent', 11) }}">
                                    </td>
                                    <th class="text-end" id="tax_amount_display">Rp 0</th>
                                </tr>
                                <tr id="pph23_row" class="bgs-u-6e22c58a7a6a">
                                    <th colspan="4" class="text-end">PPH23 (%)</th>
                                    <td>
                                        <input type="number"
                                               id="pph23_percent"
                                               class="form-control text-end"
                                               step="0.01"
                                               min="0"
                                               max="100"
                                               value="{{ old('pph23_percent', 0) }}">

                                        <input type="hidden"
                                               name="pph23_percent"
                                               id="pph23_percent_submit"
                                               value="{{ old('pph23_percent', 0) }}">
                                    </td>
                                    <th class="text-end" id="pph23_amount_display">Rp 0</th>
                                </tr>
                                <tr class="table-primary">
                                    <th colspan="5" class="text-end">Grand Total</th>
                                    <th class="text-end"><strong id="grand_total_display">Rp 0</strong></th>
                                </tr>
                                <tr>
                                    <th colspan="5" class="text-end">Remaining PO Amount Before Tax</th>
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
    <label class="form-label">Invoice Status</label>
    <input type="text"
           class="form-control"
           value="Calculated automatically"
           readonly>
    <div class="form-text">
        Partial or Completed is determined automatically from the remaining PO amount.
    </div>
</div>
                        <div class="col-md-6">
    <label class="form-label">Payment Status</label>
    <input type="text"
           class="form-control"
           value="Sent"
           readonly>
    <div class="form-text">
        New invoices always start with payment status Sent.
    </div>
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
        $('.invoice-item-row').each(function () {
            const rowId = $(this).data('row');
            const $selector = $(this).find('.invoice-line-select');

            if ($selector.length && !$selector.is(':checked')) {
                return;
            }

            subtotal += parseFloat(
                $('#subtotal_' + rowId).attr('data-value')
            ) || 0;
        });

        const allocationMode = $('#allocation_mode').val() || 'PERCENTAGE';

        let dpPercent = parseFloat($('#dp_percent').val()) || 0;

        if (allocationMode === 'ITEM_QTY') {
            dpPercent = 100;
            $('#dp_percent').val(100);
        }

        let discountPercent = parseFloat($('#discount_percent').val()) || 0;
        let taxPercent = parseFloat($('#tax_percent').val()) || 0;
        let pph23Percent = $('#has_pph23').is(':checked')
            ? (parseFloat($('#pph23_percent').val()) || 0)
            : 0;

        /*
         * Display calculation only. Store recalculates authoritatively.
         */
        const poOriginalBase = parseFloat(
            $('#po_customer_id option:selected').data('original-base')
        ) || 0;

        let dpAmount = allocationMode === 'PERCENTAGE'
            ? poOriginalBase * dpPercent / 100
            : subtotal;

        let baseAmount = dpAmount;
        let discountAmount = baseAmount * discountPercent / 100;
        let dpp = baseAmount - discountAmount;
        let taxAmount = dpp * taxPercent / 100;
        let pph23Amount = dpp * pph23Percent / 100;
        let grandTotal = dpp + taxAmount - pph23Amount;
        let poRemainingBase = parseFloat($('#po_customer_id option:selected').data('remaining-base')) || 0;
        let remaining = Math.max(0, poRemainingBase - baseAmount);

        let dppLain = dpAmount * 11 / 12;
        $('#dpplain_display').html('Rp ' + formatRupiah(dppLain));

        const displaySubtotal = allocationMode === 'PERCENTAGE'
            ? poOriginalBase
            : subtotal;

        $('#subtotal_display').html('Rp ' + formatRupiah(displaySubtotal));
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
        $('#pph23_percent_submit').val(pph23Percent);
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
            $('#pph23_percent_submit').val(0);
        }
        calculateAll();
    });

    function applyAllocationModeUi() {
        const isItemQty = ($('#allocation_mode').val() || 'PERCENTAGE') === 'ITEM_QTY';

        $('#productDetailsCard').show();

        $('.invoice-line-select').each(function () {
            const checkbox = $(this);
            const rowId = checkbox.data('row');
            const qty = $('#qty_' + rowId);
            const remainingQty = parseFloat(qty.attr('max')) || 0;

            if (isItemQty) {
                checkbox.prop('disabled', remainingQty <= 0);

                if (remainingQty > 0) {
                    checkbox.prop('checked', true);
                }

                qty.prop(
                    'disabled',
                    remainingQty <= 0 || !checkbox.prop('checked')
                );
            } else {
                checkbox.prop('checked', false);
                checkbox.prop('disabled', true);
                qty.prop('disabled', true);
            }
        });

        $('#dp_percent').prop('readonly', isItemQty);

        if (isItemQty) {
            $('#dp_percent').val(100);
        }

        $('#percentageStrategyInfo').toggle(!isItemQty);
    }

    $('#allocation_mode').on('change.uiParity', function () {
        applyAllocationModeUi();
        calculateAll();
    });

    $(document).ajaxComplete(function () {
        applyAllocationModeUi();
    });
    function percentageFromPaymentTerms(terms, fallbackPercent = 100) {
        terms = (terms || '').trim();

        const match = terms.match(/(\d+(?:\.\d+)?)%/);
        if (match) {
            return Math.min(100, Math.max(0, parseFloat(match[1]) || 0));
        }

        const normalized = terms.toLowerCase();

        if (
            normalized.includes('100%') ||
            normalized.includes('cash before delivery') ||
            normalized.includes('(cbd)') ||
            normalized.includes('cash on delivery') ||
            normalized.includes('(cod)') ||
            normalized.includes('days after delivery')
        ) {
            return 100;
        }

        return Math.min(100, Math.max(0, parseFloat(fallbackPercent) || 0));
    }

    $('#payment_terms').on('change', function () {
        const terms = $(this).val();
        const selectedPo = $('#po_customer_id option:selected');
        const poRemainingBase = parseFloat(selectedPo.data('remaining-base')) || 0;

        let poSubtotal = 0;
        $('[id^="subtotal_"]').each(function () {
            poSubtotal += parseFloat($(this).attr('data-value')) || 0;
        });

        const derivedPercent = percentageFromPaymentTerms(
            terms,
            100
        );

        $('#dp_percent').val(
            Number(Number(derivedPercent).toFixed(4))
        );

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
            url: '{{ route("invoice-customers.get-po-customer-details", "__ID__") }}'.replace('__ID__', poId),
            type: 'GET',
            success: function (res) {
                let rows = '';
                rowIndex = 0;

                $.each(res.items, function (i, item) {
                    rows += `
                    <tr id="row_${rowIndex}" class="invoice-item-row"
                        data-row="${rowIndex}">
                        <td class="text-center align-middle">
                            <input type="checkbox"
                                class="form-check-input invoice-line-select"
                                data-row="${rowIndex}"
                                ${Number(item.remaining_quantity) > 0 ? 'checked' : 'disabled'}>
                        </td>
                        <td>
                            ${item.product_name}
                            <br>
                            <small class="text-muted">
                                ${item.product_code} - ${item.brand || '-'}
                            </small>

                            <input type="hidden"
                                class="invoice-line-submit"
                                name="details[${rowIndex}][po_customer_detail_id]"
                                value="${item.po_customer_detail_id}">

                            <input type="hidden"
                                data-field="product_id"
                                value="${item.product_id}">
                        </td>

                        <td class="text-center">
                            ${item.po_quantity} ${item.unit || ''}
                        </td>

                        <td>
                            <input type="number"
                                name="details[${rowIndex}][quantity]"
                                id="qty_${rowIndex}"
                                class="form-control qty-input invoice-line-submit text-end bgs-u-6c4370585036"

                                data-row="${rowIndex}"
                                value="${item.remaining_quantity}"
                                min="0.0001"
                                max="${item.remaining_quantity}"
                                step="any">
                        </td>

                        <td>
                            <input type="text"
                                id="price_${rowIndex}"
                                class="form-control price-input text-end bgs-u-7764811b4dfb"

                                data-row="${rowIndex}"
                                value="${formatRupiah(item.unit_price)}"
                                readonly>

                            <input type="hidden"
                                data-field="unit_price_numeric"
                                id="price_hidden_${rowIndex}"
                                value="${item.unit_price}">
                        </td>

                        <td class="text-end text-nowrap bgs-u-76b49776b3e0"

                            id="subtotal_${rowIndex}"
                            data-value="0">
                            Rp 0
                        </td>

                        <input type="hidden"
                            id="subtotal_hidden_${rowIndex}"
                            data-field="subtotal"
                            value="0">
                    </tr>`;
                    rowIndex++;
                });

                if (!rows) {
                    rows = `
                        <tr>
                            <td colspan="6"
                                class="text-center text-muted">
                                PO Customer tidak memiliki detail item.
                            </td>
                        </tr>`;
                }

                $('#itemsBody').html(rows);

                applyAllocationMode();

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

                if (res.terms_source_label) {
                    $('#termsInheritanceSource').text(res.terms_source_label);
                    $('#termsInheritanceInfo').show();
                } else {
                    $('#termsInheritanceInfo').hide();
                    $('#termsInheritanceSource').text('');
                }

                if (res.has_previous_invoice) {
                    $('#parentInvoiceValue').text(res.root_invoice_number + ' (otomatis)');
                    $('#parentInvoiceInfo').show();
                    // Subsequent invoice defaults to 100% of selected item/qty value.
                    // Amount balance remains authoritative and is enforced server-side.
                    // User may still reduce Payment Percentage for partial-by-amount.
                    $('#dp_percent').val(100);

                    const poDiscountPercent = Number(
                        Number(res.discount_percent || 0).toFixed(4)
                    );
                    $('#discount_percent').val(poDiscountPercent);

                    $('#tax_percent').val(res.tax_percent || 0);

                    const poPphPercent = Number(
                        Number(res.pph23_percent || 0).toFixed(4)
                    );
                    $('#pph23_percent').val(poPphPercent);
                    $('#has_pph23').prop('checked', poPphPercent > 0);
                    $('#pph23_row').toggle(poPphPercent > 0);
                } else {
                    $('#parentInvoiceValue').text('Invoice ini akan menjadi parent/root');
                    $('#parentInvoiceInfo').show();

                    const initialTerms = $paymentTerms.val() || '';
                    const initialPercent = percentageFromPaymentTerms(
                        initialTerms,
                        Number(res.recommended_percent || 100)
                    );

                    $('#dp_percent').val(
                        Number(Number(initialPercent).toFixed(4))
                    );

                    const poDiscountPercent = Number(
                        Number(res.discount_percent || 0).toFixed(4)
                    );
                    $('#discount_percent').val(poDiscountPercent);

                    $('#tax_percent').val(res.tax_percent || 0);

                    const poPphPercent = Number(
                        Number(res.pph23_percent || 0).toFixed(4)
                    );
                    $('#pph23_percent').val(poPphPercent);
                    $('#has_pph23').prop('checked', poPphPercent > 0);
                    $('#pph23_row').toggle(poPphPercent > 0);
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

    function applyAllocationMode() {
        const mode = $('#allocation_mode').val() || 'PERCENTAGE';
        const isItemQty = mode === 'ITEM_QTY';

        $('#productDetailsCard').toggle(isItemQty);
        $('#percentageStrategyInfo').toggle(!isItemQty);

        $('#dp_percent')
            .prop('readonly', isItemQty)
            .val(isItemQty ? 100 : ($('#dp_percent').val() || 100));

        $('.invoice-line-select').each(function () {
            const rowId = $(this).data('row');

            const selected =
                isItemQty &&
                $(this).is(':checked') &&
                !$(this).is(':disabled');

            $('#row_' + rowId)
                .find('.invoice-line-submit')
                .prop('disabled', !selected);
        });

        calculateAll();
    }

    $('#allocation_mode').on('change', applyAllocationMode);

    // =========================================================
    // ITEM SELECTION
    $(document).on('change', '.invoice-line-select', function () {
        const rowId = $(this).data('row');
        const selected = $(this).is(':checked') && !$(this).is(':disabled');

        $('#row_' + rowId)
            .find('.invoice-line-submit')
            .prop('disabled', !selected);

        calculateAll();
    });

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

    applyAllocationMode();

    // =========================================================
    // RM-004O SUBMIT SAFETY
    // Product / Qty / Price are PO Customer controlled.
    // =========================================================

    $('form').on('submit', function (event) {
        const submittedPph23Percent =
            $('#has_pph23').is(':checked')
                ? (parseFloat($('#pph23_percent').val()) || 0)
                : 0;

        $('#pph23_percent_submit').val(submittedPph23Percent);

        const allocationMode = $('#allocation_mode').val();

        if (allocationMode === 'PERCENTAGE') {
            const percentage = parseFloat($('#dp_percent').val()) || 0;

            if (percentage <= 0 || percentage > 100) {
                event.preventDefault();
                alert('Payment Percentage must be greater than 0 and not exceed 100%.');
                return false;
            }

            return true;
        }

        // ITEM_QTY is always 100% server-side; mirror that in the browser.
        $('#dp_percent').val(100);

        const $selectedLines =
            $('.invoice-line-select:checked:not(:disabled)');

        if ($selectedLines.length === 0) {
            event.preventDefault();
            alert('Select at least one invoice item.');
            return false;
        }

        let invalidQty = false;

        $selectedLines.each(function () {
            const rowId = $(this).data('row');
            const $qtyInput = $('#row_' + rowId).find('.qty-input');

            const qty = parseFloat($qtyInput.val()) || 0;
            const maxQty = parseFloat($qtyInput.attr('max')) || 0;

            if (qty <= 0 || (maxQty > 0 && qty > maxQty)) {
                invalidQty = true;
            }
        });

        if (invalidQty) {
            event.preventDefault();
            alert('Qty Invoice must be greater than 0 and cannot exceed Remaining Qty.');
            return false;
        }
    });

});
</script>
@endsection
