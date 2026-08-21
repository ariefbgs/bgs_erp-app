@extends('layouts.app')
@section('title', 'Edit PO Supplier')
@section('content')
<style>
    .page-title {
        font-size: 1.4rem;
        font-weight: 600;
    }
    .readonly-bg,
    .brand-display,
    .subtotal-display,
    .code2-display,
    .name2-display {
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
    .grand-total-row th {
        background: #0d6efd;
        color: #fff;
        font-weight: 600;
    }

    .grand-total-row td {
        background: #0d6efd;
        color: #fff;
        font-weight: 700;
    }

    /* optional: bikin lebih “ERP look” */
    .grand-total-row td strong {
        font-size: 1.1rem;
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
                Edit PO Supplier
            </div>
            <a href="{{ route('po-suppliers.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        {{-- ALERT --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>There is an error!</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <form action="{{ route('po-suppliers.update', $poSupplier->id) }}"
              method="POST"
              enctype="multipart/form-data"
              id="po-form">
            @csrf
            @method('PUT')
            {{-- HEADER --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="po_supplier_number" class="form-label fw-semibold">PO Supplier Number *</label>
                    <input type="text" name="po_supplier_number" id="po_supplier_number" 
                        class="form-control" required 
                        value="{{ old('po_supplier_number', $poSupplier->po_supplier_number) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">PO Date</label>
                    <input type="text"
                           class="form-control readonly-bg"
                           name="po_date"
                           value="{{ $poSupplier->po_date }}"
                           readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Expected Date</label>
                    <input type="text"
                           class="form-control readonly-bg"
                           name="expected_date"
                           value="{{ $poSupplier->expected_date }}"
                           readonly>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Supplier</label>
                    <input type="text"
                           class="form-control readonly-bg"
                           name="supplier_name"
                           value="{{ $poSupplier->supplier->name }}"
                           readonly>
                    <input type="hidden" name="supplier_id" value="{{ $poSupplier->supplier_id }}">
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="draft" {{ $poSupplier->status == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="confirmed" {{ $poSupplier->status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="cancelled" {{ $poSupplier->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Received Status</label>
                <select name="receive_status" class="form-select">
                    <option value="pending" {{ $poSupplier->status == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="partial" {{ $poSupplier->status == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="completed" {{ $poSupplier->status == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $poSupplier->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-3 d-flex align-items-center">
                    <div class="me-2"> <i class="bi bi-box-seam-fill text-primary fs-5"></i> </div>
                    <div> Product Details </div>
                </div>
                <button type="button"
                        class="btn btn-success btn-sm"
                        id="add-product">
                    <i class="bi bi-plus-circle"></i>
                    Add Product
                </button>
            </div>

            {{-- PRODUCT AREA (full width) --}}
            <div id="products-container">
                @foreach($poSupplier->details as $index => $detail)
                <div class="product-card product-row" data-row-id="{{ $index }}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Product</label>
                            <input type="text"
                                   class="form-control readonly-bg"
                                   value="{{ $detail->product->product_code }} - {{ $detail->product->name }}"
                                   readonly>
                            <input type="hidden"
                                   name="items[{{ $index }}][product_id]"
                                   value="{{ $detail->product_id }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Brand</label>
                            <input type="text"
                                   class="form-control brand-display"
                                   value="{{ $detail->product->brand ?? '-' }}"
                                   readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Qty</label>
                            <input type="number"
                                   name="items[{{ $index }}][quantity]"
                                   class="form-control qty-input"
                                   data-row-id="{{ $index }}"
                                   value="{{ $detail->quantity }}"
                                   min="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Price</label>
                            <input type="text"
                                   class="form-control price-display"
                                   data-row-id="{{ $index }}"
                                   value="{{ number_format($detail->purchase_price,0,',','.') }}">
                            <input type="hidden"
                                   name="items[{{ $index }}][purchase_price]"
                                   class="price-hidden"
                                   value="{{ $detail->purchase_price }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Subtotal</label>
                            <input type="text"
                                   class="form-control subtotal-display"
                                   value="{{ number_format($detail->subtotal,0,',','.') }}"
                                   readonly>
                            <input type="hidden"
                                   name="items[{{ $index }}][subtotal]"
                                   class="subtotal-hidden"
                                   value="{{ $detail->subtotal }}">
                            <button type="button"
                                    class="btn btn-danger btn-sm mt-2 remove-product btn-remove">
                                <i class="bi bi-trash"></i>
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            {{-- SUMMARY TABLE (dipindahkan ke bawah product card) --}}
            <div class="table-summary-wrapper">
                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <table class="table table-bordered summary-table">
                            <tr>
                                <th>Subtotal</th>
                                <td class="text-end">
                                    <span id="subtotal-text">Rp 0</span>
                                    <input type="hidden" name="subtotal" id="subtotal-value">
                                </td>
                            </tr>
                            <tr>
                                <th>Discount (%)</th>
                                <td>
                                    <input type="number"
                                           name="discount_percent"
                                           id="discount_percent"
                                           class="form-control summary-input"
                                           value="{{ $poSupplier->discount_percent ?? 0 }}"
                                           min="0"
                                           max="100"
                                           step="0.5">
                                </td>
                            </tr>
                            <tr>
                                <th>Discount Amount</th>
                                <td class="text-end">
                                    <span id="discount-text">Rp 0</span>
                                    <input type="hidden" name="discount_amount" id="discount-value">
                                </td>
                            </tr>
                            <tr>
                                <th>VAT (%)</th>
                                <td>
                                    <input type="number"
                                           name="tax_percent"
                                           id="tax_percent"
                                           class="form-control summary-input"
                                           value="{{ $poSupplier->tax_percent ?? 11 }}"
                                           min="0"
                                           max="100"
                                           step="0.5">
                                </td>
                            </tr>
                            <tr>
                                <th>VAT Amount</th>
                                <td class="text-end">
                                    <span id="tax-text">Rp 0</span>
                                    <input type="hidden" name="tax_amount" id="tax-value">
                                </td>
                            </tr>
                            <tr class="grand-total-row">
                                <th>Grand Total</th>
                                <td class="text-end">
                                    <strong id="grand-total-text">Rp 0</strong>
                                    <input type="hidden" name="total" id="grand-total-value">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            {{-- NOTES --}}
            <div class="mb-3 mt-4">
                <label class="form-label fw-semibold">Remarks</label>
                <textarea name="notes" class="form-control" rows="2">{{ $poSupplier->notes }}</textarea>
            </div>

            {{-- ACTION --}}
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Update PO Supplier
                </button>
                <a href="{{ route('po-suppliers.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let rowCount = {{ count($poSupplier->details) }};
    let productsData = @json($products);

    function formatRupiah(angka) {
        if(!angka || isNaN(angka)) return '0';
        return new Intl.NumberFormat('id-ID').format(Math.round(angka));
    }

    function parseRupiahToNumber(str) {
        if(!str) return 0;
        return parseInt(str.toString().replace(/\./g, '')) || 0;
    }

    function calculateGrandTotal() {
        let subtotal = 0;
        $('.subtotal-display').each(function() {
            let val = $(this).data('value') || 0;
            subtotal += parseInt(val);
        });
        
        let discountPercent = parseFloat($('#discount_percent').val()) || 0;
        let discountAmount = subtotal * (discountPercent / 100);
        let afterDiscount = subtotal - discountAmount;
        
        let taxPercent = parseFloat($('#tax_percent').val()) || 0;
        let taxAmount = afterDiscount * (taxPercent / 100);
        let grandTotal = afterDiscount + taxAmount;
        
        $('#subtotal-text').text('Rp ' + formatRupiah(subtotal));
        $('#subtotal-value').val(subtotal);
        $('#discount-text').text('Rp ' + formatRupiah(discountAmount));
        $('#discount-value').val(discountAmount);
        $('#tax-text').text('Rp ' + formatRupiah(taxAmount));
        $('#tax-value').val(taxAmount);
        $('#grand-total-text').text('Rp ' + formatRupiah(grandTotal));
        $('#grand-total-value').val(grandTotal);
    }

    function calculateSubtotal(rowId) {
        let qty = parseInt($(`#row-${rowId} .qty-input`).val()) || 0;
        let priceText = $(`#row-${rowId} .price-display`).val();
        let price = parseRupiahToNumber(priceText);
        let subtotal = qty * price;
        
        $(`#row-${rowId} .subtotal-display`).val(formatRupiah(subtotal));
        $(`#row-${rowId} .subtotal-display`).data('value', subtotal);
        $(`#row-${rowId} .price-hidden`).val(price);
        $(`#row-${rowId} #subtotal-hidden-${rowId}`).val(subtotal);
        calculateGrandTotal();
    }

    function addNewRow(item = null) {
        let productOptions = '<option value="">Pilih Produk</option>';
        productsData.forEach(function(product) {
            let selected = (item && item.product_id == product.id) ? 'selected' : '';
            productOptions += `<option value="${product.id}" data-price="${product.price}" data-name="${product.name}" data-brand="${product.brand || '-'}" ${selected}>${product.product_code} - ${product.name}</option>`;
        });
        
        let qtyValue = (item && item.quantity) ? item.quantity : 1;
        let priceValue = (item && item.purchase_price) ? formatRupiah(item.purchase_price) : '';
        let priceNumeric = (item && item.purchase_price) ? item.purchase_price : 0;
        let subtotalValue = (item && item.subtotal) ? formatRupiah(item.subtotal) : '';
        let subtotalNumeric = (item && item.subtotal) ? item.subtotal : 0;
        let brandValue = (item && item.brand) ? item.brand : '';
        
        let newRow = `
            <div class="product-row mb-3" id="row-${rowCount}">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Produk</label>
                        <select name="items[${rowCount}][product_id]" class="form-control product-select" data-row="${rowCount}" required>${productOptions}</select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Brand</label>
                        <input type="text" class="form-control brand-display" id="brand-${rowCount}" readonly style="background:#f8f9fa;" value="${brandValue}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="items[${rowCount}][quantity]" class="form-control qty-input" data-row="${rowCount}" value="${qtyValue}" min="1" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Purchase Price (Rp)</label>
                        <input type="text" class="form-control price-display" data-row="${rowCount}" value="${priceValue}" required>
                        <input type="hidden" name="items[${rowCount}][purchase_price]" class="price-hidden" data-row="${rowCount}" value="${priceNumeric}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Subtotal (Rp)</label>
                        <input type="text" class="form-control subtotal-display" id="subtotal-${rowCount}" readonly style="background:#f8f9fa;" value="${subtotalValue}">
                        <input type="hidden" name="items[${rowCount}][subtotal]" id="subtotal-hidden-${rowCount}" value="${subtotalNumeric}">

                        <button type="button" class="btn btn-danger btn-sm mt-2 remove-product" data-row="${rowCount}">Delete</button>
                    </div>
                </div>
            </div>
        `;
        $('#products-container').append(newRow);
        if(item && item.purchase_price) $(`#row-${rowCount} .subtotal-display`).data('value', item.subtotal);
        rowCount++;
    }

    // Inisialisasi data subtotal
    $('.subtotal-display').each(function() {
        let text = $(this).val();
        let number = parseRupiahToNumber(text);
        $(this).data('value', number);
    });
    
    calculateGrandTotal();
    
    // Event perubahan diskon & PPN
    $('#discount_percent, #tax_percent').on('keyup change', calculateGrandTotal);
    
    // Event untuk product select (tambah produk baru)
    $(document).on('change', '.product-select', function() {
        let rowId = $(this).data('row');
        let price = $(this).find(':selected').data('price');
        let brand = $(this).find(':selected').data('brand');
        if(brand) $(`#row-${rowId} .brand-display`).val(brand);
        if(price) {
            $(`#row-${rowId} .price-display`).val(formatRupiah(price));
            $(`#row-${rowId} .price-hidden`).val(price);
        }
        calculateSubtotal(rowId);
    });
    
    // Event untuk price display
    $(document).on('keyup change', '.price-display', function() {
        let rowId = $(this).data('row');
        let rawValue = $(this).val();
        let numericValue = parseRupiahToNumber(rawValue);
        if(numericValue > 0) {
            $(this).val(formatRupiah(numericValue));
            $(`#row-${rowId} .price-hidden`).val(numericValue);
        } else if(rawValue === '') {
            $(this).val('');
            $(`#row-${rowId} .price-hidden`).val(0);
        }
        calculateSubtotal(rowId);
    });
    
    // Event untuk qty change
    $(document).on('keyup change', '.qty-input', function() {
        let rowId = $(this).data('row');
        calculateSubtotal(rowId);
    });
    
    // Tombol tambah produk
    $('#add-product').click(function() {
        addNewRow();
    });
    
    // Tombol hapus produk
    $(document).on('click', '.remove-product', function() {
        let rowId = $(this).data('row');
        $(`#row-${rowId}`).remove();
        calculateGrandTotal();
    });
    
    // Tidak ada event handler submit yang mencegah, biarkan form submit normal
});
</script>
@endsection