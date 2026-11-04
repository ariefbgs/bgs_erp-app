@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-supplier-invoice-input-create bgs-mode-create')
@section('title','Input Invoice Supplier')
@section('content')

<div class="card main-card"><div class="card-header-custom bgs-page-header"><h4>Input Invoice Supplier</h4></div><div class="card-body p-4">
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<p class="text-muted mb-4">Masukkan nomor invoice dari supplier. Invoice ini mencakup seluruh PO; harga, diskon, pajak, dan total mengikuti PO yang dipilih.</p>
@if($pos->isEmpty())<div class="alert alert-warning">Belum ada PO Supplier yang dapat dibuatkan invoice. Siapkan PO dengan status Sent, Confirmed, atau Received dan detail barang, yang belum memiliki invoice.</div>@endif
<form method="POST" action="{{ route('supplier-invoice-input.store') }}">@csrf
<div class="row g-3"><div class="col-md-12"><label for="po_supplier_id" class="form-label">PO Supplier *</label><select class="form-select" id="po_supplier_id" name="po_supplier_id" required><option value="">Pilih PO Supplier</option>@foreach($pos as $po)<option value="{{ $po->id }}" data-amount="{{ $po->subtotal - $po->discount_amount }}" data-tax="{{ $po->tax_amount }}" data-total="{{ $po->total }}" @selected((string)old('po_supplier_id')===(string)$po->id)>{{ $po->po_supplier_number }} — {{ $po->supplier_name }} — Rp {{ number_format($po->total,2,',','.') }}</option>@endforeach</select></div>
<div class="col-md-6"><label for="invoice_number" class="form-label">Nomor Invoice Supplier *</label><input id="invoice_number" name="invoice_number" class="form-control" maxlength="50" value="{{ old('invoice_number') }}" required></div>
<div class="col-md-3"><label for="invoice_date" class="form-label">Tanggal Invoice *</label><input id="invoice_date" name="invoice_date" type="date" class="form-control" value="{{ old('invoice_date',now()->format('Y-m-d')) }}" required></div>
<div class="col-md-3"><label for="due_date" class="form-label">Jatuh Tempo</label><input id="due_date" name="due_date" type="date" class="form-control" value="{{ old('due_date') }}"></div></div>
<div class="info-box-bg mt-3 mb-3">Nilai setelah diskon: <strong id="po-amount">—</strong><br>Pajak: <strong id="po-tax">—</strong><br>Total: <strong id="po-total">—</strong></div>
<label class="d-block mb-3"><input type="checkbox" name="confirmed" value="1" required @checked(old('confirmed'))> Saya memastikan nomor dan nilai invoice supplier sesuai PO yang dipilih.</label>
<div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top"><button class="btn btn-primary" @disabled($pos->isEmpty())>Simpan Invoice Supplier</button> <a class="btn btn-secondary" href="{{ route('supplier-invoice-input.index') }}">Back</a></div>
</form></div></div>
@endsection
@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function(){
    $('#po_supplier_id').select2({width:'100%',placeholder:'Pilih PO Supplier',allowClear:true});
    $('#po_supplier_id').on('select2:open',function(){const instance=$(this).data('select2');setTimeout(function(){if(instance && instance.isOpen() && instance.dropdown.$search.length)instance.dropdown.$search[0].focus();},50);});
    function preview(){const option=document.getElementById('po_supplier_id').selectedOptions[0];['amount','tax','total'].forEach(function(key){const value=option && option.dataset[key];document.getElementById('po-'+key).textContent=value ? new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR'}).format(Number(value)) : '—';});}
    $('#po_supplier_id').on('change',preview);preview();
});
</script>
@endpush
