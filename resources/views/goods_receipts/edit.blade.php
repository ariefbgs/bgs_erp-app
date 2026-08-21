@extends('layouts.app')
@section('title', 'Edit Good Receipt')
@section('content')
<style>
    .page-title {
        font-size: 1.4rem;
        font-weight: 600;
    }
    .readonly-bg,
    .brand-display,
    .subtotal-display {
        background: #f8f9fa !important;
    }
    .price-display,
    .qty-input,
    .subtotal-display,
    .summary-input {
        text-align: right;
    }
    .product-card {
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 15px;
        background: #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .summary-table th {
        width: 60%;
        background: #f8f9fa;
    }
    .summary-table td,
    .summary-table th {
        vertical-align: middle;
    }
    .grand-total-row {
        background: #0d6efd;
        color: white;
        font-size: 1.05rem;
    }
    .section-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 15px;
    }
    .btn-remove {
        width: 100%;
    }
    .card-header-custom {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border-radius: 10px 10px 0 0;
    }
    .table-summary-wrapper {
        margin-top: 20px;
    }
</style>

<div class="card shadow-sm border-0">
    <div class="card-header card-header-custom py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="page-title">
                Edit Good Receipt  
            </div>
            <a href="{{ route('quotations.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
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
            <h5>Product Received</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="itemsTable">
                    <thead class="table-dark">
                        <tr>
                            <th width="30%">Product</th>
                            <th width="15%">PO Qty</th>
                            <th width="15%">Remaining</th>
                            <th width="20%">Qty DReceived <span class="text-danger">*</span></th>
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

            <div class="text-end mt-3">
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
            row.css('opacity', '0.5');
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