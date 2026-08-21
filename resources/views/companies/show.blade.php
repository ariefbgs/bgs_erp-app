@extends('layouts.app')

@section('title', 'Detail Perusahaan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Detail Perusahaan: {{ $company->name }}</h3>
    <div>
        <a href="{{ route('companies.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
        <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-warning text-white"><i class="fas fa-edit"></i> Edit Data</a>
    </div>
</div>

<div class="row">
    {{-- Kolom Kiri: Informasi Utama & Kontak --}}
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white fw-bold">Informasi Utama</div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tr><th width="35%">Kode Perusahaan</th><td>{{ $company->company_code }}</td></tr>
                    <tr><th>Nama</th><td>{{ $company->name }}</td></tr>
                    <tr><th>Status</th><td>
                        <span class="badge {{ $company->is_active ? 'bg-success' : 'bg-danger' }}">
                            {{ $company->is_active ? 'Aktif' : 'Non-Aktif' }}
                        </span>
                    </td></tr>
                    <tr><th>Email</th><td>{{ $company->email ?? '-' }}</td></tr>
                    <tr><th>Telepon</th><td>{{ $company->phone ?? '-' }}</td></tr>
                    <tr><th>Fax</th><td>{{ $company->fax ?? '-' }}</td></tr>
                    <tr><th>Website</th><td>{{ $company->website ?? '-' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white fw-bold">Alamat & Keuangan</div>
            <div class="card-body">
                <p class="mb-2"><strong>Alamat:</strong><br>{{ $company->address ?? '-' }}</p>
                <p class="mb-2"><strong>Kota / Pos:</strong> {{ $company->city ?? '-' }} / {{ $company->postal_code ?? '-' }}</p>
                <hr>
                <table class="table table-borderless table-sm mb-0">
                    <tr><th width="30%">NPWP</th><td>{{ $company->npwp ?? '-' }}</td></tr>
                    <tr><th>Bank</th><td>{{ $company->bank_name ?? '-' }}</td></tr>
                    <tr><th>No. Rek</th><td>{{ $company->bank_account_number ?? '-' }}</td></tr>
                    <tr><th>A/N</th><td>{{ $company->bank_account_name ?? '-' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Kolom Kanan: Logo & TTD --}}
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white fw-bold">Logo & Tanda Tangan</div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-12 mb-4">
                        <label class="d-block text-muted small">LOGO PERUSAHAAN</label>
                        @if($company->logo)
                            <img src="{{ asset($company->logo) }}" class="img-thumbnail" style="width: 60px;">
                        @else <p>-</p> @endif
                    </div>
                    <div class="col-6 mb-4">
                        <label class="d-block text-muted small">TTD DO ({{ $company->pic_do ?? 'PIC' }})</label>
                        @if($company->ttd_do)
                            <img src="{{ asset($company->ttd_do) }}" class="img-thumbnail" style="width: 60px;">
                        @else <p>-</p> @endif
                    </div>
                    <div class="col-6 mb-4">
                        <label class="d-block text-muted small">TTD INV 1 ({{ $company->pic_inv ?? 'PIC' }})</label>
                        @if($company->ttd_inv)
                            <img src="{{ asset($company->ttd_inv) }}" class="img-thumbnail" style="width: 60px;">
                        @else <p>-</p> @endif
                    </div>
                    <div class="col-12">
                        <label class="d-block text-muted small">TTD INV 2</label>
                        @if($company->ttd_inv2)
                            <img src="{{ asset($company->ttd_inv2) }}" class="img-thumbnail" style="width: 60px;">
                        @else <p>-</p> @endif
                    </div>
                </div>
            </div>
            <div class="card-footer text-muted small text-center">
                Dibuat: {{ $company->created_at->format('d/m/Y H:i') }} | Terakhir Update: {{ $company->updated_at->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</div>
@endsection