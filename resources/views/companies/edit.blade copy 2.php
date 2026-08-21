@extends('layouts.app')

@section('title', 'Edit Perusahaan')

@section('content')
<div class="card">
    <div class="card-header bg-warning text-white">
        <h4>Edit Perusahaan</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('companies.update', $company->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Baris 1: Kode & Nama --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Kode Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" name="company_code" class="form-control @error('company_code') is-invalid @enderror" value="{{ old('company_code', $company->company_code) }}" required>
                    @error('company_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $company->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Baris 2: Logo Perusahaan --}}
            <div class="row">
                <div class="col-md-12 mb-4">
                    <label for="logo" class="form-label fw-bold">Logo Perusahaan</label>
                    <input type="file" name="logo" id="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                    <div class="form-text">Format: JPEG, PNG, JPG, GIF. Maks 2MB. Kosongkan jika tidak ingin mengubah.</div>
                    @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($company->logo)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $company->logo) }}" 
                                alt="Logo" 
                                style="max-height: 80px; border:1px solid #ccc; padding:4px; border-radius:6px;">
                            <br>
                            <a href="{{ asset('storage/' . $company->logo) }}" target="_blank" class="btn btn-sm btn-info text-white mt-1">Lihat Logo Asli</a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Baris 3: PIC DO & TTD DO --}}
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label for="pic_sales_support" class="form-label fw-bold">Sales Support</label>
                    <input type="text" name="pic_sales_support" class="form-control" 
                        value="{{ old('pic_sales_support', $company->pic_sales_support) }}">
                </div>
                
                <div class="col-md-6 mb-4">
                    <label for="ttd_sales_support" class="form-label fw-bold">Tanda Tangan Sales Support</label>
                    <input type="file" name="ttd_sales_support" class="form-control" accept="image/*">
                    @if($company->ttd_sales_support)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $company->ttd_sales_support) }}" 
                                alt="TTD Sales Support" 
                                style="max-height: 80px; border:1px solid #ccc; padding:4px; border-radius:6px;">
                        </div>
                    @endif
                </div>
                <div class="col-md-6 mb-4">
                    <label for="pic_do" class="form-label fw-bold">Penanggung Jawab DO</label>
                    <input type="text" name="pic_do" class="form-control" value="{{ old('pic_do', $company->pic_do) }}">
                </div>
                <div class="col-md-6 mb-4">
                    <label for="ttd_do" class="form-label fw-bold">Tanda Tangan DO</label>
                    <input type="file" name="ttd_do" class="form-control" accept="image/*">
                    @if($company->ttd_do)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $company->ttd_do) }}" 
                                alt="TTD DO" 
                                style="max-height: 80px; border:1px solid #ccc; padding:4px; border-radius:6px;">
                        </div>
                    @endif
                </div>
            </div>

            {{-- Baris 4: PIC INV & TTD INV --}}
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="pic_inv" class="form-label fw-bold">Penanggung Jawab Invoice (1)</label>
                    <input type="text" name="pic_inv" class="form-control" value="{{ old('pic_inv', $company->pic_inv) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="ttd_inv" class="form-label fw-bold">Tanda Tangan Invoice (1)</label>
                    <input type="file" name="ttd_inv" class="form-control" accept="image/*">
                    @if($company->ttd_inv)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $company->ttd_inv) }}" 
                                alt="TTD INV" 
                                style="max-height: 80px; border:1px solid #ccc; padding:4px; border-radius:6px;">
                        </div>
                    @endif
                </div>
                <div class="col-md-4 mb-3">
                    <label for="ttd_inv2" class="form-label fw-bold">Tanda Tangan Invoice (2)</label>
                    <input type="file" name="ttd_inv2" class="form-control" accept="image/*">
                    @if($company->ttd_inv2)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $company->ttd_inv2) }}" 
                                alt="TTD INV2" 
                                style="max-height: 80px; border:1px solid #ccc; padding:4px; border-radius:6px;">
                        </div>
                    @endif
                </div>
            </div>

            {{-- Baris 6: Kontak --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $company->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $company->phone) }}">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Fax</label>
                    <input type="text" name="fax" class="form-control" value="{{ old('fax', $company->fax) }}">
                </div>
            </div>

            {{-- Baris 7: NPWP, Kota, dll --}}
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control" value="{{ old('website', $company->website) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">NPWP</label>
                    <input type="text" name="npwp" class="form-control" value="{{ old('npwp', $company->npwp) }}">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Kota</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $company->city) }}">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $company->postal_code) }}">
                </div>
            </div>

            {{-- Baris 8: Alamat --}}
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $company->address) }}</textarea>
                </div>
            </div>

            {{-- Baris 9: Bank --}}
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Bank</label>
                    <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $company->bank_name) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nomor Rekening</label>
                    <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number', $company->bank_account_number) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Atas Nama</label>
                    <input type="text" name="bank_account_name" class="form-control" value="{{ old('bank_account_name', $company->bank_account_name) }}">
                </div>
            </div>

            {{-- Baris 10: Status Aktif --}}
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ $company->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Aktif</label>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('companies.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection