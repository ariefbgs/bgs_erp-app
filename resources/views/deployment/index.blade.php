@extends('layouts.app')

@section('title', 'Deployment Manager')

@section('content')
@php
    $deploymentUser = Auth::user();
    $canUploadDeployment = $deploymentUser?->hasPermission('deployment_upload') ?? false;
    $canInstallDeployment = $deploymentUser?->hasPermission('deployment_install') ?? false;
@endphp
<style>
    body { background-color: #f1f5f9; }
    .deployment-card { border: 0; border-radius: 12px; overflow: hidden; }
    .deployment-header { background: linear-gradient(135deg, #0284c7, #075985); color: #fff; padding: 1.5rem; }
    .deployment-table thead th { background: #0c4a6e; color: #f0f9ff; border: 0; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
    .deployment-table td { vertical-align: middle; color: #334155; }
    .release-id { color: #0284c7; font-weight: 700; }
    .status-badge { border-radius: 999px; padding: .45rem .75rem; font-size: .72rem; font-weight: 700; text-transform: uppercase; }
    .deployment-action { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; }
</style>

<div class="py-4">
    <div id="deployment-feedback" class="alert d-none" role="alert"></div>

    <div class="card deployment-card shadow-sm">
        <div class="deployment-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h4 class="mb-1 fw-bold"><i class="fas fa-code-branch me-2"></i>Deployment Manager</h4>
                <div class="small opacity-75">Canonical application releases and lifecycle status</div>
            </div>
            <div class="d-flex gap-2">
                @auth
                    @if ($canUploadDeployment)
                        <button type="button" class="btn btn-light btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#uploadDeploymentModal">
                            <i class="fas fa-upload me-1"></i> Upload Package
                        </button>
                    @endif
                @endauth
                <a href="{{ route('deployment.history') }}" class="btn btn-light btn-sm fw-semibold">
                    <i class="fas fa-history me-1"></i> History
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table deployment-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="px-3 py-3">Release ID</th>
                            <th>Version</th>
                            <th>Scope</th>
                            <th>Module / Feature</th>
                            <th>Status</th>
                            <th>Uploaded</th>
                            <th>Installed</th>
                            @auth
                                @if ($canInstallDeployment)
                                    <th class="text-center">Action</th>
                                @endif
                            @endauth
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($releases as $release)
                            @php
                                $statusClass = match ($release->status) {
                                    'installed' => 'bg-success text-white',
                                    'failed' => 'bg-danger text-white',
                                    'validated' => 'bg-info text-dark',
                                    'rolled_back', 'superseded' => 'bg-secondary text-white',
                                    default => 'bg-warning text-dark',
                                };
                            @endphp
                            <tr>
                                <td class="px-3"><span class="release-id">{{ $release->release_id }}</span></td>
                                <td>{{ $release->version }}</td>
                                <td>{{ ucfirst($release->scope) }}</td>
                                <td>{{ $release->module ?: '-' }}{{ $release->feature ? ' / '.$release->feature : '' }}</td>
                                <td><span class="status-badge {{ $statusClass }}">{{ str_replace('_', ' ', $release->status) }}</span></td>
                                <td>{{ $release->created_at ? \Illuminate\Support\Carbon::parse($release->created_at)->format('d M Y H:i') : '-' }}</td>
                                <td>{{ $release->installed_at ? \Illuminate\Support\Carbon::parse($release->installed_at)->format('d M Y H:i') : '-' }}</td>
                                @auth
                                    @if ($canInstallDeployment)
                                        <td class="text-center">
                                            @if ($release->status === 'uploaded')
                                                <button
                                                    type="button"
                                                    class="btn btn-success btn-sm deployment-action install-release"
                                                    data-release-id="{{ $release->id }}"
                                                    data-release-label="{{ $release->release_id }}"
                                                    title="Install release"
                                                ><i class="fas fa-play"></i></button>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endif
                                @endauth
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canInstallDeployment ? 8 : 7 }}" class="text-center text-muted py-5">No deployment release has been registered.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($releases->hasPages())
        <div class="mt-3">{{ $releases->links() }}</div>
    @endif
</div>

@auth
    @if ($canUploadDeployment)
        <div class="modal fade" id="uploadDeploymentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Deployment Package</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="deployment-upload-form" action="{{ route('deployment.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <label for="deployment-package" class="form-label fw-semibold">ZIP Package</label>
                            <input id="deployment-package" class="form-control" type="file" name="package" accept=".zip,application/zip" required>
                            <div class="form-text">Maximum 100 MB. The manifest and payload will be validated before registration.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i> Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endauth
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const feedback = document.getElementById('deployment-feedback');
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function showError(message) {
        feedback.textContent = message;
        feedback.className = 'alert alert-danger';
        feedback.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    const uploadForm = document.getElementById('deployment-upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const submit = uploadForm.querySelector('[type="submit"]');
            submit.disabled = true;

            try {
                const response = await fetch(uploadForm.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: new FormData(uploadForm)
                });
                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    throw new Error(data.message || 'Deployment package upload failed.');
                }
                window.location.reload();
            } catch (error) {
                showError(error.message);
                submit.disabled = false;
            }
        });
    }

    document.querySelectorAll('.install-release').forEach(function (button) {
        button.addEventListener('click', async function () {
            const label = button.dataset.releaseLabel;
            if (!window.confirm('Install release ' + label + '? This operation may change application files and database schema.')) {
                return;
            }

            button.disabled = true;
            try {
                const response = await fetch(@json(route('deployment.install')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ application_release_id: Number(button.dataset.releaseId) })
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.message || 'Deployment installation failed.');
                }
                window.location.reload();
            } catch (error) {
                showError(error.message);
                button.disabled = false;
            }
        });
    });
});
</script>
@endpush
