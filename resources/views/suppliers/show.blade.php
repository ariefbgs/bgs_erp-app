@extends('layouts.app')

@section('title', 'Supplier Detail')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Supplier Detail</h3>
            <div class="card-tools">
                <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Supplier Code</th>
                            <td>{{ $supplier->supplier_code }}</td>
                        </tr>
                        <tr>
                            <th>Supplier Name</th>
                            <td>{{ $supplier->name }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $supplier->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Phone Number</th>
                            <td>{{ $supplier->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>{{ $supplier->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>NPWP</th>
                            <td>{{ $supplier->tax_number ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td>{{ $supplier->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection