@extends('layouts.app')

@section('title', 'Edit PO Supplier')

@section('content')

<style>
    body {
        background-color: #f1f5f9;
    }

    .main-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        background: #fff;
        overflow: hidden;
    }

    .card-header-custom {
        background: linear-gradient(
            135deg,
            #f97316,
            #c2410c
        );
        color: white;
        padding: 1.5rem;
        border: none;
    }

    .card-header-custom h4 {
        color: white;
        font-weight: 700;
        margin-bottom: 0;
    }

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

    .info-group-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        height: 100%;
    }

    .form-control,
    .form-select {
        border-color: #cbd5e1;
        padding: 0.5rem 0.75rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #f97316;
        box-shadow:
            0 0 0 3px
            rgba(249, 115, 22, 0.15);
    }

    .readonly-bg {
        background-color: #f1f5f9 !important;
        color: #475569;
        font-weight: 500;
    }

    .product-row {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 18px;
        transition: all 0.2s;
    }

    .product-row:hover {
        border-color: #cbd5e1;
        box-shadow:
            0 4px 12px
            rgba(0, 0, 0, 0.03);
    }

    .price-display,
    .qty-input,
    .subtotal-display,
    .summary-input-num {
        text-align: right;
    }

    .table-summary th {
        font-size: 0.85rem;
        text-transform: uppercase;
        color: #475569;
        vertical-align: middle;
    }
</style>


