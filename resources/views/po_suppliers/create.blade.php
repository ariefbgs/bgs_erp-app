@extends('layouts.app')
@section('title', 'Add New Supplier PO')
@section('content')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    .price-input, .qty-input, .price-display, .subtotal-display {
        text-align: right;
    }
    .product-select {
        text-align: left;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .pic-info {
        background-color: #e8f4f8;
        padding: 10px;
        border-radius: 5px;
        margin-top: 5px;
    }
    .quantity, .purchase-price {
        min-width: 100px;
    }
    .brand-display {
        background-color: #f8f9fa;
    }
</style>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h4>Add New Supplier PO</h4>
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
        <div class="alert alert-success border-0 shadow-sm mb-4" style="background-color: #f0fdf4; color: #15803d; border-left: 4px solid #16a34a !important;">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4" style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
        </div>
    @endif 
    <div class="card-body">
        <form action="{{ route('po-suppliers.store') }}" method="POST" id="poSupplierForm">
            @csrf

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

            <div id="poDetails" style="display: none;">
                <hr>
                <h5>Detail PO Supplier</h5>
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
                <hr>
                <h5>Product Details</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="itemsTable">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" width="35%">Product</th>
                                <th class="text-center" width="15%">Qty PO Customer</th>
                                <th class="text-center" width="15%">Qty PO Supplier</th>
                                <th class="text-center" width="20%">Purchase Price (Rp)</th>
                                <th class="text-center" width="15%">Subtotal (Rp)</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr><td colspan="5" class="text-center">Please select a Customer PO first</td></tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Subtotal</th>
                                <th class="text-end" id="subtotal_display">Rp 0</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Discount (%)</th>
                                <td class="text-start">
                                    <input type="number" name="discount_percent" id="discount_percent" class="form-control" step="0.01" min="0" max="100" value="0" style="width: 100px;">
                                </td>
                                <th class="text-end" id="discount_amount_display">Rp 0</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">VAT (%)</th>
                                <td class="text-start">
                                    <input type="number" name="tax_percent" id="tax_percent" class="form-control" step="0.01" min="0" max="100" value="11" style="width: 100px;">
                                </td>
                                <th class="text-end" id="tax_amount_display">Rp 0</th>
                            </tr>
                            <tr class="table-primary">
                                <th colspan="4" class="text-end"><strong>Grand Total</strong></th>
                                <th class="text-end"><strong id="grand_total_display">Rp 0</strong></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>

                <div class="text-right mt-3">
                    <button type="submit" class="btn btn-primary">Save PO Supplier</button>
                    <a href="{{ route('po-suppliers.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    let rowIndex = 0;

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
        let qty = parseFloat($(`#qty_${rowId}`).val()) || 0;
        let price = parseRupiahToNumber($(`#price_${rowId}`).val());
        let subtotal = qty * price;
        $(`#subtotal_${rowId}`).text('Rp ' + formatRupiah(subtotal));
        $(`#subtotal_${rowId}`).data('value', subtotal);
        $(`#subtotal_hidden_${rowId}`).val(subtotal);
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

        $('#itemsBody').html('<tr><td colspan="5" class="text-center">Memuat data...</td></tr>');
        $('#poDetails').show();

        $.ajax({
            url: '{{ url("po-suppliers/get-po-customer-details") }}/' + poCustomerId,
            type: "GET",
            dataType: "json",
            success: function(response) {
                if (!response.items || response.items.length === 0) {
                    $('#itemsBody').html('<tr><td colspan="5" class="text-center">Tidak ada item produk</td></tr>');
                    return;
                }

                var rows = '';
                rowIndex = 0;
                $.each(response.items, function(i, item) {
                    rows += '<tr id="row_' + rowIndex + '">';
                    rows += '<td>' + item.product_name + '<br><small>' + item.product_code + ' - ' + (item.brand || '-') + '</small></td>';
                    rows += '<td class="text-center">' + item.quantity + ' ' + item.unit + '</td>';
                    rows += '<td><input type="number" name="items[' + rowIndex + '][quantity]" id="qty_' + rowIndex + '" class="form-control qty-input" value="' + item.quantity + '" data-row="' + rowIndex + '" min="1" required></td>';
                    rows += '<td><input type="text" name="items[' + rowIndex + '][purchase_price]" id="price_' + rowIndex + '" class="form-control price-input" data-row="' + rowIndex + '" value="' + formatRupiah(item.purchase_price || 0) + '" required></td>';
                    rows += '<td class="text-end" id="subtotal_' + rowIndex + '" data-value="0">Rp 0</td>';
                    rows += '<input type="hidden" name="items[' + rowIndex + '][product_id]" value="' + item.product_id + '">';
                    rows += '<input type="hidden" name="items[' + rowIndex + '][subtotal]" id="subtotal_hidden_' + rowIndex + '" value="0">';
                    rows += '</tr>';
                    rowIndex++;
                });
                $('#itemsBody').html(rows);
                for (let i = 0; i < rowIndex; i++) {
                    calculateSubtotal(i);
                }
            },
            error: function() {
                alert('Gagal mengambil detail PO Customer');
                $('#poDetails').hide();
            }
        });
    });

    // Event perubahan qty atau harga
    $(document).on('keyup change', '.qty-input, .price-input', function() {
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
    $('#poSupplierForm').on('submit', function(e) {
        if ($('#itemsBody tr').length === 0 || $('#itemsBody tr td:first').text() === 'Memuat data...') {
            e.preventDefault();
            alert('Silakan pilih PO Customer terlebih dahulu!');
            return false;
        }
        let valid = true;
        $('.qty-input, .price-input').each(function() {
            let val = $(this).val();
            if (!val || parseFloat($(this).val()) <= 0) {
                valid = false;
                $(this).css('border-color', 'red');
            } else {
                $(this).css('border-color', '');
            }
        });
        if (!valid) {
            e.preventDefault();
            alert('Harap isi quantity dan harga beli dengan benar!');
            return false;
        }
        calculateGrandTotal();
        $('<input>').attr({type: 'hidden', name: 'subtotal', value: $('#subtotal_hidden').val() || 0}).appendTo(this);
        $('<input>').attr({type: 'hidden', name: 'discount_amount', value: $('#discount_amount_hidden').val() || 0}).appendTo(this);
        $('<input>').attr({type: 'hidden', name: 'tax_amount', value: $('#tax_amount_hidden').val() || 0}).appendTo(this);
        $('<input>').attr({type: 'hidden', name: 'total', value: $('#grand_total_hidden').val() || 0}).appendTo(this);
        return true;
    });
});
</script>
@endsection