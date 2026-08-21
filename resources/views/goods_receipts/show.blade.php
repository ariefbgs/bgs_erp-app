{{-- resources/views/goods_receipts/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h3>Detail Goods Receipt</h3>
            <div>
                <a href="{{ route('goods-receipts.index') }}" class="btn btn-secondary">Kembali</a>
                <button onclick="window.print()" class="btn btn-primary">Print</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="35%">No Receipt</th>
                            <td>{{ $receipt->receipt_number }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Terima</th>
                            <td>{{ date('d/m/Y', strtotime($receipt->receipt_date)) }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-{{ $receipt->status == 'completed' ? 'success' : 'warning' }}">
                                    {{ ucfirst($receipt->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Diterima Oleh</th>
                            <td>{{ $receipt->received_by }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="35%">PO Supplier</th>
                            <td>{{ $receipt->poSupplier->po_supplier_number }}</td>
                        </tr>
                        <tr>
                            <th>Supplier</th>
                            <td>{{ $receipt->poSupplier->supplier->name }}</td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td>{{ $receipt->notes ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <h5>Items Diterima</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Qty PO</th>
                        <th>Qty Diterima</th>
                        <th>Sisa</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipt->details as $detail)
                    <tr>
                        <td>
                            {{ $detail->product->name }}<br>
                            <small>{{ $detail->product->product_code }} - {{ $detail->product->brand }}</small>
                        </td>
                        <td>{{ number_format($detail->poSupplierDetail->quantity, 0, ',', '.') }} {{ $detail->product->unit }}</td>
                        <td>{{ number_format($detail->quantity_received, 0, ',', '.') }} {{ $detail->product->unit }}</td>
                        <td>{{ number_format($detail->quantity_remaining, 0, ',', '.') }} {{ $detail->product->unit }}</td>
                        <td>{{ $detail->notes ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .card-header .btn {
        display: none !important;
    }
}
</style>
@endsection