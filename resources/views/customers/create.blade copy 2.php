@extends('layouts.app')

@section('title', '')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-success text-white">
            <h3 class="card-title">Add New Customer</h3>
            <div class="card-tools">
                <a href="{{ route('customers.index') }}" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-top fade show mb-4" style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important; border-radius: 6px;">
                    <i class="bi bi-exclamation-triangle-fill me-3 fs-4 mt-1"></i>
                    <div>
                        <strong class="d-block mb-1">Gagal Menyimpan Data!</strong>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size: 0.8rem;"></button>
                </div>
            @endif
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                
                <!-- Data Dasar Customer -->
                <h5 class="mb-3">Basic Customer Data</h5>
                <hr>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="customer_code" class="form-label">Customer Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('customer_code') is-invalid @enderror" 
                               id="customer_code" name="customer_code" value="{{ old('customer_code') }}" required>
                        @error('customer_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="text"
                            class="form-control @error('email') is-invalid @enderror"
                            id="email"
                            name="email"
                            value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" name="phone" value="{{ old('phone') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                  id="address" name="address" rows="3">{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12 mb-3">
                        <label for="shipping_address" class="form-label">Shipping Address</label>
                        <textarea class="form-control @error('shipping_address') is-invalid @enderror" 
                                id="shipping_address" name="shipping_address" rows="3">{{ old('shipping_address') }}</textarea>
                        @error('shipping_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="document_address" class="form-label">Document Shipping Address</label>
                        <textarea class="form-control @error('document_address') is-invalid @enderror" 
                                id="document_address" name="document_address" rows="3">{{ old('document_address') }}</textarea>
                        @error('document_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="tax_number" class="form-label">NPWP</label>
                        <input type="text" class="form-control @error('tax_number') is-invalid @enderror" 
                               id="tax_number" name="tax_number" value="{{ old('tax_number') }}">
                        @error('tax_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- PIC Quotation -->
                <h5 class="mb-3 mt-4">PIC Quotation</h5>
                <hr>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="pic_quotation_name" class="form-label">PIC Quotation</label>
                        <input type="text" class="form-control @error('pic_quotation_name') is-invalid @enderror" 
                               id="pic_quotation_name" name="pic_quotation_name" value="{{ old('pic_quotation_name') }}">
                        @error('pic_quotation_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="pic_quotation_phone" class="form-label">No. Telepon PIC Quotation</label>
                        <input type="text" class="form-control @error('pic_quotation_phone') is-invalid @enderror" 
                               id="pic_quotation_phone" name="pic_quotation_phone" value="{{ old('pic_quotation_phone') }}">
                        @error('pic_quotation_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- PIC Delivery Order -->
                <h5 class="mb-3 mt-4">PIC Delivery Order</h5>
                <hr>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="pic_do_name" class="form-label">PIC Delivery Order</label>
                        <input type="text" class="form-control @error('pic_do_name') is-invalid @enderror" 
                               id="pic_do_name" name="pic_do_name" value="{{ old('pic_do_name') }}">
                        @error('pic_do_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="pic_do_phone" class="form-label">PIC Delivery Order Phone Number</label>
                        <input type="text" class="form-control @error('pic_do_phone') is-invalid @enderror" 
                               id="pic_do_phone" name="pic_do_phone" value="{{ old('pic_do_phone') }}">
                        @error('pic_do_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- PIC Invoice -->
                <h5 class="mb-3 mt-4">PIC Invoice</h5>
                <hr>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="pic_invoice_name" class="form-label">PIC Invoice</label>
                        <input type="text" class="form-control @error('pic_invoice_name') is-invalid @enderror" 
                               id="pic_invoice_name" name="pic_invoice_name" value="{{ old('pic_invoice_name') }}">
                        @error('pic_invoice_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="pic_invoice_phone" class="form-label">PIC Invoice Phone Number </label>
                        <input type="text" class="form-control @error('pic_invoice_phone') is-invalid @enderror" 
                               id="pic_invoice_phone" name="pic_invoice_phone" value="{{ old('pic_invoice_phone') }}">
                        @error('pic_invoice_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save
                    </button>
                    <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection