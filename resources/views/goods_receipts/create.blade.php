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

        {{-- ===================================================== --}}
        {{-- ALERT SUKSES & ERROR DARI SESSION --}}
        {{-- ===================================================== --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- ALERT VALIDASI ERROR --}}
        {{-- ===================================================== --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="bi bi-exclamation-circle-fill me-2"></i> Terjadi kesalahan validasi:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm d-flex fade show mb-4" style="background-color: #f0fdf4; color: #166534; border-left: 4px solid #16a34a !important; border-radius: 6px;">
                <div class="me-2"><i class="bi bi-check-circle-fill fs-5"></i></div>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size: 0.8rem;"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm mb-4" style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
                <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
            </div>
        @endif 

        {{-- ===================================================== --}}
        {{-- FORM --}}
        {{-- ===================================================== --}}
        <form id="receiptForm" action="{{ route('goods-receipts.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Select PO Supplier <span class="text-danger">*</span></label>
                    <select name="po_supplier_id" id="po_supplier_id" class="form-control" required>
                        <option value="">-- Select PO Supplier --</option>
                        @foreach($poSuppliers as $po)
                            <option value="{{ $po->id }}" {{ old('po_supplier_id') == $po->id ? 'selected' : '' }}>
                                {{ $po->po_supplier_number }} - {{ $po->supplier->name }} 
                                ({{ date('d/m/Y', strtotime($po->po_date)) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Received Date <span class="text-danger">*</span></label>
                    <input type="date" name="receipt_date" id="receipt_date" class="form-control" value="{{ old('receipt_date', date('Y-m-d')) }}" required>
                </div>
            </div>

            <div id="poDetails" style="display: none;">
                <hr>
                <div class="alert alert-info">
                    <strong>Info PO Supplier:</strong> <span id="poInfo"></span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Remarks for goods receipt...">{{ old('notes') }}</textarea>
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

                {{-- LOADING INDICATOR --}}
                <div id="loadingIndicator" class="text-center" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Memuat data...</p>
                </div>

                <div class="text-right mt-3">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-save-fill me-1"></i> Save Receipt
                    </button>
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
            $('#itemsBody').html('<tr><td colspan="6" class="text-center">Select a PO Supplier first</td></tr>');
            $('#itemsFooter').hide();
            return;
        }

        $('#itemsBody').html('<tr><td colspan="6" class="text-center">Memuat data...</td></tr>');
        $('#poDetails').show();
        $('#loadingIndicator').show();

        $.ajax({
            url: '/goods-receipts/get-po-supplier-details/' + poSupplierId,
            type: "GET",
            dataType: "json",
            success: function(response) {
                $('#loadingIndicator').hide();
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
                    rows += '<button type="button" class="btn btn-danger btn-sm remove-item" data-index="' + index + '"><i class="bi bi-trash3-fill"></i></button>';
                    rows += '</td>';
                    rows += '</tr>';
                });
                $('#itemsBody').html(rows);
                $('#itemsFooter').show();
                calculateTotalReceived();
            },
            error: function(xhr) {
                $('#loadingIndicator').hide();
                console.error('Error:', xhr.responseText);
                var errorMsg = 'Gagal mengambil detail PO Supplier. ';
                if (xhr.status === 404) {
                    errorMsg += 'Data PO Supplier tidak ditemukan.';
                } else {
                    errorMsg += 'Periksa koneksi atau data PO Supplier.';
                }
                $('#itemsBody').html('<tr><td colspan="6" class="text-center text-danger">' + errorMsg + '</td></tr>');
                $('#itemsFooter').hide();
                alert(errorMsg);
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
        var errorMsg = '';

        $('.received-qty').each(function() {
            var qty = parseInt($(this).val()) || 0;
            if (qty > 0) hasItem = true;

            var maxQty = parseInt($(this).data('max'));
            if (qty > maxQty) {
                isValid = false;
                $(this).css('border-color', 'red');
                errorMsg = 'Quantity must not exceed the remaining quantity! Maximum: ' + maxQty;
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
            alert(errorMsg);
            return false;
        }

        // Tambahkan loading state tombol
        $('#submitBtn').html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');
        $('#submitBtn').prop('disabled', true);

        return true;
    });

    // Jika ada old value, trigger change untuk memuat data PO
    var oldPoId = "{{ old('po_supplier_id') }}";
    if (oldPoId) {
        $('#po_supplier_id').val(oldPoId).trigger('change');
    }
});
</script>
@endsection