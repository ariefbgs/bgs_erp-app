@extends('layouts.app')

@section('title', 'Customer Management')

@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">
        <h4>Customer Management</h4>
    </div>
    <div class="card-body">
        <a href="{{ route('customers.create') }}" class="btn btn-success mb-3">Add New Customer</a>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Code</th>
                        <th class="text-center">Name</th>
                        <th class="text-center">Email</th>
                        <th class="text-center">Phone</th>
                        <th class="text-center">Address</th>
                        <th class="text-center">Shipping Address</th>
                        <th class="text-center">Document Address</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $index => $customer)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center">{{ $customer->customer_code }}</td>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->email ?? '-' }}</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                        <td>{{ $customer->address ?? '-' }}</td>
                        <td>{{ $customer->shipping_address ?? '-' }}</td>
                        <td>{{ $customer->document_address ?? '-' }}</td>
                        <td class="text-center">
                            <a href="{{ route('customers.show', $customer->id) }}" 
                            class="btn btn-info btn-sm">
                                <i class="bi bi-eye"></i>
                            </a> 

                            <a href="{{ route('customers.edit', $customer->id) }}" 
                            class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>

                            <form action="{{ route('customers.destroy', $customer->id) }}" 
                                method="POST" 
                                class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this customer?')">
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