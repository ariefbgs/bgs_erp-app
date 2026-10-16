@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-invoice-customers-edit bgs-mode-edit')
@section('title', 'Edit Sales Invoice')
@section('content')




<div class="card shadow-sm border-0">
    <div class="card-header card-header-custom py-3 bgs-page-header">
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
                            value="{{ old('invoice_number', $invoice->invoice_number) }}" readonly>
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
            @if(!$normalAllocationEdit)
            <div class="section-card">
            @endif
                @if(!$normalAllocationEdit)
                <div class="section-title"><i class="bi bi-box-fill"></i> Product Details</div>
                @endif

                @if($normalAllocationEdit)
                <div class="section-card">
                    <div class="section-title"><i class="bi bi-diagram-3-fill"></i> Invoice Allocation</div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Allocation Method</label>
                        <select name="allocation_mode" id="allocation_mode" class="form-select" required>
                            <option value="PERCENTAGE" {{ old('allocation_mode', $invoice->allocation_mode) === 'PERCENTAGE' ? 'selected' : '' }}>By Payment Percentage</option>
                            <option value="ITEM_QTY" {{ old('allocation_mode', $invoice->allocation_mode) === 'ITEM_QTY' ? 'selected' : '' }}>By Item / Quantity</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="percentageStrategyInfo">
                        <div class="info-box mb-0">Payment Percentage is calculated from the original PO commercial amount.</div>
                    </div>
                </div>
                </div>
                <div class="section-card" id="productDetailsCard">
                    <div class="section-title"><i class="bi bi-box-fill"></i> Product Details</div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark"><tr><th class="text-center">Select</th><th>Product</th><th class="text-center">Qty PO</th><th class="text-center">Qty Invoice</th><th class="text-end">Unit Price</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                        @foreach($invoice->poCustomer->details as $index => $poDetail)
                            @php
                                $balance = $allocationSnapshot['lines']->firstWhere('po_customer_detail_id', $poDetail->id);
                                $currentQty = $invoice->details->where('po_customer_detail_id', $poDetail->id)->sum('quantity');
                                $qty = old("details.$index.quantity", $currentQty);
                            @endphp
                            <tr class="allocation-row" data-price="{{ $poDetail->unit_price }}" data-available="{{ $balance['remaining_quantity'] }}">
                                <td class="text-center"><input type="checkbox" class="form-check-input allocation-select" {{ $qty > 0 ? 'checked' : '' }}></td>
                                <td>{{ $poDetail->product->name ?? '' }}<br><small class="text-muted">{{ $poDetail->product->product_code ?? '' }} — {{ $poDetail->product->brand ?? '-' }}</small></td>
                                <td class="text-center">{{ $poDetail->quantity }} {{ $poDetail->product->unit ?? '' }}<br><small class="text-muted">Available: {{ $balance['remaining_quantity'] }}</small></td>
                                <td>
                                    <input type="hidden" class="allocation-identity" name="details[{{ $index }}][po_customer_detail_id]" value="{{ $poDetail->id }}">
                                    <input type="number" name="details[{{ $index }}][quantity]" class="form-control allocation-qty text-end" value="{{ $qty }}" min="0" max="{{ $balance['remaining_quantity'] }}" step="1">
                                </td>
                                <td><input class="form-control text-end" value="{{ number_format($poDetail->unit_price, 0, ',', '.') }}" readonly></td>
                                <td class="allocation-line-total text-end text-nowrap">{{ number_format($poDetail->quantity * $poDetail->unit_price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end">Subtotal</th>
                                    <th class="text-end" id="subtotal-text">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Discount (%)</th>
                                    <td>
                                        <input type="number" name="discount_percent" id="discount_percent"
                                               class="form-control text-end" step="0.5" min="0" max="100" value="{{ old('discount_percent', $invoice->discount_percent ?? 0) }}">
                                    </td>
                                    <th class="text-end" id="discount-text">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Payment Percentage (%)</th>
                                    <td>
                                        <input type="number" name="dp_percent" id="dp_percent"
                                               class="form-control text-end" step="any" min="0.000001" max="100" value="{{ old('dp_percent', $invoice->dp_percent ?? 100) }}">
                                    </td>
                                    <th class="text-end" id="dp-text">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="5" class="text-end">DPP Lain (11/12 × DP)</th>
                                    <th class="text-end" id="dpplain-text">Rp 0</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">PPN (%)</th>
                                    <td>
                                        <input type="number" name="tax_percent" id="tax_percent"
                                               class="form-control text-end" step="0.5" min="0" max="100" value="{{ old('tax_percent', $invoice->tax_percent ?? 11) }}">
                                    </td>
                                    <th class="text-end" id="tax-text">Rp 0</th>
                                </tr>
                                <tr id="pph23_row" class="{{ old('pph23_percent', $invoice->pph23_percent) > 0 ? '' : 'bgs-hidden' }}">
                                    <th colspan="4" class="text-end">PPH23 (%)</th>
                                    <td>
                                        <input type="number"
                                               name="pph23_percent" id="pph23_percent"
                                               class="form-control text-end"
                                               step="0.01"
                                               min="0"
                                               max="100"
                                               value="{{ old('pph23_percent', $invoice->pph23_percent ?? 0) }}">
                                    </td>
                                    <th class="text-end" id="pph23-text">Rp 0</th>
                                </tr>
                                <tr class="table-primary">
                                    <th colspan="5" class="text-end">Grand Total</th>
                                    <th class="text-end"><strong id="grand-total-text">Rp 0</strong></th>
                                </tr>
                                <tr>
                                    <th colspan="5" class="text-end">Remaining PO Amount Before Tax</th>
                                    <th class="text-end" id="remaining-text">Rp 0</th>
                                </tr>
                            </tfoot>
                    </table>
                </div>
                @else
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
                                    class="form-control qty-input readonly-bg"
                                    data-row-id="{{ $index }}"
                                    value="{{ $detail->quantity }}"
                                    min="1" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Price</label>
                                <input type="text"
                                    class="form-control price-display readonly-bg"
                                    data-row-id="{{ $index }}"
                                    value="{{ number_format($detail->unit_price, 0, ',', '.') }}"
                                    readonly>
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
                @endif

            </div>

            {{-- ========================================================= --}}
            {{-- BAGIAN 6: SUMMARY --}}
            {{-- ========================================================= --}}
            @if(!$normalAllocationEdit)
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
                                        class="form-control form-control-sm text-end bgs-u-341b166096f4"

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
                                        class="form-control form-control-sm text-end bgs-u-15c93980da4c"

                                        value="{{ old('dp_percent', $invoice->dp_percent ?? 100) }}"
                                        min="0.000001" max="100" step="any">
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
                                        class="form-control form-control-sm text-end bgs-u-e9c46ed6e89f"

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
                            <div class="row-item {{ $invoice->pph23_percent > 0 ? '' : 'bgs-hidden' }}" id="pph23_row">
                                <span class="label">PPH23 (%)</span>
                                <span class="value">
                                    <input type="number" name="pph23_percent" id="pph23_percent"
                                        class="form-control form-control-sm text-end bgs-u-28d27d979dee"

                                        value="{{ old('pph23_percent', $invoice->pph23_percent ?? 0) }}"
                                        min="0" max="100" step="0.5">
                                </span>
                            </div>
                            <div class="row-item {{ $invoice->pph23_percent > 0 ? '' : 'bgs-hidden' }}" id="pph23_amount_row">
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

            @endif

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
    <label class="form-label">Invoice Status</label>
    <input type="text"
           class="form-control"
           value="{{ ucfirst($invoice->status ?? '-') }}"
           readonly>
    <div class="form-text">
        Invoice status is calculated automatically from the remaining PO amount.
    </div>
</div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select" required>
    <option value="sent" selected>Sent</option>

</select>
<div class="form-text">
    Gunakan menu Payment Customer untuk konfirmasi pelunasan. Invoice Paid terkunci.
</div>
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
    // BGS invoice edit Select2 focus
    $('.select2-tags').on('select2:open.bgsInvoiceFocus', function () {
        const instance = $(this).data('select2');
        setTimeout(function () {
            if (!instance || !instance.isOpen()) return;
            const search = instance.dropdown && instance.dropdown.$search;
            if (search && search.length) {
                search[0].focus();
            }
        }, 50);
    });

    let rowCounter = {{ count($invoice->details) }};
    const normalAllocationEdit = @json($normalAllocationEdit);
    const savedAllocationMode = @json($invoice->allocation_mode);
    const poAmount = @json($allocationSnapshot['amount']['po_amount'] ?? 0);
    const availableAmount = @json($allocationSnapshot['amount']['remaining_amount'] ?? 0);


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

        const allocationMode = normalAllocationEdit ? $('#allocation_mode').val() : savedAllocationMode;
        let dpAmount = subtotal * dpPercent / 100;
        if (normalAllocationEdit) {
            $('#percentageStrategyInfo').toggle(allocationMode === 'PERCENTAGE');
            if (allocationMode === 'PERCENTAGE') {
                subtotal = poAmount;
                $('#dp_percent').prop('readonly', false);
                $('.allocation-qty, .allocation-select, .allocation-identity').prop('disabled', true);
                $('.allocation-line-total').text('Rp 0');
                dpAmount = Math.round(poAmount * dpPercent) / 100;
            } else {
                subtotal = 0;
                $('.allocation-row').each(function () {
                    const row = $(this);
                    const qty = row.find('.allocation-qty');
                    qty.prop('disabled', false);
                    row.find('.allocation-identity').prop('disabled', false);
                    const selected = row.find('.allocation-select');
                    selected.prop('disabled', false);
                    if (!selected.prop('checked')) qty.val(0);
                    qty.prop('readonly', false);
                    const lineTotal = (Number(qty.val()) || 0) * Number(row.data('price'));
                    subtotal += lineTotal;
                    row.find('.allocation-line-total').text(formatRupiah(lineTotal));
                });
                dpPercent = 100;
                $('#dp_percent').val(100).prop('readonly', true);
                dpAmount = subtotal;
            }
        }
        let baseAmount = normalAllocationEdit ? dpAmount : ((dpAmount > 0) ? dpAmount : subtotal);
        let discountAmount = baseAmount * discountPercent / 100;
        if (normalAllocationEdit) discountAmount = Math.round(discountAmount * 100) / 100;
        let dpp = baseAmount - discountAmount;
        let taxAmount = dpp * taxPercent / 100;
        let pph23Amount = dpp * pph23Percent / 100;
        if (normalAllocationEdit) {
            taxAmount = Math.round(taxAmount * 100) / 100;
            pph23Amount = Math.round(pph23Amount * 100) / 100;
        }
        let grandTotal = dpp + taxAmount - pph23Amount;
        if (normalAllocationEdit) grandTotal = Math.round(grandTotal * 100) / 100;
        let remaining = normalAllocationEdit ? Math.max(0, availableAmount - baseAmount) : ((dpAmount > 0) ? (subtotal - dpAmount) : 0);

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

    }

    // =========================================================
    // EVENT LISTENERS
    // =========================================================
    $('#type').change(calculateGrandTotal);
    $('.allocation-qty').on('input change', calculateGrandTotal);
    $('#allocation_mode').on('change', calculateGrandTotal);
    $('.allocation-select').on('change', function () {
        const row = $(this).closest('.allocation-row');
        if ($(this).is(':checked') && !(Number(row.find('.allocation-qty').val()) > 0)) row.find('.allocation-qty').val(Number(row.data('available')));
        calculateGrandTotal();
    });

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
        if (normalAllocationEdit) return;
        let terms = $(this).val() || '';
        let match = terms.match(/(\d+)%/);
        if (match) {
            $('#dp_percent').val(match[1]);
        } else {
            $('#dp_percent').val(100);
        }
        calculateGrandTotal();
    });

    $('#dp_percent, #discount_percent, #tax_percent, #pph23_percent').on('input change', calculateGrandTotal);

    // =========================================================
    // PRODUCT SELECT CHANGE
    // =========================================================
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