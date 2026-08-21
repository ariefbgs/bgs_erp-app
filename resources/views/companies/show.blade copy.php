@extends('layouts.app')

@section('title', 'Detail Perusahaan')

@section('content')
<div class="card">
    <div class="card-header bg-info text-white">
        <h4>Detail Perusahaan</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th width="35%">Kode Perusahaan</th>
                        <td>{{ $company->company_code }}</td>
                    </tr>
                    <tr>
                        <th>Nama Perusahaan</th>
                        <td>{{ $company->name }}</td>
                    </tr>
                    @if($company->logo)
                    <tr>
                        <th>Logo</th>
                        <td>
                            <img src="{{ route('companies.file.view', ['id' => $company->id, 'field' => 'logo']) }}" alt="Logo" class="img-thumbnail" 
                                    style="width: 60px; height: 60px; object-fit: cover;">
                        </td>
                    </tr>
                    @endif
                    
                    {{-- PIC DO --}}
                    @if($company->pic_do)
                    <tr>
                        <th>Penanggung Jawab DO</th>
                        <td>{{ $company->pic_do }}</td>
                    </tr>
                    @endif
                    
                    @if($company->ttd_do)
                    <tr>
                        <th>Tanda Tangan DO</th>
                        <td><img src="{{ route('companies.file.view', ['id' => $company->id, 'field' => 'ttd_do']) }}" style="max-width: 150px;"></td>
                    </tr>
                    @endif
                    
                    {{-- PIC INV --}}
                    @if($company->pic_inv)
                    <tr>
                        <th>Penanggung Jawab Invoice 1</th>
                        <td>{{ $company->pic_inv }}</td>
                    </tr>
                    @endif
                    
                    @if($company->ttd_inv)
                    <tr>
                        <th>Tanda Tangan Invoice 1</th>
                        <td><img src="{{ route('companies.file.view', ['id' => $company->id, 'field' => 'ttd_inv']) }}" style="max-width: 150px;"></td>
                    </tr>
                    @endif
                    
                    {{-- PIC INV2 --}}
                    @if($company->pic_inv2)
                    <tr>
                        <th>Penanggung Jawab Invoice 2</th>
                        <td>{{ $company->pic_inv2 }}</td>
                    </tr>
                    @endif
                    
                    @if($company->ttd_inv2)
                    <tr>
                        <th>Tanda Tangan Invoice 2</th>
                        <td><img src="{{ route('companies.file.view', ['id' => $company->id, 'field' => 'ttd_inv2']) }}" style="max-width: 150px;"></td>
                    </tr>
                    @endif
                    
                    <tr>
                        <th>Alamat</th>
                        <td>{{ $company->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Kota</th>
                        <td>{{ $company->city ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Kode Pos</th>
                        <td>{{ $company->postal_code ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Telepon</th>
                        <td>{{ $company->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Fax</th>
                        <td>{{ $company->fax ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $company->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Website</th>
                        <td>{{ $company->website ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>NPWP</th>
                        <td>{{ $company->npwp ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Bank</th>
                        <td>{{ $company->bank_name ?? '-' }} - {{ $company->bank_account_number ?? '' }} a.n {{ $company->bank_account_name ?? '' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($company->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Tidak Aktif</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Dibuat</th>
                        <td>{{ $company->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Diupdate</th>
                        <td>{{ $company->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Kembali</a>
            <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-warning">Edit</a>
        </div>
    </div>
</div>
@endsection