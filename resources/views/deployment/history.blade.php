@extends('layouts.app')

@section('title', 'Deployment History')

@section('content')
<div class="py-4">
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap text-white p-4" style="background: linear-gradient(135deg, #0284c7, #075985);">
            <div>
                <h4 class="mb-1 fw-bold"><i class="fas fa-history me-2"></i>Deployment History</h4>
                <div class="small opacity-75">Immutable execution audit trail</div>
            </div>
            <a href="{{ route('deployment.index') }}" class="btn btn-light btn-sm fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Releases
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead style="background: #0c4a6e; color: #f0f9ff;">
                    <tr>
                        <th class="px-3 py-3">Release ID</th>
                        <th>Version</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $entry)
                        <tr>
                            <td class="px-3 fw-bold text-primary">{{ $entry->release_id }}</td>
                            <td>{{ $entry->version }}</td>
                            <td>{{ ucfirst($entry->action) }}</td>
                            <td><span class="badge {{ $entry->status === 'success' ? 'bg-success' : ($entry->status === 'failed' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ ucfirst(str_replace('_', ' ', $entry->status)) }}</span></td>
                            <td>{{ $entry->started_at ? \Illuminate\Support\Carbon::parse($entry->started_at)->format('d M Y H:i:s') : '-' }}</td>
                            <td>{{ $entry->completed_at ? \Illuminate\Support\Carbon::parse($entry->completed_at)->format('d M Y H:i:s') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No deployment execution history is available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($history->hasPages())
        <div class="mt-3">{{ $history->links() }}</div>
    @endif
</div>
@endsection
