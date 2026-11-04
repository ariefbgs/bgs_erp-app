@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-document-receipts-create bgs-mode-create')

@section('title', 'Create Document Receipt')

@section('content')





<div class="card main-card">

    <div class="card-header-custom bgs-page-header">
        <h4 class="mb-0">
            <i class="bi bi-file-earmark-plus-fill me-2"></i>
            Create Document Receipt
        </h4>
    </div>

    <div class="card-body p-4">

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Terjadi kesalahan.</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            id="receiptForm"
            action="{{ route('document-receipts.store') }}"
            method="POST"
            enctype="multipart/form-data"
        >

            @csrf

            <div class="row g-3 mb-4">

                <div class="col-md-8">

                    <label class="form-label fw-semibold">
                        PO Customer
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        name="po_customer_id"
                        id="po_customer_id"
                        class="form-control"
                        required
                    >

                        <option value=""></option>

                        @foreach($poCustomers as $po)

                            <option
                                value="{{ $po->id }}"
                                {{ (string) old('po_customer_id') === (string) $po->id ? 'selected' : '' }}
                            >
                                {{ $po->po_number }}
                                — {{ $po->customer->name ?? '-' }}
                            </option>

                        @endforeach

                    </select>

                    <div class="form-text">
                        Pilih Nomor PO Customer. Semua dokumen terkait
                        akan dimuat otomatis.
                    </div>

                </div>

                <div class="col-md-4">

                    <label class="form-label fw-semibold">
                        Receipt Date
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="date"
                        name="receipt_date"
                        class="form-control"
                        value="{{ old('receipt_date', date('Y-m-d')) }}"
                        required
                    >

                </div>

            </div>

            <div id="documentWorkspace" class="bgs-u-d0466aa33fa8">

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-light py-2">
                        <strong>PO Customer Information</strong>
                    </div>

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-md-3">
                                <label class="form-label small text-muted">
                                    PO Number
                                </label>
                                <input
                                    id="display_po_number"
                                    class="form-control readonly-value"
                                    readonly
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small text-muted">
                                    PO Date
                                </label>
                                <input
                                    id="display_po_date"
                                    class="form-control readonly-value"
                                    readonly
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small text-muted">
                                    Customer
                                </label>
                                <input
                                    id="display_customer"
                                    class="form-control readonly-value"
                                    readonly
                                >
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small text-muted">
                                    PO Status
                                </label>
                                <input
                                    id="display_po_status"
                                    class="form-control readonly-value"
                                    readonly
                                >
                            </div>

                        </div>

                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-light d-flex justify-content-between align-items-center">

                        <span class="section-title">
                            Delivery Orders
                        </span>

                        <span
                            id="doCount"
                            class="badge bg-primary"
                        >
                            0
                        </span>

                    </div>

                    <div class="card-body">

                        <div class="form-text mb-2">
                            Semua Delivery Order dari PO Customer
                            otomatis dipilih. Uncheck jika dokumen
                            tersebut tidak disertakan pada DR ini.
                        </div>

                        <div id="deliveryOrderRows"></div>

                    </div>

                </div>

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-header bg-light d-flex justify-content-between align-items-center">

                        <span class="section-title">
                            Sales Invoice & Faktur Pajak
                        </span>

                        <span
                            id="invoiceCount"
                            class="badge bg-primary"
                        >
                            0
                        </span>

                    </div>

                    <div class="card-body">

                        <div class="form-text mb-2">
                            Sales Invoice aktif dari PO Customer
                            otomatis dipilih. Faktur Pajak mengikuti
                            masing-masing Sales Invoice.
                        </div>

                        <div id="invoiceRows"></div>

                    </div>

                </div>

                <div class="row g-3 mb-3">

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Attachment
                        </label>

                        <input
                            type="file"
                            name="attachment"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.pdf"
                        >

                        <div class="form-text">
                            Maximum 5 MB.
                        </div>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Remarks
                        </label>

                        <textarea
                            name="notes"
                            class="form-control"
                            rows="3"
                        >{{ old('notes') }}</textarea>

                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">

                    <a
                        href="{{ route('document-receipts.index') }}"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        id="submitBtn"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-save-fill me-1"></i>
                        Save Document Receipt
                    </button>

                </div>

            </div>

        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function () {

    const oldDoIds = @json(
        array_map(
            'strval',
            (array) old('delivery_order_ids', [])
        )
    );

    const oldInvoiceIds = @json(
        array_map(
            'strval',
            (array) old('invoice_customer_ids', [])
        )
    );

    const $po = $('#po_customer_id');

    function esc(value) {
        return $('<div>')
            .text(value ?? '')
            .html();
    }

    function resetWorkspace() {

        $('#documentWorkspace').hide();

        $('#display_po_number').val('');
        $('#display_po_date').val('');
        $('#display_customer').val('');
        $('#display_po_status').val('');

        $('#deliveryOrderRows').empty();
        $('#invoiceRows').empty();

        $('#doCount').text('0');
        $('#invoiceCount').text('0');
    }

    function renderDeliveryOrders(rows) {

        let html = '';

        rows.forEach(function (row) {

            const id = String(row.id);

            const checked =
                oldDoIds.length
                    ? oldDoIds.includes(id)
                    : true;

            html += `
                <label class="document-row d-block">

                    <div class="form-check">

                        <input
                            class="form-check-input do-checkbox"
                            type="checkbox"
                            name="delivery_order_ids[]"
                            value="${esc(id)}"
                            ${checked ? 'checked' : ''}
                        >

                        <span class="form-check-label">

                            <strong>
                                ${esc(row.do_number)}
                            </strong>

                            <span class="badge bg-secondary ms-2">
                                ${esc(row.status || '')}
                            </span>

                            <div class="document-meta mt-1">
                                Delivery Date:
                                ${esc(row.delivery_date || '-')}
                                &nbsp; | &nbsp;
                                Receiver:
                                ${esc(row.receiver_name || '-')}
                            </div>

                        </span>

                    </div>

                </label>
            `;
        });

        $('#deliveryOrderRows').html(html);

        updateDocumentCounts();
    }

    function renderInvoices(rows) {

        let html = '';

        rows.forEach(function (row) {

            const id = String(row.id);

            const checked =
                oldInvoiceIds.length
                    ? oldInvoiceIds.includes(id)
                    : true;

            html += `
                <label class="document-row d-block">

                    <div class="form-check">

                        <input
                            class="form-check-input invoice-checkbox"
                            type="checkbox"
                            name="invoice_customer_ids[]"
                            value="${esc(id)}"
                            ${checked ? 'checked' : ''}
                        >

                        <span class="form-check-label">

                            <strong>
                                ${esc(row.invoice_number)}
                            </strong>

                            ${row.type
                                ? `<span class="text-muted ms-2">
                                    ${esc(row.type)}
                                   </span>`
                                : ''
                            }

                            <div class="document-meta mt-1">

                                Invoice Date:
                                ${esc(row.invoice_date || '-')}

                                &nbsp; | &nbsp;

                                Faktur Pajak:
                                <strong>
                                    ${esc(
                                        row.tax_invoice_number || '-'
                                    )}
                                </strong>

                                &nbsp; | &nbsp;

                                Payment:
                                ${esc(row.payment_status || '-')}

                            </div>

                        </span>

                    </div>

                </label>
            `;
        });

        $('#invoiceRows').html(html);

        updateDocumentCounts();
    }

    function updateDocumentCounts() {

        const totalDo =
            $('.do-checkbox').length;

        const selectedDo =
            $('.do-checkbox:checked').length;

        const totalInvoice =
            $('.invoice-checkbox').length;

        const selectedInvoice =
            $('.invoice-checkbox:checked').length;

        $('#doCount').text(
            selectedDo + ' / ' + totalDo + ' DO'
        );

        $('#invoiceCount').text(
            selectedInvoice + ' / ' + totalInvoice + ' Invoice'
        );
    }

    function loadPo(poId) {

        resetWorkspace();

        if (!poId) {
            return;
        }

        $.ajax({

            url:
                '{{ route("document-receipts.get-create-options-by-po", ["po_customer_id" => "__PO_ID__"]) }}'.replace('__PO_ID__', encodeURIComponent(poId)),

            type: 'GET',
            dataType: 'json',

            success: function (res) {

                $('#display_po_number')
                    .val(res.po_number || '-');

                $('#display_po_date')
                    .val(res.po_date || '-');

                $('#display_customer')
                    .val(res.customer_name || '-');

                $('#display_po_status')
                    .val(res.po_status || '-');

                renderDeliveryOrders(
                    res.delivery_orders || []
                );

                renderInvoices(
                    res.invoices || []
                );

                $('#documentWorkspace').show();
            },

            error: function (xhr) {

                console.error(
                    xhr.responseText
                );

                resetWorkspace();

                alert(
                    'Failed to load PO Customer documents.'
                );
            }

        });
    }

    $po.select2({
        width: '100%',
        placeholder:
            '-- Select PO Customer --',
        allowClear: true
    });

    $po.on('change', function () {
        loadPo(
            $(this).val()
        );
    });

    $(document).on(
        'change',
        '.do-checkbox, .invoice-checkbox',
        function () {
            updateDocumentCounts();
        }
    );

    $('#receiptForm').on(
        'submit',
        function (e) {

            const poId =
                $po.val();

            const doCount =
                $('.do-checkbox:checked')
                    .length;

            const invoiceCount =
                $('.invoice-checkbox:checked')
                    .length;

            if (!poId) {

                e.preventDefault();

                alert(
                    'Select PO Customer.'
                );

                return false;
            }

            if (doCount < 1) {

                e.preventDefault();

                alert(
                    'Select at least one Delivery Order.'
                );

                return false;
            }

            if (invoiceCount < 1) {

                e.preventDefault();

                alert(
                    'Select at least one Sales Invoice.'
                );

                return false;
            }

            $('#submitBtn')
                .prop('disabled', true)
                .html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
                );

            return true;
        }
    );

    if ($po.val()) {
        loadPo(
            $po.val()
        );
    }

});
</script>

@endsection
