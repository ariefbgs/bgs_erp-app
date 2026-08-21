@extends('layouts.app')

@section('title', '')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Detail Customer</h3>
            <div class="card-tools">
                <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Kode Customer</th>
                            <td>{{ $customer->customer_code }}</td>
                        </tr>
                        <tr>
                            <th>Nama Customer</th>
                            <td>{{ $customer->name }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $customer->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Telepon</th>
                            <td>{{ $customer->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Alamat</th>
                            <td>{{ $customer->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Alamat Pengiriman Barang</th>
                            <td>{{ $customer->shipping_address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Alamat Pengiriman Dokumen</th>
                            <td>{{ $customer->document_address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>NPWP</th>
                            <td>{{ $customer->tax_number ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Dibuat pada</th>
                            <td>{{ $customer->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection