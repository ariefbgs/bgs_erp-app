@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-supplier-invoice-input-show bgs-mode-screen')
@section('title','Detail Invoice Supplier')
@section('content')
<div class="card"><div class="card-header bg-primary text-white bgs-page-header"><h4>Detail Invoice Supplier</h4></div><div class="card-body">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<p><strong>Invoice:</strong> {{ $invoice->invoice_number }}<br><strong>Supplier:</strong> {{ $invoice->supplier_name }}<br><strong>PO Supplier:</strong> {{ $invoice->po_supplier_number }}<br><strong>Tanggal:</strong> {{ $invoice->invoice_date }}<br><strong>Jatuh tempo:</strong> {{ $invoice->due_date ?? '-' }}<br><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
<div class="table-responsive"><table class="table bgs-detail-table align-middle"><thead><tr><th>No</th><th>Product Code</th><th>Product</th><th>Qty</th><th>Harga</th><th>Subtotal sebelum diskon</th></tr></thead><tbody>@foreach($details as $detail)<tr><td>{{ $loop->iteration }}</td><td>{{ $detail->product_code ?? '-' }}</td><td>{{ $detail->product_name ?? '-' }}</td><td>{{ $detail->quantity }}</td><td>Rp {{ number_format($detail->unit_price,2,',','.') }}</td><td>Rp {{ number_format($detail->subtotal,2,',','.') }}</td></tr>@endforeach</tbody></table></div>
<p>Nilai invoice setelah diskon: <strong>Rp {{ number_format($invoice->amount,2,',','.') }}</strong><br>Pajak: <strong>Rp {{ number_format($invoice->tax,2,',','.') }}</strong><br>Total: <strong>Rp {{ number_format($invoice->total,2,',','.') }}</strong></p>
<a class="btn btn-success" href="{{ route('invoice-payments.index',['kind'=>'supplier','search'=>$invoice->invoice_number,'status'=>$invoice->status==='paid'?'paid':'unpaid']) }}">Payment Supplier</a> <a class="btn btn-secondary" href="{{ route('supplier-invoice-input.index') }}">Back</a>
</div></div>
@endsection
