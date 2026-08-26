@extends('layouts.app')
@section('title', 'Delivery Order & Document Receipt Management')
@section('content')
<style> 
    .qty-input { text-align: right; }
    .select2-container .select2-selection--single {
        min-height: 40px !important;
        height: auto !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        display: flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5 !important;
        padding: 8px 12px !important;
        color: #212529 !important;
        width: 100%;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
        top: 0 !important;
        right: 8px !important;
    }
    .select2-container--default .select2-results__option {
        padding: 8px 12px !important;
        white-space: normal !important;
        word-break: break-word !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        padding: 8px 12px !important;
        border-radius: 0.375rem !important;
        border: 1px solid #ced4da !important;
        min-height: 38px !important;
    }
    .select2-container--open .select2-dropdown {
        border-color: #86b7fe !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
    }
</style>
<div class="card">
    <div class="card-header bg-primary text-white">
        <h4>Add New Delivery Order & Document Receipt</h4>
    </div>
    <div class="card-body">
        <form id="doForm" action="{{ route('delivery-orders.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Pilih PO Customer -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Select PO Customer <span class="text-danger">*</span></label>
                    <select name="po_customer_id" id="po_customer_id" class="form-control select2" data-placeholder="-- Select PO Customer --" required>
                        <option value="">-- Select PO Customer --</option>
                        @foreach($poCustomers as $po)
                            <option value="{{ $po->id }}" {{ old('po_customer_id') == $po->id ? 'selected' : '' }}>
                                {{ $po->po_number }} - {{ $po->customer->name }} ({{ date('d/m/Y', strtotime($po->po_date)) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Delivery Date <span class="text-danger">*</span></label>
                    <input type="date" name="delivery_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <!-- Informasi PO Customer (akan diisi via AJAX) -->
            <div id="poInfoContainer" style="display: none;">
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <strong>PO Customer Information</strong>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr><th width="35%">No. PO</th><td id="info_po_number">-</td></tr>
                                    <tr><th>Customer</th><td id="info_customer_name">-</td></tr>
                                    <tr><th>Address</th><td id="info_customer_address">-</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr><th>Shipping Address (default)</th><td id="info_shipping_address">-</td></tr>
                                    <tr><th>Receiver Name</th><td id="info_receiver_name">-</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden inputs untuk shipping_address dan receiver_name yang akan dikirim -->
            <input type="hidden" name="shipping_address" id="shipping_address_hidden" value="">
            <input type="hidden" name="receiver_name" id="receiver_name_hidden" value="">

            <!-- Detail Produk -->
            <div id="products-container"></div>

            <!-- Tanda Terima Dokumen -->
            <div id="tandaTerimaContainer" style="display: none;">
                <hr>
                <h5 class="mb-3">Document Receipt</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th>Description</th>
                                <th width="25%">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Invoice -->
                            <tr>
                                <td class="text-center">1</td>
                                <td>
                                    <strong>Invoice</strong><br>
                                    <select name="invoice_number" id="invoice_number" class="form-control form-control-sm select2-invoice" data-placeholder="-- Select Invoice --">
                                        <option value="">-- Select Invoice --</option>
                                    </select>
                                 </td>
                                <td class="align-middle">1 Rangkap (Asli)</td>
                            </tr>
                            <!-- Faktur Pajak (akan terisi otomatis berdasarkan pilihan invoice) -->
                            <tr>
                                <td class="text-center">2</td>
                                <td>
                                    <strong>Faktur Pajak</strong><br>
                                    <input type="text" name="tax_invoice_number" id="tax_invoice_number" class="form-control form-control-sm mt-2" readonly placeholder="Will be filled automatically">
                                 </td>
                                <td class="align-middle">2 Rangkap (Asli + Copy)</td>
                            </tr>
                            <!-- PO -->
                            <tr>
                                <td class="text-center">3</td>
                                <td>
                                    <strong>PO Customer</strong><br>
                                    <span id="selected-po-number" class="text-primary">-</span>
                                 </td>
                                <td class="align-middle">1 Rangkap</td>
                            </tr>
                            <!-- DO -->
                            <tr>
                                <td class="text-center">4</td>
                                <td>
                                    <strong>Delivery Order</strong><br>
                                    <small class="text-muted">DO number will be generated automatically after saving</small>
                                 </td>
                                <td class="align-middle">3 Rangkap (Asli + Copy)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Remarks -->
            <div id="catatanContainer" style="display: none;">
                <div class="mb-3">
                    <label>Remarks</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div id="deliveryByContainer" class="col-md-6 mb-3" style="display: none;">
                <label class="form-label">Delivery By <span class="text-danger">*</span></label>
                <select name="delivery_by" id="delivery_by" class="form-control" required>
                    <option value="">Select</option>
                    <option value="JNE">JNE</option>
                    <option value="Courier Online">Courier Online</option>
                </select>
            </div>
            <div id="attachmentContainer" class="col-md-6 mb-3" style="display: none;">
                <label class="form-label">Attachment (PDF/Image)</label>
                <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <small class="text-muted">Maximum 5MB. Images will be compressed.</small>
            </div>

            <div class="text-right mt-3">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('delivery-orders.index') }}" class="btn btn-danger">Cancel</a>
            </div>
        </form>
    </div>
</div>

{{-- ========================================================= --}}
{{-- SCRIPT --}}
{{-- ========================================================= --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
{{-- Select2 CDN --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // =============================================================
    // INISIALISASI SELECT2
    // =============================================================
    function initSelect2() {
        // Select2 untuk PO Customer
        $('.select2').each(function() {
            if ($(this).data('select2')) {
                $(this).select2('destroy');
            }
            $(this).select2({
                placeholder: $(this).data('placeholder') || 'Select Option',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0
            });
        });

        // Select2 untuk Invoice
        $('.select2-invoice').each(function() {
            if ($(this).data('select2')) {
                $(this).select2('destroy');
            }
            $(this).select2({
                placeholder: $(this).data('placeholder') || 'Select Invoice',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0,
                // dropdownParent dihapus karena menyebabkan error
            });
        });
    }

    // =============================================================
    // AUTOFOCUS KE SEARCH SAAT SELECT2 DIBUKA
    // =============================================================
    $(document).on('select2:open', function(e) {
        setTimeout(function() {
            var searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField) {
                searchField.focus();
            }
        }, 50);
    });

    // Panggil inisialisasi pertama kali
    initSelect2();

    // =============================================================
    // EVENT: PO CUSTOMER CHANGE
    // =============================================================
    $('#po_customer_id').on('change', function() {
        var poId = $(this).val();
        if (!poId) {
            $('#poInfoContainer').hide();
            $('#products-container').html('');
            $('#selected-po-number').text('-');
            $('#tandaTerimaContainer').hide();
            $('#catatanContainer').hide();
            $('#deliveryByContainer').hide();
            $('#attachmentContainer').hide();

            // Hancurkan Select2 invoice, ganti HTML, lalu inisialisasi ulang
            var $invoiceSelect = $('#invoice_number');
            if ($invoiceSelect.data('select2')) {
                $invoiceSelect.select2('destroy');
            }
            $invoiceSelect.html('<option value="">-- Select Invoice --</option>');
            $('#tax_invoice_number').val('');
            $('#shipping_address_hidden').val('');
            $('#receiver_name_hidden').val('');
            initSelect2();
            return;
        }

        $('#poInfoContainer').hide();
        $('#products-container').html('<div class="alert alert-info">Loading...</div>');
        $('#tandaTerimaContainer').hide();
        $('#catatanContainer').hide();
        $('#deliveryByContainer').hide();
        $('#attachmentContainer').hide();

        $.ajax({
            url: '{{ url("delivery-orders/get-po-customer-details") }}/' + poId,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                // Isi informasi PO Customer
                $('#info_po_number').text(res.po_customer.po_number || '-');
                $('#info_customer_name').text(res.customer.name || '-');
                $('#info_customer_address').text(res.customer.address || '-');
                $('#info_shipping_address').text(res.shipping_address || '-');
                $('#info_receiver_name').text(res.receiver_name || '-');
                $('#poInfoContainer').show();

                // Isi hidden inputs
                $('#shipping_address_hidden').val(res.shipping_address || '');
                $('#receiver_name_hidden').val(res.receiver_name || '');

                $('#selected-po-number').text(res.po_customer.po_number || '-');

                // Buat tabel produk
                var html = '<h5>Product Details (limited by active Goods Receipt)</h5>';
                html += '<div class="table-responsive"><table class="table table-bordered">';
                html += '<thead class="table-dark"><tr><th>Product</th><th>Brand</th><th>Qty PO</th><th>Qty Send</th></tr></thead><tbody>';
                if (res.items && res.items.length > 0) {
                    $.each(res.items, function(i, item) {
                        html += '<tr>';
                        html += '<td>' + (item.product_name || '') + '<br><small>' + (item.product_code || '') + '</small>' + '</td>';
                        html += '<td>' + (item.brand || '-') + '</td>';
                        html += '<td class="text-center">' + (item.po_quantity || 0) + ' ' + (item.unit || '') + '<br><small class="text-success">Available from GR: ' + (item.quantity || 0) + '</small></td>';
                        html += '<td><input type="number" name="items[' + i + '][quantity]" class="form-control qty-input" value="' + (item.quantity || 0) + '" min="0" max="' + (item.quantity || 0) + '" required>';
                        html += '<input type="hidden" name="items[' + i + '][product_id]" value="' + (item.product_id || '') + '">' + '</td>';
                        html += '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="4" class="text-center">No products available</td></tr>';
                }
                html += '</tbody></table></div>';
                $('#products-container').html(html);

                // Ambil daftar invoice berdasarkan PO Customer ID
                $.ajax({
                    url: '{{ url("delivery-orders/get-invoices-by-po") }}/' + poId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(invoices) {
                        // Hancurkan Select2 invoice sebelum mengganti HTML
                        var $invoiceSelect = $('#invoice_number');
                        if ($invoiceSelect.data('select2')) {
                            $invoiceSelect.select2('destroy');
                        }

                        var options = '<option value="">-- Select Invoice --</option>';
                        if (invoices && invoices.length > 0) {
                            $.each(invoices, function(i, inv) {
                                options += '<option value="' + inv.invoice_number + '" data-tax="' + (inv.tax_invoice_number || '') + '">' + inv.invoice_number + ' (' + inv.type + ')</option>';
                            });
                        } else {
                            options += '<option value="">No invoice available</option>';
                        }
                        $invoiceSelect.html(options);

                        // Jika hanya ada satu invoice, langsung pilih dan isi tax
                        if (invoices && invoices.length === 1) {
                            $invoiceSelect.val(invoices[0].invoice_number).trigger('change');
                        }

                        // Re-init Select2 untuk invoice yang baru
                        initSelect2();
                    },
                    error: function() {
                        var $invoiceSelect = $('#invoice_number');
                        if ($invoiceSelect.data('select2')) {
                            $invoiceSelect.select2('destroy');
                        }
                        $invoiceSelect.html('<option value="">Error loading invoices</option>');
                        initSelect2();
                    }
                });

                // Tampilkan semua container
                $('#tandaTerimaContainer').show();
                $('#catatanContainer').show();
                $('#deliveryByContainer').show();
                $('#attachmentContainer').show();
            },
            error: function() {
                $('#products-container').html('<div class="alert alert-danger">Failed to load PO details.</div>');
                $('#poInfoContainer').hide();
                $('#tandaTerimaContainer').hide();
                $('#catatanContainer').hide();
                $('#deliveryByContainer').hide();
                $('#attachmentContainer').hide();
                $('#shipping_address_hidden').val('');
                $('#receiver_name_hidden').val('');

                var $invoiceSelect = $('#invoice_number');
                if ($invoiceSelect.data('select2')) {
                    $invoiceSelect.select2('destroy');
                }
                $invoiceSelect.html('<option value="">-- Select Invoice --</option>');
                $('#tax_invoice_number').val('');
                initSelect2();
            }
        });
    });

    // =============================================================
    // EVENT: INVOICE CHANGE -> AUTO FILL TAX NUMBER
    // =============================================================
    $(document).on('change', '#invoice_number', function() {
        var selected = $(this).find(':selected');
        var taxNumber = selected.data('tax') || '';
        $('#tax_invoice_number').val(taxNumber);
    });

    // =============================================================
    // EVENT: SELECT2 INVOICE SELECT -> AUTO FILL TAX NUMBER
    // =============================================================
    $(document).on('select2:select', '#invoice_number', function(e) {
        var selected = $(this).find(':selected');
        var taxNumber = selected.data('tax') || '';
        $('#tax_invoice_number').val(taxNumber);
    });

    // =============================================================
    // SUBMIT FORM HANDLER
    // =============================================================
    $('#doForm').on('submit', function() {
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');
        submitBtn.prop('disabled', true);
        return true;
    });

    // =============================================================
    // RESTORE OLD VALUE (jika validasi gagal)
    // =============================================================
    var oldPoId = "{{ old('po_customer_id') }}";
    if (oldPoId) {
        $('#po_customer_id').val(oldPoId).trigger('change');
    }

    var oldDeliveryBy = "{{ old('delivery_by') }}";
    if (oldDeliveryBy) {
        $('#delivery_by').val(oldDeliveryBy);
    }

    var oldNotes = "{{ old('notes') }}";
    if (oldNotes) {
        $('textarea[name="notes"]').val(oldNotes);
    }
});
</script>
@endsection
