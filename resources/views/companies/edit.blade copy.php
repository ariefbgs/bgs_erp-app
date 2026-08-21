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

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Kode Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" name="company_code" class="form-control @error('company_code') is-invalid @enderror" value="{{ old('company_code', $company->company_code) }}" required>
                    @error('company_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $company->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="logo" class="form-label fw-bold">Logo Perusahaan</label>
                        <input type="file" name="logo" id="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                        <div class="form-text">Format: JPEG, PNG, JPG, GIF. Max: 2MB. Kosongkan jika tidak ingin mengubah.</div>
                        @error('logo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        {{-- TAMPILKAN LOGO SAAT INI --}}
                        @if($company->logo)
                            <div class="mt-2">
                                <div class="mb-2" style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px; background: #fff; display: inline-block;">
                                    <img src="{{ route('companies.file.view', ['id' => $company->id, 'field' => 'logo']) }}" 
                                        alt="Current Logo" 
                                        style="max-height: 80px; width: auto; object-fit: contain; display: block;">
                                </div>
                                <a href="{{ route('companies.file.view', ['id' => $company->id, 'field' => 'logo']) }}" target="_blank" class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-eye-fill"></i> Lihat Logo Ukuran Asli
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-6 mb-4">
                        <label for="ttd_do" class="form-label fw-bold">Tanda Tangan DO</label>
                        <input type="file" name="ttd_do" id="ttd_do" class="form-control @error('ttd_do') is-invalid @enderror" accept="image/*">
                        <div class="form-text">Format: JPEG, PNG, JPG, GIF. Max: 2MB. Kosongkan jika tidak ingin mengubah.</div>
                        @error('ttd_do')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        {{-- TAMPILKAN TANDA TANGAN DO SAAT INI --}}
                        @if($company->ttd_do)
                            <div class="mt-2">
                                <div class="mb-2" style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px; background: #fff; display: inline-block;">
                                    <img src="{{ route('companies.file.view', ['id' => $company->id, 'field' => 'ttd_do']) }}" 
                                        alt="Current TTD DO" 
                                        style="max-height: 80px; width: auto; object-fit: contain; display: block;">
                                </div>
                                <a href="{{ route('companies.file.view', ['id' => $company->id, 'field' => 'ttd_do']) }}" target="_blank" class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-eye-fill"></i> Lihat TTD DO Asli
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="ttd_inv" class="form-label fw-bold">Tanda Tangan Invoice (1)</label>
                        <input type="file" name="ttd_inv" id="ttd_inv" class="form-control @error('ttd_inv') is-invalid @enderror" accept="image/*">
                        <div class="form-text">Format: JPEG, PNG, JPG, GIF. Max: 2MB. Kosongkan jika tidak ingin mengubah.</div>
                        @error('ttd_inv')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        {{-- TAMPILKAN TANDA TANGAN INVOICE 1 SAAT INI --}}
                        @if($company->ttd_inv)
                            <div class="mt-2">
                                <div class="mb-2" style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px; background: #fff; display: inline-block;">
                                    <img src="{{ route('companies.file.view', ['id' => $company->id, 'field' => 'ttd_inv']) }}" 
                                        alt="Current TTD INV" 
                                        style="max-height: 80px; width: auto; object-fit: contain; display: block;">
                                </div>
                                <a href="{{ route('companies.file.view', ['id' => $company->id, 'field' => 'ttd_inv']) }}" target="_blank" class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-eye-fill"></i> Lihat TTD Invoice (1) Asli
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-6 mb-4">
                        <label for="ttd_inv2" class="form-label fw-bold">Tanda Tangan Invoice (2)</label>
                        <input type="file" name="ttd_inv2" id="ttd_inv2" class="form-control @error('ttd_inv2') is-invalid @enderror" accept="image/*">
                        <div class="form-text">Format: JPEG, PNG, JPG, GIF. Max: 2MB. Kosongkan jika tidak ingin mengubah.</div>
                        @error('ttd_inv2')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        {{-- TAMPILKAN TANDA TANGAN INVOICE 2 SAAT INI --}}
                        @if($company->ttd_inv2)
                            <div class="mt-2">
                                <div class="mb-2" style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px; background: #fff; display: inline-block;">
                                    <img src="{{ route('companies.file.view', ['id' => $company->id, 'field' => 'ttd_inv2']) }}" 
                                        alt="Current TTD INV2" 
                                        style="max-height: 80px; width: auto; object-fit: contain; display: block;">
                                </div>
                                <a href="{{ route('companies.file.view', ['id' => $company->id, 'field' => 'ttd_inv2']) }}" target="_blank" class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-eye-fill"></i> Lihat TTD Invoice (2) Asli
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $company->email) }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $company->phone) }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Fax</label>
                    <input type="text" name="fax" class="form-control" value="{{ old('fax', $company->fax) }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control" value="{{ old('website', $company->website) }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">NPWP</label>
                    <input type="text" name="npwp" class="form-control" value="{{ old('npwp', $company->npwp) }}">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Kota</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $company->city) }}">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $company->postal_code) }}">
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $company->address) }}</textarea>
                </div>

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