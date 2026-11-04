@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-invoice-payments-index bgs-mode-index')
@section('title', $kind === 'customer' ? 'Payment Customer' : 'Payment Supplier')
@section('content')
<div class="card main-card">
<div class="card-header-custom bgs-page-header"><h4>{{ $kind === 'customer' ? 'Payment dari Customer' : 'Payment ke Supplier' }}</h4></div>
<div class="card-body p-4">
@if($kind === 'supplier')
<a href="{{ route('supplier-invoice-input.create') }}" class="btn btn-primary mb-3 me-2">Input Invoice Supplier</a>
<a href="{{ route('supplier-invoice-input.index') }}" class="btn btn-secondary mb-3">Daftar Invoice Supplier</a>
@endif
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<p class="text-muted mb-4">Pilih invoice yang sudah lunas, kemudian catat pembayaran. Status Paid tidak mengubah nilai invoice atau alokasi partial invoice.</p>
<form method="GET" action="{{ route('invoice-payments.index', $kind) }}" class="bgs-filter-form row g-2 mb-3">
<div class="col-md-6"><label class="filter-label" for="payment-search">Search</label><input id="payment-search" name="search" class="form-control" placeholder="Nomor invoice / nama" value="{{ request('search') }}"></div>
<div class="col-md-3"><label class="filter-label" for="payment-status">Status</label><select id="payment-status" name="status" class="form-select"><option value="unpaid" @selected(request('status','unpaid')==='unpaid')>Belum Paid</option><option value="paid" @selected(request('status')==='paid')>Paid</option></select></div>
<div class="col-md-3 align-self-end"><button class="btn btn-primary">Search</button> <a class="btn btn-secondary" href="{{ route('invoice-payments.index', $kind) }}">Reset</a></div>
</form>
<form method="POST" action="{{ route('invoice-payments.store', $kind) }}" onsubmit="return confirm('Konfirmasi invoice ini sudah lunas dan ubah status menjadi Paid?')">
@csrf
<div class="table-responsive quotation-table-wrap"><table class="table table-modern table-hover align-middle quotation-table bgs-index-table bgs-actions-compact"><thead><tr><th class="text-center col-action">Pilih</th><th>Invoice</th><th class="col-customer">{{ $kind === 'customer' ? 'Customer' : 'Supplier' }}</th><th>Tanggal invoice</th><th>Total invoice</th><th>Status</th><th>Tanggal bayar</th><th>Referensi</th></tr></thead><tbody>
@forelse($invoices as $invoice)
<tr><td class="text-center col-action">@if($invoice->paid_status !== 'paid')<input aria-label="Pilih {{ $invoice->invoice_number }}" class="form-check-input" type="radio" name="invoice_id" value="{{ $invoice->id }}" @checked((string)old('invoice_id')===(string)$invoice->id) required>@else — @endif</td><td>{{ $invoice->invoice_number }}</td><td class="col-customer customer-name">{{ $invoice->party_name }}</td><td>{{ $invoice->invoice_date }}</td><td class="text-end">Rp {{ number_format($invoice->total, 2, ',', '.') }}</td><td>{{ ucfirst($invoice->paid_status) }}</td><td>{{ $payments->get($invoice->id)?->payment_date ?? '-' }}</td><td>{{ $payments->get($invoice->id)?->reference ?? '-' }}</td></tr>
@empty<tr><td colspan="8" class="text-center empty-state">Tidak ada invoice sesuai filter.</td></tr>@endforelse
</tbody></table></div>
@if(request('status','unpaid') !== 'paid' && $invoices->count())
<div class="row g-3 mt-3"><div class="col-md-3"><label class="form-label" for="payment-date">Tanggal pembayaran</label><input id="payment-date" name="payment_date" type="date" class="form-control" value="{{ old('payment_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required></div><div class="col-md-5"><label class="form-label" for="payment-reference">Referensi pembayaran</label><input id="payment-reference" name="reference" class="form-control" maxlength="255" value="{{ old('reference') }}"></div></div>
<label class="d-block my-3"><input type="checkbox" name="confirmed" value="1" required> Saya memastikan invoice yang dipilih sudah lunas.</label><div class="d-flex justify-content-end mt-4 pt-3 border-top"><button class="btn btn-primary">Simpan Payment &amp; Tandai Paid</button></div>
@endif
</form>
<div class="mt-3 d-flex justify-content-end">{{ $invoices->links() }}</div>
</div></div>
@endsection