<div class="container-fluid py-4">

    <div class="card main-card">

        {{-- =====================================================
             HEADER — PO CUSTOMER EDIT BASELINE
        ====================================================== --}}
        <div
            class="card-header-custom
                   d-flex
                   justify-content-between
                   align-items-center"
        >
            <h4>
                <i class="bi bi-pencil-square me-2"></i>
                Edit PO Supplier
            </h4>

            <a
                href="{{ route('po-suppliers.index') }}"
                class="btn btn-light btn-sm fw-bold px-3"
            >
                <i class="bi bi-arrow-left me-1"></i>
                Back to List
            </a>
        </div>


        <div class="card-body p-4">

            {{-- =================================================
                 ALERTS
            ================================================== --}}
            @if ($errors->any())
                <div
                    class="alert alert-danger border-0 shadow-sm
                           d-flex fade show mb-4"
                    style="
                        background-color:#fef2f2;
                        color:#991b1b;
                        border-left:4px solid #dc2626 !important;
                        border-radius:6px;
                    "
                >
                    <div class="me-2">
                        <i
                            class="bi bi-exclamation-triangle-fill fs-5"
                        ></i>
                    </div>

                    <div>
                        <strong class="d-block mb-1">
                            Please fix the following validation errors:
                        </strong>

                        <ul class="mb-0 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <button
                        type="button"
                        class="btn-close ms-auto"
                        data-bs-dismiss="alert"
                    ></button>
                </div>
            @endif


            @if(session('success'))
                <div
                    class="alert alert-success border-0 shadow-sm
                           d-flex fade show mb-4"
                    style="
                        background-color:#f0fdf4;
                        color:#166534;
                        border-left:4px solid #16a34a !important;
                        border-radius:6px;
                    "
                >
                    <i
                        class="bi bi-check-circle-fill me-2 fs-5"
                    ></i>

                    <div>
                        {{ session('success') }}
                    </div>

                    <button
                        type="button"
                        class="btn-close ms-auto"
                        data-bs-dismiss="alert"
                    ></button>
                </div>
            @endif


            @if(session('error'))
                <div
                    class="alert alert-danger border-0 shadow-sm mb-4"
                    style="
                        background-color:#fef2f2;
                        color:#991b1b;
                        border-left:4px solid #dc2626 !important;
                    "
                >
                    <i
                        class="bi bi-exclamation-circle-fill me-2"
                    ></i>

                    {{ session('error') }}
                </div>
            @endif


            <form
                action="{{ route(
                    'po-suppliers.update',
                    $poSupplier->id
                ) }}"
                method="POST"
                id="po-form"
            >

                @csrf
                @method('PUT')


                {{-- =================================================
                     HEADER / RELATIONSHIP
                ================================================== --}}
                <div class="row g-4 mb-4">

                    <div class="col-md-6">

                        <div class="info-group-box">

                            <div class="section-sub-title">
                                <i class="bi bi-diagram-3-fill me-2"></i>
                                1. Customer & PO Relationship
                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Customer
                                </label>

                                <input
                                    type="text"
                                    class="form-control readonly-bg"
                                    value="{{
                                        ($poSupplier->customer->customer_code ?? '')
                                        .
                                        ' - '
                                        .
                                        ($poSupplier->customer->name ?? '-')
                                    }}"
                                    readonly
                                >

                            </div>


                            <div class="mb-0">

                                <label class="form-label">
                                    PO Customer
                                </label>

                                <input
                                    type="text"
                                    class="form-control readonly-bg"
                                    value="{{
                                        $poSupplier->poCustomer->po_number
                                        ?? '-'
                                    }}"
                                    readonly
                                >

                            </div>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="info-group-box">

                            <div class="section-sub-title">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                2. Supplier PO Registry
                            </div>


                            <div class="row g-3">

                                <div class="col-12">

                                    <label class="form-label">
                                        PO Supplier Number
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control readonly-bg"
                                        value="{{
                                            $poSupplier->po_supplier_number
                                        }}"
                                        readonly
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Supplier
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control readonly-bg"
                                        value="{{
                                            ($poSupplier->supplier->supplier_code ?? '')
                                            .
                                            ' - '
                                            .
                                            ($poSupplier->supplier->name ?? '-')
                                        }}"
                                        readonly
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        PO Date
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control readonly-bg"
                                        value="{{
                                            $poSupplier->po_date
                                        }}"
                                        readonly
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        PO Status
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control readonly-bg"
                                        value="{{
                                            ucfirst(
                                                $poSupplier->status
                                            )
                                        }}"
                                        readonly
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Receipt Status
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control readonly-bg"
                                        value="{{
                                            ucfirst(
                                                $poSupplier->receipt_status
                                                ?? 'pending'
                                            )
                                        }}"
                                        readonly
                                    >

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     PRODUCT DETAILS
                ================================================== --}}
                <div class="info-group-box mb-4">

                    <div
                        class="section-sub-title
                               d-flex
                               justify-content-between
                               align-items-center
                               text-dark
                               w-100"
                    >
                        <span>
                            <i class="bi bi-box-seam-fill me-2"></i>
                            3. Product Detail
                        </span>

                        <button
                            type="button"
                            class="btn btn-success btn-sm fw-bold px-3 ms-auto"
                            id="add-product"
                            @if(
                                $eligiblePoCustomerDetails->isEmpty()
                            )
                                disabled
                                title="Tidak ada remaining PO Customer item"
                            @endif
                        >
                            <i
                                class="bi bi-plus-circle-fill me-1"
                            ></i>

                            Add Product Row
                        </button>
                    </div>


                    <div
                        id="products-container"
                        class="d-flex flex-column gap-3 mb-3"
                    >

                        @foreach(
                            $poSupplier->details
                            as $index => $detail
                        )

                            <div
                                class="product-row position-relative"
                                id="row-{{ $index }}"
                            >

                                <div class="row g-2 text-start">

                                    <div class="col-md-2">

                                        <label class="form-label small">
                                            Brand
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control
                                                   form-control-sm
                                                   readonly-bg"
                                            value="{{
                                                $detail->product->brand
                                                ?? '-'
                                            }}"
                                            readonly
                                        >

                                    </div>


                                    <div class="col-md-4">

                                        <label class="form-label small">
                                            Product
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control
                                                   form-control-sm
                                                   readonly-bg"
                                            value="{{
                                                (
                                                    $detail->product->product_code
                                                    ?? ''
                                                )
                                                .
                                                ' - '
                                                .
                                                (
                                                    $detail->product->name
                                                    ?? ''
                                                )
                                            }}"
                                            readonly
                                        >

                                        <input
                                            type="hidden"
                                            name="items[{{ $index }}][po_customer_detail_id]"
                                            value="{{
                                                $detail->po_customer_detail_id
                                            }}"
                                        >

                                        <input
                                            type="hidden"
                                            name="items[{{ $index }}][product_id]"
                                            value="{{
                                                $detail->product_id
                                            }}"
                                        >

                                    </div>


                                    <div class="col-md-1">

                                        <label class="form-label small">
                                            Qty
                                        </label>

                                        <input
                                            type="number"
                                            name="items[{{ $index }}][quantity]"
                                            class="form-control
                                                   form-control-sm
                                                   qty-input"
                                            data-row="{{ $index }}"
                                            value="{{
                                                $detail->quantity
                                            }}"
                                            min="0.0001"
                                            step="any"
                                            required
                                        >

                                    </div>


                                    <div class="col-md-2">

                                        <label class="form-label small">
                                            Purchase Price (Rp)
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control
                                                   form-control-sm
                                                   price-display"
                                            data-row="{{ $index }}"
                                            data-original-master-price="{{
                                                $detail->product->purchase_price
                                                ?? 0
                                            }}"
                                            data-product-name="{{
                                                ($detail->product->product_code ?? '')
                                                .
                                                ' - '
                                                .
                                                ($detail->product->name ?? '')
                                            }}"
                                            value="{{
                                                number_format(
                                                    $detail->purchase_price,
                                                    0,
                                                    ',',
                                                    '.'
                                                )
                                            }}"
                                            required
                                        >

                                        <input
                                            type="hidden"
                                            name="items[{{ $index }}][purchase_price]"
                                            class="price-hidden"
                                            value="{{
                                                $detail->purchase_price
                                            }}"
                                        >

                                        <input
                                            type="hidden"
                                            name="items[{{ $index }}][master_price_decision]"
                                            class="master-price-decision"
                                            value=""
                                        >

                                    </div>


                                    <div class="col-md-2">

                                        <label class="form-label small">
                                            Subtotal (Rp)
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control
                                                   form-control-sm
                                                   subtotal-display
                                                   readonly-bg"
                                            value="{{
                                                number_format(
                                                    $detail->subtotal,
                                                    0,
                                                    ',',
                                                    '.'
                                                )
                                            }}"
                                            data-value="{{
                                                $detail->subtotal
                                            }}"
                                            readonly
                                        >

                                    </div>


                                    <div
                                        class="col-md-1
                                               d-flex
                                               align-items-end
                                               justify-content-center"
                                    >

                                        <button
                                            type="button"
                                            class="btn
                                                   btn-outline-danger
                                                   btn-sm
                                                   remove-product
                                                   w-100"
                                            data-row="{{ $index }}"
                                            title="Remove Item"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>

                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>


                {{-- =================================================
                     REMARKS + FINANCIAL SUMMARY
                ================================================== --}}
                <div class="row g-4 text-start">

                    <div class="col-md-5">

                        <div class="info-group-box">

                            <div class="section-sub-title">
                                <i
                                    class="bi bi-chat-right-quote-fill me-2"
                                ></i>

                                4. Additional Data
                            </div>


                            <div class="mb-0">

                                <label class="form-label">
                                    Remarks
                                </label>

                                <textarea
                                    name="notes"
                                    class="form-control"
                                    rows="5"
                                    placeholder="Write remarks or instructions..."
                                >{{ old(
                                    'notes',
                                    $poSupplier->notes
                                ) }}</textarea>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-7">

                        <div
                            class="border rounded-3
                                   overflow-hidden shadow-sm"
                        >

                            <table
                                class="table
                                       table-bordered
                                       table-summary
                                       m-0
                                       bg-white
                                       align-middle"
                            >

                                <tr>

                                    <th
                                        width="45%"
                                        class="ps-3"
                                    >
                                        Subtotal Items Amount
                                    </th>

                                    <td
                                        colspan="2"
                                        class="text-end pe-3
                                               fw-bold text-dark"
                                    >
                                        <span id="subtotal-text">
                                            Rp 0
                                        </span>
                                    </td>

                                </tr>


                                <tr>

                                    <th class="ps-3">
                                        Discount Allowance
                                    </th>

                                    <td
                                        width="25%"
                                        class="px-2"
                                    >

                                        <div
                                            class="input-group
                                                   input-group-sm"
                                        >

                                            <input
                                                type="number"
                                                name="discount_percent"
                                                id="discount_percent"
                                                class="form-control
                                                       summary-input-num"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                value="{{
                                                    old(
                                                        'discount_percent',
                                                        $poSupplier->discount_percent
                                                        ?? 0
                                                    )
                                                }}"
                                            >

                                            <span
                                                class="input-group-text"
                                            >
                                                %
                                            </span>

                                        </div>

                                    </td>


                                    <td
                                        width="30%"
                                        class="text-end pe-3"
                                    >
                                        <span id="discount-text">
                                            Rp 0
                                        </span>
                                    </td>

                                </tr>


                                <tr
                                    style="
                                        background-color:#f8fafc;
                                    "
                                >

                                    <th
                                        class="ps-3
                                               fw-semibold
                                               text-secondary"
                                    >
                                        Subtotal After Discount
                                    </th>

                                    <td
                                        colspan="2"
                                        class="text-end pe-3
                                               fw-bold text-secondary"
                                    >
                                        <span id="after-discount-text">
                                            Rp 0
                                        </span>
                                    </td>

                                </tr>


                                <tr>

                                    <th class="ps-3">
                                        VAT
                                    </th>

                                    <td class="px-2">

                                        <div
                                            class="input-group
                                                   input-group-sm"
                                        >

                                            <input
                                                type="number"
                                                id="tax_percent"
                                                class="form-control
                                                       readonly-bg
                                                       summary-input-num"
                                                value="{{
                                                    $poSupplier->tax_percent
                                                    ?? 0
                                                }}"
                                                readonly
                                            >

                                            <span
                                                class="input-group-text"
                                            >
                                                %
                                            </span>

                                        </div>

                                    </td>


                                    <td
                                        class="text-end pe-3"
                                    >
                                        <span id="tax-text">
                                            Rp 0
                                        </span>
                                    </td>

                                </tr>


                                <tr
                                    style="
                                        border-top:
                                        2px solid #cbd5e1;
                                    "
                                >

                                    <th
                                        class="ps-3
                                               fs-6
                                               text-dark"
                                    >
                                        <strong>
                                            Grand Total
                                        </strong>
                                    </th>

                                    <td
                                        colspan="2"
                                        class="text-end
                                               pe-3
                                               fs-5
                                               text-success
                                               fw-bold"
                                    >
                                        <strong>
                                            <span
                                                id="grand-total-text"
                                            >
                                                Rp 0
                                            </span>
                                        </strong>
                                    </td>

                                </tr>

                            </table>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     ACTION
                ================================================== --}}
                <div
                    class="col-12 mt-4 pt-3 border-top
                           d-flex gap-2"
                >

                    <button
                        type="submit"
                        class="btn btn-warning
                               px-4 py-2 fw-bold text-white"
                        style="
                            background:
                            linear-gradient(
                                135deg,
                                #f97316,
                                #c2410c
                            );
                            border:none;
                        "
                    >
                        <i
                            class="bi bi-save-fill me-1"
                        ></i>

                        Update PO Supplier
                    </button>


                    <a
                        href="{{ route('po-suppliers.index') }}"
                        class="btn
                               btn-outline-secondary
                               px-4 py-2"
                    >
                        Discard Changes
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>



<!-- =========================================================
     PURCHASE PRICE DIFFERENCE DECISION
========================================================== -->
<div class="modal fade"
     id="masterPriceDecisionModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Purchase Price Differences
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>

            <div class="modal-body">

                <div class="alert alert-info">

                    Purchase Price PO Supplier berbeda dari
                    Purchase Price pada Product Master.

                    Pilih keputusan untuk setiap item.
                    Harga transaksi PO Supplier tetap memakai
                    harga yang Anda masukkan.

                </div>

                <div class="table-responsive">

                    <table class="table table-bordered align-middle">

                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">
                                    Master Price
                                </th>
                                <th class="text-end">
                                    PO Supplier Price
                                </th>
                                <th style="min-width:220px;">
                                    Decision
                                </th>
                            </tr>
                        </thead>

                        <tbody id="masterPriceDecisionBody">
                        </tbody>

                    </table>

                </div>

                <div
                    id="masterPriceDecisionError"
                    class="alert alert-danger d-none"
                >
                    Pilih Update Master atau Keep Existing
                    untuk semua item yang berbeda harga.
                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Back
                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    id="continuePriceDecision"
                >
                    Continue
                </button>

            </div>

        </div>

    </div>

</div>


<script
    src="https://code.jquery.com/jquery-3.6.0.min.js"
></script>


<script>
$(document).ready(function() {

    let rowCount =
        {{ count($poSupplier->details) }};

    let eligiblePoCustomerDetails =
        @json($eligiblePoCustomerDetails);


    function formatRupiah(value) {

        if (
            value === null ||
            value === undefined ||
            isNaN(value)
        ) {
            return '0';
        }

        return new Intl.NumberFormat(
            'id-ID'
        ).format(
            Math.round(value)
        );
    }


    function parseRupiahToNumber(value) {

        if (!value) {
            return 0;
        }

        return parseFloat(
            value
                .toString()
                .replace(/\./g, '')
                .replace(/,/g, '.')
        ) || 0;
    }


    function calculateGrandTotal() {

        let subtotal = 0;

        $('.subtotal-display').each(
            function() {

                subtotal +=
                    parseFloat(
                        $(this).data('value')
                    ) || 0;
            }
        );

        let discountPercent =
            parseFloat(
                $('#discount_percent').val()
            ) || 0;

        let discountAmount =
            subtotal *
            (discountPercent / 100);

        let afterDiscount =
            subtotal -
            discountAmount;

        let taxPercent =
            parseFloat(
                $('#tax_percent').val()
            ) || 0;

        let taxAmount =
            afterDiscount *
            (taxPercent / 100);

        let grandTotal =
            afterDiscount +
            taxAmount;

        $('#subtotal-text')
            .text(
                'Rp ' +
                formatRupiah(subtotal)
            );

        $('#discount-text')
            .text(
                'Rp ' +
                formatRupiah(
                    discountAmount
                )
            );

        $('#after-discount-text')
            .text(
                'Rp ' +
                formatRupiah(
                    afterDiscount
                )
            );

        $('#tax-text')
            .text(
                'Rp ' +
                formatRupiah(
                    taxAmount
                )
            );

        $('#grand-total-text')
            .text(
                'Rp ' +
                formatRupiah(
                    grandTotal
                )
            );
    }


    function calculateSubtotal(rowId) {

        let row =
            $('#row-' + rowId);

        let qty =
            parseFloat(
                row
                    .find('.qty-input')
                    .val()
            ) || 0;

        let price =
            parseRupiahToNumber(
                row
                    .find('.price-display')
                    .val()
            );

        let subtotal =
            qty *
            price;

        row
            .find('.price-hidden')
            .val(price);

        row
            .find('.subtotal-display')
            .val(
                formatRupiah(
                    subtotal
                )
            )
            .data(
                'value',
                subtotal
            );

        calculateGrandTotal();
    }


    function buildEligibleOptions() {

        let options =
            '<option value="">' +
            '-- Choose PO Customer Item --' +
            '</option>';

        eligiblePoCustomerDetails.forEach(
            function(detail) {

                if (!detail.product) {
                    return;
                }

                let remaining =
                    parseFloat(
                        detail.remaining_quantity
                        || 0
                    );

                if (remaining <= 0) {
                    return;
                }

                let product =
                    detail.product;

                options +=
                    '<option ' +
                    'value="' +
                    detail.id +
                    '" ' +
                    'data-product-id="' +
                    product.id +
                    '" ' +
                    'data-price="' +
                    (
                        product.purchase_price
                        || 0
                    ) +
                    '" ' +
                    'data-brand="' +
                    (
                        product.brand
                        || '-'
                    ) +
                    '" ' +
                    'data-product-name="' +
                    (
                        (
                            product.product_code
                            || ''
                        )
                        +
                        ' - '
                        +
                        (
                            product.name
                            || ''
                        )
                    ) +
                    '" ' +
                    'data-remaining="' +
                    remaining +
                    '">' +
                    (
                        product.product_code
                        || ''
                    ) +
                    ' - ' +
                    (
                        product.name
                        || ''
                    ) +
                    ' | Remaining: ' +
                    remaining +
                    '</option>';
            }
        );

        return options;
    }


    function addNewRow() {

        let options =
            buildEligibleOptions();

        let newRow =
            `
            <div
                class="product-row position-relative"
                id="row-${rowCount}"
            >

                <div class="row g-2 text-start">

                    <div class="col-md-2">

                        <label class="form-label small">
                            Brand
                        </label>

                        <input
                            type="text"
                            class="form-control
                                   form-control-sm
                                   brand-display
                                   readonly-bg"
                            value="-"
                            readonly
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label small">
                            PO Customer Item
                        </label>

                        <select
                            name="items[${rowCount}][po_customer_detail_id]"
                            class="form-select
                                   form-select-sm
                                   po-customer-detail-select"
                            data-row="${rowCount}"
                            required
                        >
                            ${options}
                        </select>

                        <input
                            type="hidden"
                            name="items[${rowCount}][product_id]"
                            class="product-id-hidden"
                            value=""
                        >

                    </div>


                    <div class="col-md-1">

                        <label class="form-label small">
                            Qty
                        </label>

                        <input
                            type="number"
                            name="items[${rowCount}][quantity]"
                            class="form-control
                                   form-control-sm
                                   qty-input"
                            data-row="${rowCount}"
                            value="1"
                            min="0.0001"
                            step="any"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <label class="form-label small">
                            Purchase Price (Rp)
                        </label>

                        <input
                            type="text"
                            class="form-control
                                   form-control-sm
                                   price-display"
                            data-row="${rowCount}"
                            data-original-master-price="0"
                            data-product-name=""
                            value=""
                            required
                        >

                        <input
                            type="hidden"
                            name="items[${rowCount}][purchase_price]"
                            class="price-hidden"
                            value="0"
                        >

                        <input
                            type="hidden"
                            name="items[${rowCount}][master_price_decision]"
                            class="master-price-decision"
                            value=""
                        >

                    </div>


                    <div class="col-md-2">

                        <label class="form-label small">
                            Subtotal (Rp)
                        </label>

                        <input
                            type="text"
                            class="form-control
                                   form-control-sm
                                   subtotal-display
                                   readonly-bg"
                            value="0"
                            data-value="0"
                            readonly
                        >

                    </div>


                    <div
                        class="col-md-1
                               d-flex
                               align-items-end
                               justify-content-center"
                    >

                        <button
                            type="button"
                            class="btn
                                   btn-outline-danger
                                   btn-sm
                                   remove-product
                                   w-100"
                            data-row="${rowCount}"
                        >
                            <i class="bi bi-trash"></i>
                        </button>

                    </div>

                </div>

            </div>
            `;

        $('#products-container')
            .append(newRow);

        rowCount++;
    }


    $('.subtotal-display').each(
        function() {

            let value =
                parseFloat(
                    $(this).attr(
                        'data-value'
                    )
                ) || 0;

            $(this).data(
                'value',
                value
            );
        }
    );


    calculateGrandTotal();


    $(document).on(
        'change',
        '.po-customer-detail-select',
        function() {

            let rowId =
                $(this).data('row');

            let row =
                $('#row-' + rowId);

            let selected =
                $(this)
                    .find(':selected');

            let productId =
                selected.data(
                    'product-id'
                ) || '';

            let price =
                parseFloat(
                    selected.data(
                        'price'
                    )
                ) || 0;

            let brand =
                selected.data(
                    'brand'
                ) || '-';

            let remaining =
                parseFloat(
                    selected.data(
                        'remaining'
                    )
                ) || 0;

            let productName =
                selected.data(
                    'product-name'
                ) || '';

            row
                .find(
                    '.price-display'
                )
                .attr(
                    'data-original-master-price',
                    price
                )
                .attr(
                    'data-product-name',
                    productName
                );

            row
                .find(
                    '.master-price-decision'
                )
                .val('');

            row
                .find(
                    '.product-id-hidden'
                )
                .val(
                    productId
                );

            row
                .find(
                    '.brand-display'
                )
                .val(
                    brand
                );

            row
                .find(
                    '.qty-input'
                )
                .attr(
                    'max',
                    remaining
                )
                .attr(
                    'data-remaining',
                    remaining
                )
                .val(
                    Math.min(
                        1,
                        remaining
                    )
                );

            row
                .find(
                    '.price-display'
                )
                .val(
                    price > 0
                        ?
                        formatRupiah(
                            price
                        )
                        :
                        ''
                );

            row
                .find(
                    '.price-hidden'
                )
                .val(
                    price
                );

            calculateSubtotal(
                rowId
            );
        }
    );


    $(document).on(
        'keyup change',
        '.price-display',
        function() {

            let rowId =
                $(this).data('row');

            let numericValue =
                parseRupiahToNumber(
                    $(this).val()
                );

            $('#row-' + rowId)
                .find(
                    '.master-price-decision'
                )
                .val('');

            if (numericValue > 0) {

                $(this).val(
                    formatRupiah(
                        numericValue
                    )
                );

            } else if (
                $(this).val() === ''
            ) {

                $(this).val('');
            }

            calculateSubtotal(
                rowId
            );
        }
    );


    $(document).on(
        'keyup change',
        '.qty-input',
        function() {

            let rowId =
                $(this).data('row');

            calculateSubtotal(
                rowId
            );
        }
    );


    $('#discount_percent')
        .on(
            'input change',
            calculateGrandTotal
        );


    $('#add-product')
        .on(
            'click',
            function() {
                addNewRow();
            }
        );


    $(document).on(
        'click',
        '.remove-product',
        function() {

            let rowId =
                $(this).data('row');

            $('#row-' + rowId)
                .remove();

            calculateGrandTotal();
        }
    );


    let allowFinalSubmit = false;

    let masterPriceDecisionModal = null;


    function collectMasterPriceDifferences() {

        let differences = [];

        $('.product-row').each(
            function() {

                let row =
                    $(this);

                let priceInput =
                    row.find(
                        '.price-display'
                    );

                let transactionPrice =
                    parseRupiahToNumber(
                        priceInput.val()
                    );

                let masterPrice =
                    parseFloat(
                        priceInput.attr(
                            'data-original-master-price'
                        )
                    ) || 0;

                let productName =
                    priceInput.attr(
                        'data-product-name'
                    ) || 'Product';

                let decisionInput =
                    row.find(
                        '.master-price-decision'
                    );

                /*
                 * priceDecision is intentionally recalculated
                 * from the latest transaction price before save.
                 */
                let priceDecision =
                    decisionInput.val() || '';

                if (
                    Math.abs(
                        transactionPrice -
                        masterPrice
                    ) > 0.009
                ) {

                    differences.push({
                        row:
                            row,

                        productName:
                            productName,

                        masterPrice:
                            masterPrice,

                        transactionPrice:
                            transactionPrice,

                        decision:
                            priceDecision
                    });

                } else {

                    decisionInput.val('');
                }
            }
        );

        return differences;
    }


    function renderMasterPriceDifferences(
        differences
    ) {

        let body =
            $('#masterPriceDecisionBody');

        body.empty();

        differences.forEach(
            function(item, index) {

                let selectedDecision =
                    item.row
                        .find(
                            '.master-price-decision'
                        )
                        .val() || '';

                let html =
                    '<tr ' +
                    'data-difference-index="' +
                    index +
                    '">' +

                    '<td>' +
                    $('<div>')
                        .text(item.productName)
                        .html() +
                    '</td>' +

                    '<td class="text-end">' +
                    'Rp ' +
                    formatRupiah(
                        item.masterPrice
                    ) +
                    '</td>' +

                    '<td class="text-end">' +
                    'Rp ' +
                    formatRupiah(
                        item.transactionPrice
                    ) +
                    '</td>' +

                    '<td>' +
                    '<select ' +
                    'class="form-select form-select-sm ' +
                    'price-decision-select" ' +
                    'data-index="' +
                    index +
                    '">' +

                    '<option value="">' +
                    '-- Select Decision --' +
                    '</option>' +

                    '<option value="update"' +
                    (
                        selectedDecision ===
                        'update'
                            ? ' selected'
                            : ''
                    ) +
                    '>' +
                    'Update Master' +
                    '</option>' +

                    '<option value="keep"' +
                    (
                        selectedDecision ===
                        'keep'
                            ? ' selected'
                            : ''
                    ) +
                    '>' +
                    'Keep Existing' +
                    '</option>' +

                    '</select>' +
                    '</td>' +

                    '</tr>';

                body.append(html);
            }
        );

        body.data(
            'differences',
            differences
        );

        $('#masterPriceDecisionError')
            .addClass('d-none');
    }


    $('#po-form')
        .on(
            'submit',
            function(event) {

                $('.price-display')
                    .each(
                        function() {

                            let row =
                                $(this)
                                    .closest(
                                        '.product-row'
                                    );

                            row
                                .find(
                                    '.price-hidden'
                                )
                                .val(
                                    parseRupiahToNumber(
                                        $(this).val()
                                    )
                                );
                        }
                    );

                if (allowFinalSubmit) {

                    allowFinalSubmit =
                        false;

                    return true;
                }

                let differences =
                    collectMasterPriceDifferences();

                if (
                    differences.length === 0
                ) {
                    return true;
                }

                event.preventDefault();

                renderMasterPriceDifferences(
                    differences
                );

                if (!masterPriceDecisionModal) {

                    masterPriceDecisionModal =
                        new bootstrap.Modal(
                            document.getElementById(
                                'masterPriceDecisionModal'
                            )
                        );
                }

                masterPriceDecisionModal.show();

                return false;
            }
        );


    $(document).on(
        'change',
        '.price-decision-select',
        function() {

            let index =
                parseInt(
                    $(this).data('index'),
                    10
                );

            let differences =
                $('#masterPriceDecisionBody')
                    .data('differences')
                || [];

            if (!differences[index]) {
                return;
            }

            differences[index]
                .row
                .find(
                    '.master-price-decision'
                )
                .val(
                    $(this).val()
                );

            $('#masterPriceDecisionError')
                .addClass('d-none');
        }
    );


    $('#continuePriceDecision')
        .on(
            'click',
            function() {

                let differences =
                    collectMasterPriceDifferences();

                let missingDecision =
                    differences.some(
                        function(item) {

                            return ![
                                'update',
                                'keep'
                            ].includes(
                                item.row
                                    .find(
                                        '.master-price-decision'
                                    )
                                    .val()
                            );
                        }
                    );

                if (missingDecision) {

                    $('#masterPriceDecisionError')
                        .removeClass('d-none');

                    return;
                }

                allowFinalSubmit =
                    true;

                masterPriceDecisionModal.hide();

                document
                    .getElementById(
                        'po-form'
                    )
                    .requestSubmit();
            }
        );


    $('#masterPriceDecisionModal')
        .on(
            'hidden.bs.modal',
            function() {

                if (allowFinalSubmit) {
                    return;
                }

                /*
                 * Back / close resets decisions.
                 * The next Save recalculates against latest prices.
                 */
                $('.master-price-decision')
                    .val('');
            }
        );

});
</script>

@endsection
