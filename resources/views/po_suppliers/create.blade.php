@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-po-suppliers-create bgs-mode-create')
@section('title', 'Create PO Supplier')
@section('content')






<div class="card main-card">
    <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
        <h4><i class="bi bi-file-earmark-plus-fill me-2"></i>Create PO Supplier</h4>
    </div>
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>There is an error!</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4 bgs-u-be237583235b">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4 bgs-u-f812d16479e9">
            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
        </div>
    @endif 
    <div class="card-body p-4">
        <form action="{{ route('po-suppliers.store') }}" method="POST" id="poSupplierForm">
            @csrf

            
            <div class="section-sub-title">
                <i class="bi bi-person-vcard-fill me-2"></i>
                Customer PO Information
            </div>

            <div class="info-group-box mb-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Select Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-control searchable-select" required>
                        <option value="">-- Select Customer --</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->customer_code }} - {{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Select Customer PO <span class="text-danger">*</span></label>
                    <select name="po_customer_id" id="po_customer_id" class="form-control" required disabled>
                        <option value="">-- Select Customer First --</option>
                    </select>
                </div>
            </div>

            
            </div>

            <div id="poDetails" class="bgs-u-6e22c58a7a6a">
                <div class="section-sub-title">
                    <i class="bi bi-building-fill me-2"></i>
                    Supplier PO Information
                </div>

                <div class="info-group-box mb-4">
                    <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id" class="form-control" required>
                            <option value="">-- Select Supplier --</option>
                            @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->supplier_code }} - {{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">PO Date <span class="text-danger">*</span></label>
                        <input type="date"
                            name="po_date"
                            id="po_date"
                            class="form-control"
                            value="{{ date('Y-m-d') }}"
                            required>
                    </div>
                </div>
                
                </div>

                <div class="section-sub-title">
                    <i class="bi bi-box-seam-fill me-2"></i>
                    Product Details
                </div>

                <div class="info-group-box mb-4 p-0 overflow-hidden">
                    <div class="table-responsive">
                    <table class="table table-bordered po-supplier-items-table mb-0 table-striped" id="itemsTable">
                        <thead>
                            <tr>
                                <th class="text-center" width="5%">Select</th>
              <th class="text-center" width="30%">Product</th>
              <th class="text-center" width="15%">Remaining Qty</th>
              <th class="text-center" width="15%">Qty PO Supplier</th>
              <th class="text-center bgs-u-37dd0c2a6468" width="20%">Purchase Price (Rp)</th>
              <th class="text-center bgs-u-37dd0c2a6468" width="15%">Subtotal (Rp)</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr><td colspan="6" class="text-center">Please select a Customer PO first</td></tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end">Subtotal</th>
                                <th class="text-end" id="subtotal_display">Rp 0</th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">Discount (%)</th>
                                <td class="text-start bgs-u-37dd0c2a6468">
                                    <input type="number" name="discount_percent" id="discount_percent" class="form-control bgs-u-ed55e5720fee" step="0.01" min="0" max="100" value="0">
                                </td>
                                <th class="text-end" id="discount_amount_display">Rp 0</th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">VAT (%)</th>
                                <td class="text-start bgs-u-37dd0c2a6468">
                                    <input type="number" name="tax_percent" id="tax_percent" class="form-control bgs-u-ed55e5720fee" step="0.01" min="0" max="100" value="11">
                                </td>
                                <th class="text-end" id="tax_amount_display">Rp 0</th>
                            </tr>
                            <tr class="table-primary">
                                <th colspan="5" class="text-end"><strong>Grand Total</strong></th>
                                <th class="text-end"><strong id="grand_total_display">Rp 0</strong></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                </div>

                <div class="section-sub-title">
                    <i class="bi bi-chat-left-text-fill me-2"></i>
                    Remarks
                </div>

                <div class="info-group-box mb-4">
                    <div class="mb-0">
                        <label class="form-label">Remarks</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-success px-4 py-2 fw-bold text-white bgs-u-5c3a9bfaf58c"><i class="bi bi-save-fill me-1"></i> Save PO Supplier</button>
                    <a href="{{ route('po-suppliers.index') }}" class="btn btn-light px-4 py-2 fw-bold">Cancel</a>
                </div>
            </div>
        </form>

<!-- Purchase Price Difference Decision -->
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

                <button type="button"
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
                    Harga PO Supplier tetap memakai harga transaksi
                    yang Anda masukkan.

                </div>

                <div class="table-responsive">

                    <table class="table table-bordered po-supplier-items-table mb-0 align-middle">

                        <thead>
                            <tr>
                                <th>Product</th>

                                <th class="text-end">
                                    Master Price
                                </th>

                                <th class="text-end">
                                    PO Supplier Price
                                </th>

                                <th class="bgs-u-9fda40b045c3">
                                    Decision
                                </th>
                            </tr>
                        </thead>

                        <tbody id="masterPriceDecisionBody">
                        </tbody>

                    </table>

                </div>

                <div id="masterPriceDecisionError"
                     class="alert alert-danger d-none">

                    Pilih Update Master atau Keep Existing
                    untuk semua item yang berbeda harga.

                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn btn-light btn-sm fw-bold px-3"
                        data-bs-dismiss="modal">

                    Back

                </button>

                <button type="button"
                        class="btn btn-primary"
                        id="confirmMasterPriceDecision">

                    Continue Save

                </button>

            </div>

        </div>

    </div>

</div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    let rowIndex = 0;

    /*
     * Prevent normal Save until every changed price has
     * an explicit decision.
     */
    let masterPriceDecisionConfirmed = false;

    // Inisialisasi Select2 khusus untuk Select Customer
    $('.searchable-select').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Otomatis fokus ke kotak pencarian ketika dropdown diklik/dibuka
    $(document).on('select2:open', function(e) {
        window.setTimeout(function () {
            document.querySelector('.select2-container--open .select2-search__field').focus();
        }, 50);
    });

    function formatRupiah(angka) {
        if (!angka || isNaN(angka)) return '0';
        return new Intl.NumberFormat('id-ID').format(Math.round(angka));
    }

    function parseRupiahToNumber(str) {
        if (!str) return 0;
        return parseInt(str.toString().replace(/\./g, '')) || 0;
    }

    function calculateSubtotal(rowId) {

        let selected =
            $('.item-selector[data-row="' + rowId + '"]')
                .is(':checked');

        if (!selected) {

            $('#subtotal_' + rowId)
                .text('Rp 0')
                .data('value', 0);

            $('#subtotal_hidden_' + rowId).val(0);

            calculateGrandTotal();
            return;
        }

        let qty =
            parseFloat(
                $('#qty_' + rowId).val()
            ) || 0;

        let price =
            parseRupiahToNumber(
                $('#price_' + rowId).val()
            );

        let subtotal = qty * price;

        $('#subtotal_' + rowId)
            .text('Rp ' + formatRupiah(subtotal))
            .data('value', subtotal);

        $('#subtotal_hidden_' + rowId).val(subtotal);

        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let subtotal = 0;
        $('[id^="subtotal_"]').each(function() {
            let val = $(this).data('value') || 0;
            subtotal += val;
        });

        let discountPercent = parseFloat($('#discount_percent').val()) || 0;
        let discountAmount = subtotal * discountPercent / 100;
        let afterDiscount = subtotal - discountAmount;

        let taxPercent = parseFloat($('#tax_percent').val()) || 0;
        let taxAmount = afterDiscount * taxPercent / 100;
        let grandTotal = afterDiscount + taxAmount;

        $('#subtotal_display').text('Rp ' + formatRupiah(subtotal));
        $('#subtotal_hidden').val(subtotal);
        $('#discount_amount_display').text('Rp ' + formatRupiah(discountAmount));
        $('#discount_amount_hidden').val(discountAmount);
        $('#tax_amount_display').text('Rp ' + formatRupiah(taxAmount));
        $('#tax_amount_hidden').val(taxAmount);
        $('#grand_total_display').text('Rp ' + formatRupiah(grandTotal));
        $('#grand_total_hidden').val(grandTotal);
    }

    // Ketika customer dipilih
    $('#customer_id').change(function() {
        var customerId = $(this).val();
        var poCustomerSelect = $('#po_customer_id');
        $('#poDetails').hide();
        
        if (customerId) {
            poCustomerSelect.html('<option value="">Loading...</option>').prop('disabled', false);
            $.ajax({
                url: '{{ route("po-suppliers.get-po-customer-list") }}',
                type: "GET",
                data: {customer_id: customerId},
                dataType: "json",
                success: function(response) {
                    var options = '<option value="">-- Pilih PO Customer --</option>';
                    if (response.length > 0) {
                        $.each(response, function(key, po) {
                            var formattedTotal = formatRupiah(po.total);
                            options += '<option value="'+po.id+'">'+po.po_number+' - '+po.po_date+' (Rp '+formattedTotal+')</option>';
                        });
                    } else {
                        options = '<option value="">Tidak ada PO Customer tersedia</option>';
                    }
                    poCustomerSelect.html(options).prop('disabled', false);
                },
                error: function() {
                    alert('Gagal mengambil daftar PO Customer');
                    poCustomerSelect.html('<option value="">Error</option>').prop('disabled', false);
                }
            });
        } else {
            poCustomerSelect.html('<option value="">-- Pilih Customer Terlebih Dahulu --</option>').prop('disabled', true);
        }
    });

    // Ketika PO Customer dipilih
    $('#po_customer_id').change(function() {
        var poCustomerId = $(this).val();
        if (!poCustomerId) {
            $('#poDetails').hide();
            return;
        }

        $('#itemsBody').html('<tr><td colspan="6" class="text-center">Memuat data...</td></tr>');
        $('#poDetails').show();

        $.ajax({
            url: '{{ url("po-suppliers/get-po-customer-details") }}/' + poCustomerId,
            type: "GET",
            dataType: "json",
            success: function(response) {
                if (!response.items || response.items.length === 0) {
                    $('#itemsBody').html('<tr><td colspan="6" class="text-center">Tidak ada item produk</td></tr>');
                    return;
                }

                var rows = '';
                rowIndex = 0;
                $.each(response.items, function(i, item) {
                    rows += '<tr id="row_' + rowIndex + '" class="po-item-row">';

                    rows += '<td class="text-center align-middle">' +
                        '<input type="checkbox" ' +
                        'class="form-check-input item-selector" ' +
                        'data-row="' + rowIndex + '">' +
                        '</td>';

                    rows += '<td>' +
                        item.product_name +
                        '<br><small>' +
                        item.product_code +
                        ' - ' +
                        (item.brand || '-') +
                        '</small></td>';

                    rows += '<td class="text-center align-middle">' +
                        item.remaining_quantity +
                        ' ' +
                        item.unit +
                        '</td>';

                    rows += '<td><input type="number" ' +
                        'name="items[' + rowIndex + '][quantity]" ' +
                        'id="qty_' + rowIndex + '" ' +
                        'class="form-control qty-input" ' +
                        'value="' + item.remaining_quantity + '" ' +
                        'data-row="' + rowIndex + '" ' +
                        'data-remaining="' + item.remaining_quantity + '" ' +
                        'min="1" ' +
                        'max="' + item.remaining_quantity + '" ' +
                        'disabled></td>';

                    rows += '<td class="bgs-u-37dd0c2a6468"><input type="text" ' +
                        'name="items[' + rowIndex + '][purchase_price]" ' +
                        'id="price_' + rowIndex + '" ' +
                        'class="form-control price-input" ' +
                        'data-row="' + rowIndex + '" ' +
                        'value="' + formatRupiah(item.purchase_price || 0) + '" ' +
                        'data-master-price="' + (item.purchase_price || 0) + '" ' +
                        'disabled></td>';

                    rows += '<input type="hidden" ' +
                        'name="items[' + rowIndex + '][master_price_decision]" ' +
                        'id="master_price_decision_' + rowIndex + '" ' +
                        'value="" disabled>';

                    rows += '<td class="text-end align-middle" ' +
                        'id="subtotal_' + rowIndex + '" ' +
                        'data-value="0">Rp 0</td>';

                    rows += '<input type="hidden" ' +
                        'name="items[' + rowIndex + '][po_customer_detail_id]" ' +
                        'id="detail_' + rowIndex + '" ' +
                        'value="' + item.po_customer_detail_id + '" disabled>';

                    rows += '<input type="hidden" ' +
                        'name="items[' + rowIndex + '][product_id]" ' +
                        'id="product_' + rowIndex + '" ' +
                        'value="' + item.product_id + '" disabled>';

                    rows += '<input type="hidden" ' +
                        'name="items[' + rowIndex + '][subtotal]" ' +
                        'id="subtotal_hidden_' + rowIndex + '" ' +
                        'value="0" disabled>';

                    rows += '</tr>';
                    rowIndex++;
                });
                $('#itemsBody').html(rows);
                calculateGrandTotal();
            },
            error: function() {
                alert('Gagal mengambil detail PO Customer');
                $('#poDetails').hide();
            }
        });
    });

    // Event perubahan qty atau harga
    // Select PO Customer Detail
    $(document).on('change', '.item-selector', function() {

        masterPriceDecisionConfirmed = false;

        let rowId = $(this).data('row');
        let selected = $(this).is(':checked');

        $('#qty_' + rowId).prop('disabled', !selected);
        $('#price_' + rowId).prop('disabled', !selected);
        $('#detail_' + rowId).prop('disabled', !selected);
        $('#product_' + rowId).prop('disabled', !selected);
        $('#subtotal_hidden_' + rowId).prop('disabled', !selected);
        $('#master_price_decision_' + rowId).prop('disabled', !selected);

        if (!selected) {
            $('#master_price_decision_' + rowId).val('');
        }

        $('#row_' + rowId)
            .toggleClass(
                'table-primary',
                selected
            );

        calculateSubtotal(rowId);
    });

    $(document).on('keyup change', '.qty-input, .price-input', function() {

        masterPriceDecisionConfirmed = false;
        let rowId = $(this).data('row');
        if (rowId !== undefined) calculateSubtotal(rowId);
    });

    // Format harga input saat diubah
    $(document).on('keyup change', '.price-input', function() {
        let val = $(this).val();
        let numeric = parseRupiahToNumber(val);
        if (numeric > 0) $(this).val(formatRupiah(numeric));
        else if (val === '') $(this).val('');
    });

    // Perubahan diskon dan PPN
    $('#discount_percent, #tax_percent').on('keyup change', function() {
        calculateGrandTotal();
    });

    // Submit form - tambahkan hidden fields untuk total
        function collectMasterPriceDifferences() {

        let differences = [];

        $('.item-selector:checked')
            .each(function() {

                let rowId =
                    $(this).data('row');

                let priceInput =
                    $('#price_' + rowId);

                let masterPrice =
                    parseFloat(
                        priceInput.attr(
                            'data-master-price'
                        )
                    ) || 0;

                let transactionPrice =
                    parseRupiahToNumber(
                        priceInput.val()
                    );

                if (
                    Math.round(masterPrice * 100) !==
                    Math.round(transactionPrice * 100)
                ) {

                    let productName =
                        $('#row_' + rowId)
                            .find('td')
                            .eq(1)
                            .clone()
                            .children()
                            .remove()
                            .end()
                            .text()
                            .trim();

                    differences.push({
                        rowId: rowId,
                        productName: productName,
                        masterPrice: masterPrice,
                        transactionPrice: transactionPrice
                    });
                }
            });

        return differences;
    }

    function showMasterPriceDecisionModal(
        differences
    ) {

        let body =
            $('#masterPriceDecisionBody');

        body.empty();

        differences.forEach(function(item) {

            /*
             * Force fresh explicit decision every time modal opens.
             */
            $('#master_price_decision_' + item.rowId)
                .val('');

            let row =
                $('<tr>');

            $('<td>')
                .text(item.productName)
                .appendTo(row);

            $('<td>')
                .addClass('text-end')
                .text(
                    'Rp ' +
                    formatRupiah(
                        item.masterPrice
                    )
                )
                .appendTo(row);

            $('<td>')
                .addClass('text-end')
                .text(
                    'Rp ' +
                    formatRupiah(
                        item.transactionPrice
                    )
                )
                .appendTo(row);

            let decisionCell =
                $('<td>');

            let updateId =
                'master_update_' +
                item.rowId;

            let keepId =
                'master_keep_' +
                item.rowId;

            decisionCell.append(
                '<div class="form-check">' +
                    '<input class="form-check-input master-price-choice" ' +
                    'type="radio" ' +
                    'name="master_price_choice_' + item.rowId + '" ' +
                    'id="' + updateId + '" ' +
                    'data-row="' + item.rowId + '" ' +
                    'value="update">' +
                    '<label class="form-check-label" for="' + updateId + '">' +
                        'Update Master' +
                    '</label>' +
                '</div>'
            );

            decisionCell.append(
                '<div class="form-check mt-1">' +
                    '<input class="form-check-input master-price-choice" ' +
                    'type="radio" ' +
                    'name="master_price_choice_' + item.rowId + '" ' +
                    'id="' + keepId + '" ' +
                    'data-row="' + item.rowId + '" ' +
                    'value="keep">' +
                    '<label class="form-check-label" for="' + keepId + '">' +
                        'Keep Existing' +
                    '</label>' +
                '</div>'
            );

            row.append(decisionCell);

            body.append(row);
        });

        $('#masterPriceDecisionError')
            .addClass('d-none');

        let modal =
            bootstrap.Modal.getOrCreateInstance(
                document.getElementById(
                    'masterPriceDecisionModal'
                )
            );

        modal.show();
    }

    $('#confirmMasterPriceDecision')
        .on('click', function() {

            let valid = true;

            $('#masterPriceDecisionBody tr')
                .each(function() {

                    let choice =
                        $(this).find(
                            '.master-price-choice:checked'
                        );

                    if (choice.length !== 1) {
                        valid = false;
                        return;
                    }

                    let rowId =
                        choice.data('row');

                    $('#master_price_decision_' + rowId)
                        .val(
                            choice.val()
                        );
                });

            if (!valid) {

                $('#masterPriceDecisionError')
                    .removeClass('d-none');

                return;
            }

            masterPriceDecisionConfirmed =
                true;

            let modal =
                bootstrap.Modal.getInstance(
                    document.getElementById(
                        'masterPriceDecisionModal'
                    )
                );

            if (modal) {
                modal.hide();
            }

            document
                .getElementById(
                    'poSupplierForm'
                )
                .requestSubmit();
        });
$('#poSupplierForm').on('submit', function(e) {

        if (!$('#po_customer_id').val()) {

            e.preventDefault();

            alert(
                'Silakan pilih PO Customer terlebih dahulu!'
            );

            return false;
        }

        let selectedRows =
            $('.item-selector:checked');

        if (selectedRows.length === 0) {

            e.preventDefault();

            alert(
                'Pilih minimal satu item yang akan dibuatkan PO Supplier.'
            );

            return false;
        }

        let valid = true;

        selectedRows.each(function() {

            let rowId = $(this).data('row');

            let qtyInput =
                $('#qty_' + rowId);

            let priceInput =
                $('#price_' + rowId);

            let qty =
                parseFloat(
                    qtyInput.val()
                ) || 0;

            let remaining =
                parseFloat(
                    qtyInput.attr('data-remaining')
                ) || 0;

            let price =
                parseRupiahToNumber(
                    priceInput.val()
                );

            if (qty <= 0 || qty > remaining) {

                valid = false;
                qtyInput.addClass('is-invalid');

            } else {

                qtyInput.removeClass('is-invalid');
            }

            if (price <= 0) {

                valid = false;
                priceInput.addClass('is-invalid');

            } else {

                priceInput.removeClass('is-invalid');
            }
        });

        if (!valid) {

            e.preventDefault();

            alert(
                'Periksa quantity dan purchase price item yang dipilih. ' +
                'Quantity tidak boleh melebihi remaining quantity.'
            );

            return false;
        }

        /*
         * Validation sudah PASS.
         *
         * Sebelum formatted purchase price dinormalisasi,
         * bandingkan dengan Product Master.
         */
        if (!masterPriceDecisionConfirmed) {

            let priceDifferences =
                collectMasterPriceDifferences();

            if (priceDifferences.length > 0) {

                e.preventDefault();

                showMasterPriceDecisionModal(
                    priceDifferences
                );

                return false;
            }
        }
        selectedRows.each(function() {

            let rowId = $(this).data('row');

            let priceInput =
                $('#price_' + rowId);

            priceInput.val(
                parseRupiahToNumber(
                    priceInput.val()
                )
            );

            calculateSubtotal(rowId);
        });

        calculateGrandTotal();

        $(this)
            .find(
                'input[data-generated-total="1"]'
            )
            .remove();

        $('<input>').attr({
            type: 'hidden',
            name: 'subtotal',
            value: $('#subtotal_hidden').val() || 0,
            'data-generated-total': '1'
        }).appendTo(this);

        $('<input>').attr({
            type: 'hidden',
            name: 'discount_amount',
            value: $('#discount_amount_hidden').val() || 0,
            'data-generated-total': '1'
        }).appendTo(this);

        $('<input>').attr({
            type: 'hidden',
            name: 'tax_amount',
            value: $('#tax_amount_hidden').val() || 0,
            'data-generated-total': '1'
        }).appendTo(this);

        $('<input>').attr({
            type: 'hidden',
            name: 'total',
            value: $('#grand_total_hidden').val() || 0,
            'data-generated-total': '1'
        }).appendTo(this);

        return true;
    });
});
</script>
@endsection
