@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-supplier-invoice-input-index bgs-mode-index')
@section('title','Invoice Supplier')
@section('content')
<div class="card main-card"><div class="card-header-custom bgs-page-header"><h4>Invoice Supplier</h4></div><div class="card-body p-4">
<div class="d-flex flex-wrap gap-2 mb-3"><a href="{{ route('supplier-invoice-input.create') }}" class="btn btn-primary">Input Invoice Supplier</a>
<a href="{{ route('invoice-payments.index','supplier') }}" class="btn btn-secondary">Payment Supplier</a></div>
<form method="GET" action="{{ route('supplier-invoice-input.index') }}" class="bgs-filter-form row g-2 mb-3">
<div class="col-md-6"><label for="invoice-search" class="filter-label">Search</label><input id="invoice-search" name="search" class="form-control" placeholder="Invoice / PO / Supplier" value="{{ request('search') }}"></div>
<div class="col-md-3"><label for="invoice-status" class="filter-label">Status</label><select id="invoice-status" name="status" class="form-select"><option value="">Semua</option>@foreach(['unpaid','paid','overdue'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
<div class="col-md-3 align-self-end"><button class="btn btn-primary">Search</button> <a href="{{ route('supplier-invoice-input.index') }}" class="btn btn-secondary">Reset</a></div>
</form>
<div class="table-responsive quotation-table-wrap"><table class="table table-modern table-hover align-middle quotation-table bgs-index-table bgs-actions-compact"><thead><tr><th class="text-center col-action">Actions</th><th>No</th><th>Invoice</th><th class="col-customer">Supplier</th><th>PO Supplier</th><th>Tanggal</th><th>Jatuh tempo</th><th>Total</th><th>Status</th></tr></thead><tbody>
@forelse($invoices as $invoice)<tr><td class="text-center col-action"><div class="d-flex justify-content-center action-btn-group"><a class="btn btn-info btn-sm text-white" title="Detail" aria-label="Detail invoice supplier" href="{{ route('supplier-invoice-input.show',$invoice->id) }}"><i class="bi bi-eye" aria-hidden="true"></i></a></div></td><td>{{ $invoices->firstItem()+$loop->index }}</td><td>{{ $invoice->invoice_number }}</td><td class="col-customer customer-name">{{ $invoice->supplier_name }}</td><td>{{ $invoice->po_supplier_number }}</td><td>{{ $invoice->invoice_date }}</td><td>{{ $invoice->due_date ?? '-' }}</td><td class="text-end">Rp {{ number_format($invoice->total,2,',','.') }}</td><td>{{ ucfirst($invoice->status) }}</td></tr>
@empty<tr><td colspan="9" class="text-center empty-state">Belum ada invoice supplier sesuai filter.</td></tr>@endforelse
</tbody></table></div><div class="mt-3 d-flex justify-content-end">{{ $invoices->links() }}</div></div></div>
@endsection
