@extends('layouts.app')

@section('title', 'Supplier Management')

@section('content')
<style>
    .text-right {
        text-align: right;
    }
    .total-column {
        text-align: right;
        font-weight: bold;
    }
</style>
<div class="card">
    <div class="card-header bg-primary text-white">
        <h4>Supplier Management</h4>
    </div>
    <div class="card-body">
        <a href="{{ route('suppliers.create') }}" class="btn btn-success mb-3">Add Supplier</a>
        
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Supplier Code</th>
                        <th class="text-center">Name</th>
                        <th class="text-center">Email</th>
                        <th class="text-center">Phone Number</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suppliers as $index => $supplier)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $supplier->supplier_code }}</td>
                        <td>{{ $supplier->name }}</td>
                        <td>{{ $supplier->email ?? '-' }}</td>
                        <td>{{ $supplier->phone ?? '-' }}</td>
                        <td class="text-center">
                            <a href="{{ route('suppliers.show', $supplier->id) }}" 
                            class="btn btn-info btn-sm">
                                <i class="bi bi-eye"></i>
                            </a>

                            <a href="{{ route('suppliers.edit', $supplier->id) }}" 
                            class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>

                            <form action="{{ route('suppliers.destroy', $supplier->id) }}" 
                                method="POST" 
                                class="d-inline">
                                @csrf
                                @method('DELETE')

                                <button type="submit" 
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Yakin hapus?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection