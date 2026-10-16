@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-invoice-customers-show bgs-mode-screen')
@section('title', 'Detail Sales Invoice')
@section('content')

<div class="container-fluid py-4"><div class="card main-card">
<div class="card-header card-header-custom py-3 bgs-page-header"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
<h4><i class="bi bi-receipt-cutoff me-2"></i>Detail Sales Invoice</h4><div class="d-flex gap-2 flex-wrap">
<a href="{{ route('invoice-customers.edit',$invoice->id) }}" class="btn btn-warning btn-sm fw-bold px-3"><i class="bi bi-pencil-square me-1"></i>Edit Invoice</a>
<a href="{{ route('invoice-customers.print',$invoice->id) }}" class="btn btn-primary btn-sm fw-bold px-3" target="_blank"><i class="bi bi-printer me-1"></i>Print PDF</a>
<a href="{{ route('invoice-customers.print_invoice',$invoice->id) }}" class="btn btn-success btn-sm fw-bold px-3" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>Print Invoice</a>
<a href="{{ route('invoice-customers.index') }}" class="btn btn-outline-light btn-sm fw-bold px-3"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div></div></div>
<div class="card-body p-4">
<div class="row g-4 mb-5">
<div class="col-md-6"><div class="h-100 p-3 rounded-3 info-box-bg"><div class="section-sub-title"><i class="bi bi-info-circle-fill me-2"></i>Invoice Information</div><table class="table info-table bgs-detail-table align-middle">
<tr><td class="label">Invoice #</td><td class="value fw-bold text-dark">: {{ $invoice->invoice_number }}</td></tr>
<tr><td class="label">Invoice Date</td><td class="value">: {{ date('d M Y',strtotime($invoice->invoice_date)) }}</td></tr>
<tr><td class="label">Due Date</td><td class="value">: {{ $invoice->due_date?date('d M Y',strtotime($invoice->due_date)):'-' }}</td></tr>
<tr><td class="label">Invoice Type</td><td class="value">: <span class="badge {{ $invoice->type==='proforma'?'bg-secondary':'bg-primary' }} badge-custom">{{ $invoice->type==='proforma'?'Proforma':'Sales Invoice' }}</span></td></tr>
<tr><td class="label">Invoice Status</td><td class="value">: <span class="badge {{ $invoice->status==='completed'?'bg-success':($invoice->status==='cancelled'?'bg-danger':'bg-warning text-dark') }} badge-custom">{{ ucfirst($invoice->status??'-') }}</span></td></tr>
<tr><td class="label">Payment Status</td><td class="value">: <span class="badge {{ $invoice->payment_status==='paid'?'bg-success':'bg-secondary' }} badge-custom">{{ ucfirst($invoice->payment_status??'-') }}</span></td></tr>
<tr><td class="label">Payment Terms</td><td class="value fw-semibold">: {{ $invoice->payment_terms??'-' }}</td></tr><tr><td class="label">Delivery Time</td><td class="value">: {{ $invoice->delivery_time??'-' }}</td></tr>
</table></div></div>
<div class="col-md-6"><div class="h-100 p-3 rounded-3 info-box-bg"><div class="section-sub-title"><i class="bi bi-building-fill me-2"></i>Customer & PO Reference</div><table class="table info-table bgs-detail-table align-middle">
<tr><td class="label">Customer</td><td class="value fw-bold text-dark">: {{ $invoice->poCustomer->customer->name??'-' }}</td></tr><tr><td class="label">PO Customer #</td><td class="value fw-semibold text-primary">: {{ $invoice->poCustomer->po_number??'-' }}</td></tr>
<tr><td class="label">PO Status</td><td class="value">: <span class="badge bg-info text-dark badge-custom">{{ ucfirst($invoice->poCustomer->status??'-') }}</span></td></tr><tr><td class="label">PO Invoice Status</td><td class="value">: <span class="badge {{ $invoice->poCustomer->invoice_status==='completed'?'bg-success':'bg-warning text-dark' }} badge-custom">{{ ucfirst($invoice->poCustomer->invoice_status??'-') }}</span></td></tr>
<tr><td class="label">Tax Invoice #</td><td class="value">: {{ $invoice->tax_invoice_number??'-' }}</td></tr><tr><td class="label">Tax Attachment</td><td class="value">: @if($invoice->tax_invoice_attachment)<a href="{{ route('tax-invoice.view',$invoice->id) }}" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold"><i class="bi bi-file-earmark-arrow-down-fill me-1"></i>View / Download</a>@else<span class="text-muted">No attachment</span>@endif</td></tr><tr><td class="label">Total Item</td><td class="value">: {{ $invoice->details->count() }} Item</td></tr>
</table></div></div></div>
<div class="mb-5"><div class="section-sub-title"><i class="bi bi-box-seam-fill me-2"></i>Product Specification Details</div><div class="border rounded-3 overflow-hidden shadow-sm"><div class="table-responsive"><table class="table table-modern align-middle m-0 bgs-detail-table">
<thead><tr><th class="text-center">No</th><th>Brand</th><th>Product Code</th><th>Product Name</th><th class="text-center">Qty</th><th class="text-center">Unit</th><th class="text-end">Unit Price</th><th class="text-end">Subtotal</th></tr></thead><tbody>
@forelse($invoice->details as $index=>$detail)<tr><td class="text-center">{{ $index+1 }}</td><td>{{ $detail->product->brand??'-' }}</td><td>{{ $detail->product->product_code??'-' }}</td><td class="fw-semibold text-dark">{{ $detail->product->name??'-' }}</td><td class="text-center">{{ number_format($detail->quantity,0,',','.') }}</td><td class="text-center">{{ $detail->product->unit??'-' }}</td><td class="text-end">Rp {{ number_format($detail->unit_price,0,',','.') }}</td><td class="text-end fw-bold text-dark">Rp {{ number_format($detail->subtotal,0,',','.') }}</td></tr>@empty<tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No product detail available.</td></tr>@endforelse
</tbody></table></div></div></div>
<div class="row g-4 text-start mt-2"><div class="col-md-7"><div class="p-3 border rounded-3 h-100 info-box-bg"><div class="section-sub-title mb-2"><i class="bi bi-pencil-square me-2"></i>Remarks & Internal Notes</div><p class="text-secondary mb-0 bgs-u-8f9e6c32953b">{!! nl2br(e($invoice->notes??'-')) !!}</p></div></div>
<div class="col-md-5"><div class="summary-card"><div class="summary-line"><span>Invoice Amount</span><span class="fw-bold">Rp {{ number_format((($invoice->dp_amount??0)>0?$invoice->dp_amount:$invoice->subtotal),0,',','.') }}</span></div>@if($invoice->allocation_mode === 'PERCENTAGE' && $invoice->dp_amount>0)<div class="summary-line"><span>Payment / DP ({{ $invoice->dp_percent??0 }}%)</span><span class="fw-bold">Rp {{ number_format($invoice->dp_amount,0,',','.') }}</span></div>@endif<div class="summary-line"><span>Discount ({{ $invoice->discount_percent??0 }}%)</span><span class="text-warning fw-bold">- Rp {{ number_format($invoice->discount_amount??0,0,',','.') }}</span></div><div class="summary-line"><span>PPN ({{ $invoice->tax_percent??0 }}%)</span><span class="fw-bold">+ Rp {{ number_format($invoice->tax_amount??0,0,',','.') }}</span></div><div class="summary-line"><span>PPh 23 ({{ $invoice->pph23_percent??0 }}%)</span><span class="text-danger fw-bold">- Rp {{ number_format($invoice->pph23_amount??0,0,',','.') }}</span></div><div class="summary-line grand-total"><span>Grand Total Invoice</span><span class="total-rp">Rp {{ number_format($invoice->total??0,0,',','.') }}</span></div><div class="summary-line remaining"><span>PO Remaining Amount</span><span>Rp {{ number_format($invoice->remaining_amount??0,0,',','.') }}</span></div></div></div></div>
</div></div></div>
@endsection
