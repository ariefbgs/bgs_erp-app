@extends('layouts.app')

@section('title', 'Tambah Tanda Terima')

@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">
        <h4>Tambah Tanda Terima</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('document-receipts.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Pilih Delivery Order *</label>
                    <select name="delivery_order_id" id="delivery_order_id" class="form-control" required>
                        <option value="">-- Pilih DO --</option>
                        @foreach($deliveryOrders as $do)
                        <option value="{{ $do->id }}">{{ $do->do_number }} - {{ $do->poCustomer->customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Tanggal Terima *</label>
                    <input type="date" name="receipt_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <div id="invoiceSection" style="display: none;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Nomor Invoice *</label>
                        <select name="invoice_number" id="invoice_number" class="form-control" required>
                            <option value="">-- Pilih Invoice --</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Nomor Faktur Pajak</label>
                        <input type="text" name="tax_invoice_number" id="tax_invoice_number" class="form-control" readonly>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label>Catatan</label>
                <textarea name="notes" class="form-control" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('document-receipts.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $('#delivery_order_id').change(function() {
        var doId = $(this).val();
        if (!doId) {
            $('#invoiceSection').hide();
            return;
        }

        $.ajax({
            url: '{{ route("document-receipts.get-invoices-by-do", "") }}/' + doId,
            method: 'GET',
            dataType: 'json',
            success: function(invoices) {
                var options = '<option value="">-- Pilih Invoice --</option>';
                if (invoices.length > 0) {
                    $.each(invoices, function(i, inv) {
                        options += '<option value="' + inv.invoice_number + '" data-tax="' + (inv.tax_invoice_number || '') + '">' + inv.invoice_number + '</option>';
                    });
                } else {
                    options = '<option value="">Tidak ada invoice</option>';
                }
                $('#invoice_number').html(options);
                $('#invoiceSection').show();
            },
            error: function() {
                alert('Gagal mengambil data invoice');
            }
        });
    });

    $('#invoice_number').change(function() {
        var tax = $(this).find(':selected').data('tax') || '';
        $('#tax_invoice_number').val(tax);
    });
</script>
@endsection 