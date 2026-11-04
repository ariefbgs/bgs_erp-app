@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-goods-receipts-edit bgs-mode-edit')
@section('title', 'Edit Good Receipt')
@section('content')


<div class="card main-card">
    <div class="card-header-custom bgs-page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4>
                Edit Goods Receipt
            </h4>
            <a href="{{ route('goods-receipts.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body p-4">
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>Terjadi kesalahan!</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm d-flex fade show mb-4 bgs-u-2e9c7cf0569c">
                <div class="me-2"><i class="bi bi-check-circle-fill fs-5"></i></div>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close ms-auto bgs-u-aae30f4ce161" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm mb-4 bgs-u-f812d16479e9">
                <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
            </div>
        @endif  

        <form action="{{ route('goods-receipts.update', $receipt->id) }}" method="POST" id="receiptForm">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Receipt #</label>
                    <input type="text" class="form-control" value="{{ $receipt->receipt_number }}" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Received Date <span class="text-danger">*</span></label>
                    <input type="date" name="receipt_date" class="form-control" value="{{ date('Y-m-d', strtotime($receipt->receipt_date)) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">PO Supplier #</label>
                    <input type="text" class="form-control" value="{{ $receipt->poSupplier->po_supplier_number }} - {{ $receipt->poSupplier->supplier->name }}" readonly>
                    <input type="hidden" name="po_supplier_id" value="{{ $receipt->po_supplier_id }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">PO Date</label>
                    <input type="date" class="form-control" value="{{ date('Y-m-d', strtotime($receipt->poSupplier->po_date)) }}" readonly>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Remarks</label>
                <textarea name="notes" class="form-control" rows="2">{{ $receipt->notes }}</textarea>
            </div>

            <hr>
            <h5 class="section-sub-title">Product Received</h5>

            <div class="table-responsive">
                <table class="table align-middle" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="30%">Product</th>
                            <th width="15%">PO Qty</th>
                            <th width="15%">Remaining</th>
                            <th width="20%">Received Quantity <span class="text-danger">*</span></th>
                            <th width="15%">Remarks</th>
                            <th width="5%">Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        @foreach($itemsWithRemaining as $index => $item)
                            @php
                                $detail = $item['detail'];
                                $remainingOther = $item['remaining_other'];
                                $maxQty = $item['max_qty'];
                            @endphp
                            <tr id="row-{{ $index }}">
                                <td>
                                    <strong>{{ $detail->product->name }}</strong><br>
                                    <small>{{ $detail->product->product_code }} - {{ $detail->product->brand ?? '-' }}</small>
                                    <input type="hidden" name="items[{{ $index }}][po_supplier_detail_id]" value="{{ $detail->po_supplier_detail_id }}">
                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $detail->product_id }}">
                                </td>
                                <td class="text-center">{{ $detail->poSupplierDetail->quantity }} {{ $detail->product->unit }}</td>
                                <td class="text-center remaining-qty" id="remaining-{{ $index }}">{{ $remainingOther }} {{ $detail->product->unit }}</td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][quantity_received]" 
                                           class="form-control received-qty" 
                                           data-index="{{ $index }}" 
                                           data-max="{{ $maxQty }}"
                                           value="{{ $detail->quantity_received }}" 
                                           required>
                                </td>
                                <td>
                                    <input type="text" name="items[{{ $index }}][notes]" class="form-control" value="{{ $detail->notes }}">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm remove-item" data-index="{{ $index }}">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot id="itemsFooter">
                        <tr class="table-info">
                            <td colspan="3" class="text-end"><strong>Total Received:</strong></td>
                            <td colspan="2"><strong id="totalReceived">{{ $receipt->details->sum('quantity_received') }}</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if(count($availableProducts) > 0)
            <div class="add-product-row mt-3">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Add Produk (Remaining)</label>
                        <select id="newProductSelect" class="form-control">
                            <option value="">-- Select Product --</option>
                            @foreach($availableProducts as $prod)
                            <option value="{{ $prod['po_supplier_detail_id'] }}" 
                                    data-product-id="{{ $prod['product_id'] }}"
                                    data-product-name="{{ $prod['product_name'] }}"
                                    data-product-code="{{ $prod['product_code'] }}"
                                    data-brand="{{ $prod['brand'] }}"
                                    data-unit="{{ $prod['unit'] }}"
                                    data-po-quantity="{{ $prod['po_quantity'] }}"
                                    data-remaining="{{ $prod['remaining_quantity'] }}">
                                {{ $prod['product_code'] }} - {{ $prod['product_name'] }} (Sisa: {{ $prod['remaining_quantity'] }} {{ $prod['unit'] }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Qty Received</label>
                        <input type="number" id="newProductQty" class="form-control" min="1" placeholder="Jumlah">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" id="btnAddProduct" class="btn btn-success form-control">Add Product</button>
                    </div>
                </div>
            </div>
            @endif

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary">Update Receipt</button>
                <a href="{{ route('goods-receipts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let nextIndex = {{ count($itemsWithRemaining) }};

    function calculateTotalReceived() {
        var total = 0;
        $('.received-qty').each(function() {
            var qty = parseInt($(this).val()) || 0;
            total += qty;
        });
        $('#totalReceived').text(total);
    }

    $(document).on('change keyup', '.received-qty', function() {
        var index = $(this).data('index');
        var maxQty = parseInt($(this).data('max'));
        var currentQty = parseInt($(this).val()) || 0;

        if (currentQty > maxQty) {
            alert('Quantity tidak boleh melebihi sisa quantity! Maksimal: ' + maxQty);
            $(this).val(maxQty);
            currentQty = maxQty;
        }
        if (currentQty < 0) {
            $(this).val(0);
        }
        calculateTotalReceived();
    });

    $(document).on('click', '.remove-item', function() {
        var index = $(this).data('index');
        var row = $('#row-' + index);
        if (confirm('Hapus item ini dari penerimaan? (Qty akan diset 0)')) {
            row.find('.received-qty').val(0).trigger('change');
            row.find('.remove-item').prop('disabled', true);
            row.addClass('bgs-row-muted');
        }
    });

    $('#btnAddProduct').click(function() {
        var select = $('#newProductSelect');
        var selectedOption = select.find(':selected');
        if (!selectedOption.val()) {
            alert('Pilih produk terlebih dahulu!');
            return;
        }
        var qty = parseInt($('#newProductQty').val());
        if (isNaN(qty) || qty <= 0) {
            alert('Masukkan quantity yang valid!');
            return;
        }
        var remaining = parseInt(selectedOption.data('remaining'));
        if (qty > remaining) {
            alert('Quantity melebihi sisa! Maksimal: ' + remaining);
            return;
        }

        var poSupplierDetailId = selectedOption.val();
        var productId = selectedOption.data('product-id');
        var productName = selectedOption.data('product-name');
        var productCode = selectedOption.data('product-code');
        var brand = selectedOption.data('brand');
        var unit = selectedOption.data('unit');
        var poQuantity = selectedOption.data('po-quantity');

        var newRow = `
            <tr id="row-${nextIndex}">
                <td>
                    <strong>${productName}</strong><br>
                    <small>${productCode} - ${brand}</small>
                    <input type="hidden" name="items[${nextIndex}][po_supplier_detail_id]" value="${poSupplierDetailId}">
                    <input type="hidden" name="items[${nextIndex}][product_id]" value="${productId}">
                 </td>
                <td>${poQuantity} ${unit}</td>
                <td class="remaining-qty" id="remaining-${nextIndex}">${remaining - qty} ${unit}</td>
                <td>
                    <input type="number" name="items[${nextIndex}][quantity_received]" 
                           class="form-control received-qty" 
                           data-index="${nextIndex}" 
                           data-max="${remaining}"
                           value="${qty}" 
                           required>
                 </td>
                <td>
                    <input type="text" name="items[${nextIndex}][notes]" class="form-control" placeholder="Catatan">
                 </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-item" data-index="${nextIndex}">Hapus</button>
                 </td>
             </tr>
        `;
        $('#itemsBody').append(newRow);
        calculateTotalReceived();

        // Hapus option yang sudah dipilih dari dropdown
        select.find('option[value="' + poSupplierDetailId + '"]').remove();

        $('#newProductQty').val('');
        select.val('');

        nextIndex++;
    });

    calculateTotalReceived();
});
</script>
@endsection
