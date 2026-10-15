@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-reports-index bgs-mode-index')

@section('title', 'Laporan')

@section('content')
<div class="card">
    <div class="card-header bg-primary text-white bgs-page-header">
        <h4>Laporan Transaksi</h4>
    </div>
    <div class="card-body">
        <form method="GET" class="bgs-filter-form row g-3">
            <div class="col-md-4">
                <label class="form-label">Periode Mulai</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date', date('Y-m-01')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Periode Akhir</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date', date('Y-m-d')) }}">
            </div>
            <div class="col-md-4 d-flex align-items-end flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('reports.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bgs-section-header">Pembelian</div>
            <div class="card-body">
                <a href="{{ route('reports.purchasing.pdf', request()->query()) }}" class="btn btn-danger mb-2">PDF</a>
                <a href="{{ route('reports.purchasing.excel', request()->query()) }}" class="btn btn-success mb-2">Excel</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bgs-section-header">Penjualan</div>
            <div class="card-body">
                <a href="{{ route('reports.sales.pdf', request()->query()) }}" class="btn btn-danger mb-2">PDF</a>
                <a href="{{ route('reports.sales.excel', request()->query()) }}" class="btn btn-success mb-2">Excel</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 mt-3">
        <div class="card">
            <div class="card-header bgs-section-header">Payment ke Supplier</div>
            <div class="card-body">
                <a href="{{ route('reports.payment-supplier.pdf', request()->query()) }}" class="btn btn-danger mb-2">PDF</a>
                <a href="{{ route('reports.payment-supplier.excel', request()->query()) }}" class="btn btn-success mb-2">Excel</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 mt-3">
        <div class="card">
            <div class="card-header bgs-section-header">Payment dari Customer</div>
            <div class="card-body">
                <a href="{{ route('reports.payment-customer.pdf', request()->query()) }}" class="btn btn-danger mb-2">PDF</a>
                <a href="{{ route('reports.payment-customer.excel', request()->query()) }}" class="btn btn-success mb-2">Excel</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 mt-3">
        <div class="card">
            <div class="card-header bgs-section-header">Delivery Order</div>
            <div class="card-body">
                <a href="{{ route('reports.delivery.pdf', request()->query()) }}" class="btn btn-danger mb-2">PDF</a>
                <a href="{{ route('reports.delivery.excel', request()->query()) }}" class="btn btn-success mb-2">Excel</a>
            </div>
        </div>
    </div>
</div>
@endsection