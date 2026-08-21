@extends('layouts.app')

@section('title', 'Restore Database')

@section('content')
<div class="card">
    <div class="card-header bg-warning text-white">
        <h4>Restore Database</h4>
    </div>
    <div class="card-body">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> <strong>Peringatan:</strong> Restore akan mengganti semua data saat ini dengan data dari file backup. Pastikan Anda sudah melakukan backup terbaru. Proses ini tidak dapat dibatalkan.
        </div>

        <form action="{{ route('backup.restore') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label">Pilih File SQL</label>
                <input type="file" name="backup_file" class="form-control" accept=".sql, .txt" required>
                <small class="text-muted">Maksimal 100MB.</small>
            </div>
            <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin akan merestore database? Semua data saat ini akan diganti!')">
                <i class="bi bi-database-up"></i> Restore Sekarang
            </button>
            <a href="{{ route('backup.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>
@endsection