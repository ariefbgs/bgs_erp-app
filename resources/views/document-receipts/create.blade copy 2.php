@extends('layouts.app')
@section('title', 'Create Document Receipt')
@section('content')

<div class="card">
    <div class="card-header bg-primary text-white">
        <h4>Create Document Receipt</h4>
    </div>

    <div class="card-body">
        <form id="receiptForm" action="{{ route('document-receipts.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Pilih Delivery Order -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Select Delivery Order <span class="text-danger">*</span></label>
                    <select name="delivery_order_id" id="delivery_order_id" class="form-control" required>
                        <option value="">-- Select Delivery Order --</option>
                        @foreach($deliveryOrders as $do)
                            <option value="{{ $do->id }}">
                                {{ $do->do_number }} - {{ $do->poCustomer->customer->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Receipt Date</label>
                    <input type="date" name="receipt_date" class="form-control" value="{{ date('Y-m-d') }}" required>
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
                                    <tr><th>DO Number</th><td id="info_do_number">-</td></tr>
                                    <tr><th>Shipping Address</th><td id="info_shipping_address">-</td></tr>
                                    <tr><th>Receiver Name</th><td id="info_receiver_name">-</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pilihan Invoice Number berdasarkan PO Customer (AJAX) -->
            <div id="invoiceSelector" style="display: none;">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Invoice Number <span class="text-danger">*</span></label>
                        <select name="invoice_number" id="invoice_number" class="form-control" required>
                            <option value="">-- Select Invoice --</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tax Invoice Number</label>
                        <input type="text" name="tax_invoice_number" id="tax_invoice_number" class="form-control" readonly>
                    </div>
                </div>
            </div>

            <!-- Additional fields (hidden until DO selected) -->
            <div id="additionalFields" style="display: none;">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">PO Number</label>
                        <input type="text" id="display_po_number" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DO Number</label>
                        <input type="text" id="display_do_number" class="form-control" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Attachment (PDF/Image)</label>
                    <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    <small class="text-muted">Maximum 5MB. Images will be compressed.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Save Document Receipt</button>
                    <a href="{{ route('document-receipts.index') }}" class="btn btn-danger">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#delivery_order_id').change(function() {
        var doId = $(this).val();
        if (!doId) {
            $('#poInfoContainer').hide();
            $('#invoiceSelector').hide();
            $('#additionalFields').hide();
            $('#invoice_number').html('<option value="">-- Select Invoice --</option>');
            $('#tax_invoice_number').val('');
            return;
        }

        // Ambil detail DO
        $.ajax({
            url: '{{ route("document-receipts.get-do-details", "") }}/' + doId,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                // Tampilkan info PO dan DO
                $('#info_po_number').text(res.po_number || '-');
                $('#info_customer_name').text(res.customer_name || '-');
                $('#info_customer_address').text(res.customer_address || '-');
                $('#info_do_number').text(res.do_number || '-');
                $('#info_shipping_address').text(res.shipping_address || '-');
                $('#info_receiver_name').text(res.receiver_name || '-');
                $('#poInfoContainer').show();

                // Isi display fields
                $('#display_po_number').val(res.po_number || '-');
                $('#display_do_number').val(res.do_number || '-');

                // Load daftar invoice berdasarkan po_customer_id
                $.ajax({
                    url: '{{ route("document-receipts.get-invoices-by-po", "") }}/' + res.po_customer_id,
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
                        $('#invoiceSelector').show();
                        $('#additionalFields').show();
                        // Auto select jika hanya satu invoice
                        if (invoices.length === 1) {
                            $('#invoice_number').val(invoices[0].invoice_number).trigger('change');
                        }
                    },
                    error: function() {
                        $('#invoice_number').html('<option value="">Error loading invoices</option>');
                    }
                });
            },
            error: function() {
                alert('Failed to load Delivery Order details');
                $('#poInfoContainer').hide();
                $('#invoiceSelector').hide();
                $('#additionalFields').hide();
            }
        });
    });

    // Saat invoice dipilih, isi tax invoice number
    $('#invoice_number').change(function() {
        var selected = $(this).find(':selected');
        var taxNumber = selected.data('tax') || '';
        $('#tax_invoice_number').val(taxNumber);
    });
});
</script>
@endsection