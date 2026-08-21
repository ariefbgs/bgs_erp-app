@extends('layouts.app')

@section('title', 'Add Goods Receipt')

@section('content')
<style>
    .received-qty {
        text-align: right;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .remove-item {
        white-space: nowrap;
    }
</style>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h4>Add Goods Receipt</h4>
    </div>
    <div class="card-body">
        <form id="receiptForm" action="{{ route('goods-receipts.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Select PO Supplier <span class="text-danger">*</span></label>
                    <select name="po_supplier_id" id="po_supplier_id" class="form-control" required>
                        <option value="">-- Select PO Supplier --</option>
                        @foreach($poSuppliers as $po)
                            <option value="{{ $po->id }}">
                                {{ $po->po_supplier_number }} - {{ $po->supplier->name }} 
                                ({{ date('d/m/Y', strtotime($po->po_date)) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Received Date <span class="text-danger">*</span></label>
                    <input type="date" name="receipt_date" id="receipt_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <div id="poDetails" style="display: none;">
                <hr>
                <div class="alert alert-info">
                    <strong>Info PO Supplier:</strong> <span id="poInfo"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Remarks for goods receipt..."></textarea>
                </div>

                <h5>Received Items</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="itemsTable">
                        <thead class="table-dark">
                            <tr>
                                <th width="30%">Product</th>
                                <th width="15%">PO Quantity</th>
                                <th width="15%">Remaining</th>
                                <th width="20%">Received Quantity <span class="text-danger">*</span></th>
                                <th width="20%">Description</th>
                                <th width="5%">Action</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr><td colspan="6" class="text-center">Select a PO Supplier first</td></tr>
                        </tbody>
                        <tfoot id="itemsFooter" style="display: none;">
                            <tr class="table-info">
                                <td colspan="3" class="text-end"><strong>Total Received:</strong></td>
                                <td colspan="2"><strong id="totalReceived">0</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-right mt-3">
                    <button type="submit" class="btn btn-primary">Save Receipt</button>
                    <a href="{{ route('goods-receipts.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    var itemsData = [];

    // Ketika PO Supplier dipilih
    $('#po_supplier_id').change(function() {
        var poSupplierId = $(this).val();
        if (!poSupplierId) {
            $('#poDetails').hide();
            return;
        }

        $('#itemsBody').html('<tr><td colspan="6" class="text-center">Memuat data...</td></tr>');
        $('#poDetails').show();

        $.ajax({
            url: '/goods-receipts/get-po-supplier-details/' + poSupplierId,
            type: "GET",
            dataType: "json",
            success: function(response) {
                console.log('Response:', response);
                if (!response.items || response.items.length === 0) {
                    $('#itemsBody').html('<tr><td colspan="6" class="text-center text-warning">Tidak ada item yang bisa diterima (semua sudah complete)</td></tr>');
                    $('#itemsFooter').hide();
                    return;
                }

                // Update info PO
                var poInfo = 'PO Number: ' + response.po_supplier.po_supplier_number +
                             ' | Supplier: ' + response.po_supplier.supplier.name +
                             ' | PO Date: ' + response.po_supplier.po_date;
                $('#poInfo').text(poInfo);

                var rows = '';
                $.each(response.items, function(index, item) {
                    rows += '<tr id="row-' + index + '">';
                    rows += '<td>';
                    rows += '<strong>' + escapeHtml(item.product_name) + '</strong><br>';
                    rows += '<small>' + escapeHtml(item.product_code) + ' - ' + escapeHtml(item.brand || '-') + '</small>';
                    rows += '<input type="hidden" name="items[' + index + '][po_supplier_detail_id]" value="' + item.po_supplier_detail_id + '">';
                    rows += '<input type="hidden" name="items[' + index + '][product_id]" value="' + item.product_id + '">';
                    rows += '</td>';
                    rows += '<td class="text-center">' + item.po_quantity + ' ' + escapeHtml(item.unit) + '</td>';
                    rows += '<td class="text-center remaining-qty" id="remaining-' + index + '">' + item.remaining_quantity + ' ' + escapeHtml(item.unit) + '</td>';
                    rows += '<td>';
                    rows += '<input type="number" name="items[' + index + '][quantity_received]" ';
                    rows += 'class="form-control received-qty" ';
                    rows += 'data-index="' + index + '" ';
                    rows += 'data-max="' + item.remaining_quantity + '" ';
                    rows += 'value="' + item.remaining_quantity + '" ';
                    rows += 'required>';
                    rows += '</td>';
                    rows += '<td>';
                    rows += '<input type="text" name="items[' + index + '][notes]" class="form-control" placeholder="Notes (optional)">';
                    rows += '</td>';
                    rows += '<td class="text-center">';
                    rows += '<button type="button" class="btn btn-danger btn-sm remove-item" data-index="' + index + '">Delete</button>';
                    rows += '</td>';
                    rows += '</tr>';
                });
                $('#itemsBody').html(rows);
                $('#itemsFooter').show();
                calculateTotalReceived();
            },
            error: function(xhr) {
                console.error('Error:', xhr.responseText);
                alert('Gagal mengambil detail PO Supplier. Periksa koneksi atau data PO Supplier.');
                $('#poDetails').hide();
            }
        });
    });

    // Validasi quantity received
    $(document).on('change keyup', '.received-qty', function() {
        var index = $(this).data('index');
        var maxQty = parseInt($(this).data('max'));
        var currentQty = parseInt($(this).val()) || 0;

        if (currentQty > maxQty) {
            alert('Quantity must not exceed the remaining quantity! Maximum: ' + maxQty);
            $(this).val(maxQty);
            currentQty = maxQty;
        }
        if (currentQty < 0) {
            $(this).val(0);
            currentQty = 0;
        }

        var unit = $('#row-' + index).find('.remaining-qty').text().match(/[a-zA-Z]+$/);
        var unitStr = unit ? unit[0] : '';
        var newRemaining = maxQty - currentQty;
        $('#remaining-' + index).text(newRemaining + ' ' + unitStr);

        calculateTotalReceived();
    });

    // Hapus item (set qty = 0)
    $(document).on('click', '.remove-item', function() {
        var index = $(this).data('index');
        var row = $('#row-' + index);
        var qtyInput = row.find('.received-qty');
        if (confirm('Remove this item from receipt? (Qty will be set to 0)')) {
            qtyInput.val(0);
            qtyInput.trigger('change');
            row.find('.remove-item').prop('disabled', true);
            row.css('opacity', '0.5');
        }
    });

    // Hitung total quantity yang diterima
    function calculateTotalReceived() {
        var total = 0;
        $('.received-qty').each(function() {
            var qty = parseInt($(this).val()) || 0;
            total += qty;
        });
        $('#totalReceived').text(total);
    }

    // Escape HTML untuk keamanan
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    // Validasi sebelum submit
    $('#receiptForm').on('submit', function(e) {
        var hasItem = false;
        var isValid = true;

        $('.received-qty').each(function() {
            var qty = parseInt($(this).val()) || 0;
            if (qty > 0) hasItem = true;

            var maxQty = parseInt($(this).data('max'));
            if (qty > maxQty) {
                isValid = false;
                $(this).css('border-color', 'red');
                alert('Quantity must not exceed the remaining quantity! Maximum: ' + maxQty);
            } else {
                $(this).css('border-color', '');
            }
        });

        if (!hasItem) {
            e.preventDefault();
            alert('At least 1 item with quantity > 0 is required!');
            return false;
        }
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        return true;
    });
});
</script>
@endsection