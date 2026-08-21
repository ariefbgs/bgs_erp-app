@extends('layouts.app')

@section('title', 'Tambah Supplier')

@section('content')
<div class="card">
    <div class="card-header bg-primary text-white">
        <h4>Tambah Supplier Baru</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('suppliers.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Kode Supplier <span class="text-danger">*</span></label>
                    <input type="text" name="supplier_code" class="form-control @error('supplier_code') is-invalid @enderror" value="{{ old('supplier_code') }}" required>
                    @error('supplier_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label>Nama Supplier <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label>Telepon</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                </div>
                
                <div class="col-md-12 mb-3">
                    <label>Alamat</label>
                    <textarea name="address" class="form-control" rows="3">{{ old('address') }}</textarea>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label>NPWP</label>
                    <input type="text" name="tax_number" class="form-control" value="{{ old('tax_number') }}">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>
@endsection