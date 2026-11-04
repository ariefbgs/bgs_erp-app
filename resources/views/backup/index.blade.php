@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-backup-index bgs-mode-index')

@section('title', 'Backup & Restore Database')

@section('content')
<div class="card">
    <div class="card-header bg-primary text-white bgs-page-header">
        <h4>Backup & Restore Database</h4>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bgs-section-header">Buat Backup Baru</div>
                    <div class="card-body">
                        <p>Backup database akan disimpan di folder <code>storage/app/backups</code>.</p>
                        <form action="{{ route('backup.create') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-database-down"></i> Backup Sekarang
                            </button>
                        </form>
                        @if(session('backup_file'))
                            <div class="mt-2">
                                <strong>File terbaru:</strong> {{ session('backup_file') }}
                                <a href="{{ route('backup.download', session('backup_file')) }}" class="btn btn-sm btn-info">Download</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bgs-section-header">Restore Database</div>
                    <div class="card-body">
                        <p>Upload file SQL backup untuk mengembalikan database ke kondisi sebelumnya.</p>
                        <a href="{{ route('backup.restore.form') }}" class="btn btn-warning">
                            <i class="bi bi-database-up"></i> Restore
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <hr>
        <h5>Daftar File Backup</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle bgs-index-table bgs-operations-table">
                <thead>
                    <tr>
                        <th class="col-action bgs-col-action">Actions</th>
                        <th>Nama File</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backupFiles as $file)
                    <tr>
                        <td class="col-action bgs-col-action">
                            <a href="{{ route('backup.download', $file['name']) }}" class="btn btn-sm btn-info">Download</a>
                        </td>
                        <td>{{ $file['name'] }}</td>
                        <td>{{ \Carbon\Carbon::createFromTimestamp($file['last_modified'])->format('d/m/Y H:i:s') }}</td>

                    </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">Belum ada file backup.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection