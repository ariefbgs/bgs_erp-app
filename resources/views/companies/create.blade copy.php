@extends('layouts.app')

@section('title', 'Add Company')

@section('content')
<div class="card">
    <div class="card-header bg-success text-white">
        <h4>Add New Company</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('companies.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company Code <span class="text-danger">*</span></label>
                    <input type="text" name="company_code" class="form-control @error('company_code') is-invalid @enderror" required>
                    @error('company_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company Logo</label>
                    <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                    <small class="text-muted">Format: JPEG, PNG, JPG, GIF. Max: 2MB</small>
                    @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Di dalam form create, setelah field logo atau di bagian bawah -->
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">DO Signature</label>
                        <input type="file" name="ttd_do" class="form-control @error('ttd_do') is-invalid @enderror" accept="image/*">
                        <small class="text-muted">Format: JPEG, PNG, JPG. Max: 2MB</small>
                        @error('ttd_do')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Invoice Signature (1)</label>
                        <input type="file" name="ttd_inv" class="form-control @error('ttd_inv') is-invalid @enderror" accept="image/*">
                        <small class="text-muted">Format: JPEG, PNG, JPG. Max: 2MB</small>
                        @error('ttd_inv')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Invoice Signature (2)</label>
                        <input type="file" name="ttd_inv2" class="form-control @error('ttd_inv2') is-invalid @enderror" accept="image/*">
                        <small class="text-muted">Format: JPEG, PNG, JPG. Max: 2MB</small>
                        @error('ttd_inv2')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Fax</label>
                    <input type="text" name="fax" class="form-control">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control" placeholder="https://...">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">NPWP</label>
                    <input type="text" name="npwp" class="form-control">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control">
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Bank</label>
                    <input type="text" name="bank_name" class="form-control" placeholder="Nama Bank">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="bank_account_number" class="form-control">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Account Holder Name</label>
                    <input type="text" name="bank_account_name" class="form-control">
                </div>
                
                <div class="col-md-12 mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection