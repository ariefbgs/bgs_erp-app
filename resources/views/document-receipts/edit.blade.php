@extends('layouts.app')
@section('title', 'Edit Document Receipt')
@section('content')

<style>
    .qty-input {
        text-align: right;
    }
</style>

<div class="card">
    <div class="card-header bg-warning text-dark">
        <h4>Edit Document Receipt</h4>
    </div>

    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif 

        <form action="{{ route('document-receipts.update', $receipt->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Pilih Delivery Order -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Delivery Order <span class="text-danger">*</span></label>
                    <select name="delivery_order_id" id="delivery_order_id" class="form-control" required>
                        <option value="">-- Select Delivery Order --</option>
                        @foreach($deliveryOrders as $do)
                            <option value="{{ $do->id }}" {{ $receipt->delivery_order_id == $do->id ? 'selected' : '' }}>
                                {{ $do->do_number }} - {{ $do->poCustomer->customer->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Receipt Date</label>
                    <input type="date" name="receipt_date" class="form-control" value="{{ $receipt->receipt_date ?? date('Y-m-d') }}" required>
                </div>
            </div>

            <!-- Informasi PO Customer (diambil dari DO yang dipilih, via AJAX) -->
            <div id="poInfoContainer" class="card mb-3" style="display: none;">
                <div class="card-header bg-info text-white">
                    <strong>PO Customer Information</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="35%">No. PO</th>
                                    <td id="info_po_number">-</td>
                                </tr>
                                <tr>
                                    <th>Customer</th>
                                    <td id="info_customer_name">-</td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td id="info_customer_address">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th>Shipping Address</th>
                                    <td id="info_shipping_address">-</td>
                                </tr>
                                <tr>
                                    <th>Receiver Name</th>
                                    <td id="info_receiver_name">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pilihan Invoice Number berdasarkan DO (AJAX) -->
            <div class="row mb-3" id="invoiceSelector" style="display: none;">
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
            <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="draft" {{ $receipt->status == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ $receipt->status == 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="delivered" {{ $receipt->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                </select>
            </div>

            <!-- Attachment -->
            <div class="mb-3">
                <label class="form-label">Attachment (PDF/Image)</label>
                @if($receipt->attachment)
                    <div class="mb-2">
                        <a href="{{ Storage::url($receipt->attachment) }}" target="_blank" class="btn btn-sm btn-info">View Current Attachment</a>
                    </div>
                @endif
                <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <small class="text-muted">Leave empty if you do not want to change it. Max 5MB. Images will be compressed.</small>
            </div>

            <!-- Remarks -->
            <div class="mb-3">
                <label>Remarks</label>
                <textarea name="notes" class="form-control" rows="3">{{ $receipt->notes }}</textarea>
            </div>

            <!-- Button -->
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('document-receipts.index') }}" class="btn btn-danger">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Saat delivery order dipilih
    $('#delivery_order_id').change(function() {
        var doId = $(this).val();
        if (!doId) {
            $('#poInfoContainer').hide();
            $('#invoiceSelector').hide();
            $('#invoice_number').html('<option value="">-- Select Invoice --</option>');
            $('#tax_invoice_number').val('');
            return;
        }

        // Ambil detail DO (PO Customer, shipping address, dll)
        $.ajax({
            url: '{{ url("document-receipts/get-do-details") }}/' + doId,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                // Isi info PO
                $('#info_po_number').text(res.po_number || '-');
                $('#info_customer_name').text(res.customer_name || '-');
                $('#info_customer_address').text(res.customer_address || '-');
                $('#info_shipping_address').text(res.shipping_address || '-');
                $('#info_receiver_name').text(res.receiver_name || '-');
                $('#poInfoContainer').show();

                // Ambil daftar invoice untuk PO customer ini
                $.ajax({
                    url: '{{ url("document-receipts/get-invoices-by-po") }}/' + res.po_customer_id,
                    type: 'GET',
                    dataType: 'json',
                    success: function(invoices) {
                        var options = '<option value="">-- Select Invoice --</option>';
                        var selectedInvoice = '{{ $receipt->invoice_number }}';
                        if (invoices.length > 0) {
                            $.each(invoices, function(i, inv) {
                                var selected = (inv.invoice_number == selectedInvoice) ? 'selected' : '';
                                options += '<option value="' + inv.invoice_number + '" data-tax="' + (inv.tax_invoice_number || '') + '" ' + selected + '>' + inv.invoice_number + ' (' + inv.type + ')</option>';
                            });
                        } else {
                            options = '<option value="">No invoice available</option>';
                        }
                        $('#invoice_number').html(options);
                        $('#invoiceSelector').show();
                        // Trigger change untuk isi tax invoice number
                        $('#invoice_number').trigger('change');
                    },
                    error: function() {
                        $('#invoice_number').html('<option value="">Error loading invoices</option>');
                    }
                });
            },
            error: function() {
                alert('Failed to load DO details');
                $('#poInfoContainer').hide();
                $('#invoiceSelector').hide();
            }
        });
    });

    // Saat invoice dipilih, isi tax invoice number
    $('#invoice_number').change(function() {
        var selected = $(this).find(':selected');
        var taxNumber = selected.data('tax') || '';
        $('#tax_invoice_number').val(taxNumber);
    });

    // Trigger change awal untuk prefill data jika sedang edit
    $('#delivery_order_id').trigger('change');
});
</script>
@endsection