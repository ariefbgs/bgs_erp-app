@extends('layouts.app')
@section('title', 'Document Receipt Management')
@section('content')
<style>
    .qty-input { text-align: right; }
</style>
<div class="card">
    <div class="card-header bg-primary text-white">
        <h4>Add New Document Receipt</h4>
    </div>
    <div class="card-body">
        <form id="doForm" action="{{ route('document-receipts.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Pilih PO Customer -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Select PO Customer <span class="text-danger">*</span></label>
                    <select name="po_customer_id" id="po_customer_id" class="form-control" required>
                        <option value="">-- Select PO Customer --</option>
                        @foreach($poCustomers as $po)
                        <option value="{{ $po->id }}">{{ $po->po_number }} - {{ $po->customer->name }} ({{ date('d/m/Y', strtotime($po->po_date)) }})</option>
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
                                    <select name="invoice_number" id="invoice_number" class="form-control form-control-sm mt-2">
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
                    <option value="Wisnu">Wisnu</option>
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
                <a href="{{ route('document-receipts.index') }}" class="btn btn-danger">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#po_customer_id').change(function() {
        var poId = $(this).val();
        if (!poId) {
            $('#poInfoContainer').hide();
            $('#products-container').html('');
            $('#selected-po-number').text('-');
            $('#tandaTerimaContainer').hide();
            $('#catatanContainer').hide();
            $('#deliveryByContainer').hide();
            $('#attachmentContainer').hide();
            $('#invoice_number').html('<option value="">-- Select Invoice --</option>');
            $('#tax_invoice_number').val('');
            $('#shipping_address_hidden').val('');
            $('#receiver_name_hidden').val('');
            return;
        }

        $('#poInfoContainer').hide();
        $('#products-container').html('<div class="alert alert-info">Loading...</div>');
        $('#tandaTerimaContainer').hide();
        $('#catatanContainer').hide();
        $('#deliveryByContainer').hide();
        $('#attachmentContainer').hide();

        $.ajax({
            url: '{{ url("document-receipts/get-po-customer-details") }}/' + poId,
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

                // Buat tabel produk (sama seperti sebelumnya)
                var html = '<h5>Product Details (Qty can be changed)</h5>';
                html += '<div class="table-responsive"><table class="table table-bordered">';
                html += '<thead class="table-dark"><tr><th>Product</th><th>Brand</th><th>Qty PO</th><th>Qty Send</th></tr></thead><tbody>';
                if (res.items && res.items.length > 0) {
                    $.each(res.items, function(i, item) {
                        html += '<tr>';
                        html += '<td>' + (item.product_name || '') + '<br><small>' + (item.product_code || '') + '</small>' + '</td>';
                        html += '<td>' + (item.brand || '-') + '</td>';
                        html += '<td class="text-center">' + (item.quantity || 0) + ' ' + (item.unit || '') + '</td>';
                        html += '<td><input type="number" name="items[' + i + '][quantity]" class="form-control qty-input" value="' + (item.quantity || 1) + '" min="1" required>';
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
                    url: '{{ url("document-receipts/get-invoices-by-po") }}/' + poId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(invoices) {
                        var options = '<option value="">-- Select Invoice --</option>';
                        if (invoices.length > 0) {
                            $.each(invoices, function(i, inv) {
                                options += '<option value="' + inv.invoice_number + '" data-tax="' + (inv.tax_invoice_number || '') + '">' + inv.invoice_number + ' (' + inv.type + ')</option>';
                            });
                        } else {
                            options = '<option value="">No invoice available</option>';
                        }
                        $('#invoice_number').html(options);
                        // Jika hanya ada satu invoice, langsung pilih dan isi tax
                        if (invoices.length === 1) {
                            $('#invoice_number').val(invoices[0].invoice_number).trigger('change');
                        }
                    },
                    error: function() {
                        $('#invoice_number').html('<option value="">Error loading invoices</option>');
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
                $('#invoice_number').html('<option value="">-- Select Invoice --</option>');
                $('#tax_invoice_number').val('');
            }
        });
    });

    // Saat pilihan invoice berubah, otomatis isi tax_invoice_number
    $(document).on('change', '#invoice_number', function() {
        var selected = $(this).find(':selected');
        var taxNumber = selected.data('tax') || '';
        $('#tax_invoice_number').val(taxNumber);
    });
});
</script>
@endsection