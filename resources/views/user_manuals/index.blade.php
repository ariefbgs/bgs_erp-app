@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-user-manuals-index bgs-mode-index')
@section('title', 'User Manual Management')
@section('content')


<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
            <h4>
                <i class="bi bi-diagram-3-fill me-2"></i>
                System Documentation Hierarchy
            </h4>
            <a href="{{ route('user-manuals.create') }}" class="btn btn-light-custom btn-sm px-3 py-2">
                <i class="bi bi-plus-circle-fill me-1"></i>
                Add New Documentation
            </a>
        </div>
        
        <div class="card-body p-4">
            {{-- SUCCESS MESSAGE ALERT --}}
            @if(session('success'))
                <div id="success-alert" class="alert alert-success border-0 shadow-sm d-flex align-items-center fade show mb-4 bgs-u-8a1856d11134">
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close ms-auto bgs-u-aae30f4ce161" data-bs-dismiss="alert"></button>
                </div>

                <script>
                    setTimeout(() => {
                        let alert = document.getElementById('success-alert');
                        if (alert) {
                            let bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                            bsAlert.close();
                        }
                    }, 2500);
                </script>
            @endif

            {{-- SEARCH BAR (DISELARASKAN DENGAN STYLE QUOTATION) --}}
            <div class="search-filter-bar">
                <form method="GET" action="{{ route('user-manuals.index') }}" class="bgs-filter-form">
                    <div class="row g-3">
                        <div class="col-md-9">
                            <label class="filter-label">Search Keyword</label>
                            <input type="text"
                                   name="search"
                                   class="form-control py-2"
                                   placeholder="Type Module Code or Documentation Title..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary py-2 w-100 fw-bold bgs-u-40db451c03f9">
                                <i class="bi bi-search me-1"></i> Search
                            </button>
                            <a href="{{ route('user-manuals.index') }}" class="btn btn-outline-secondary py-2 px-3" title="Reset Filters">
                                <i class="bi bi-arrow-repeat"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            {{-- TABEL DATA BERSTRUKTUR HIERARKI --}}
            <div class="border rounded-3 overflow-hidden shadow-sm mb-3">
                <div class="table-responsive">
                    <table class="table table-modern align-middle m-0 bgs-index-table bgs-admin-index-table">
                        <thead>
                            <tr>
                                <th width="10%" class="text-center bgs-col-action col-action bgs-col-action">Actions</th>
<th width="20%">Module Code</th>
                                <th width="15%" class="text-center">Hierarchy Level</th>
                                <th width="45%">Documentation Title</th>
                                <th width="10%" class="text-center">Last Updated</th>

                            </tr>
                        </thead>
                        <tbody>
                            @forelse($manuals as $manual)
                                {{-- LEVEL 1: MODUL UTAMA --}}
                                <tr class="level-modul">
                                    <td class="text-center col-action bgs-col-action">
                                        <div class="d-flex justify-content-center action-btn-group">
                                            <a href="{{ route('user-manuals.edit', $manual->id) }}"
                                               class="btn btn-warning btn-sm text-dark" title="Edit Modul">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('user-manuals.destroy', $manual->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Menghapus modul utama akan menghapus semua sub-modul & fitur di bawahnya. Lanjutkan?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
<td>
                                        <div class="module-code">
                                            <i class="bi bi-collection-play-fill me-2"></i>{{ $manual->module_name }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary badge-custom">Modul</span>
                                    </td>
                                    <td>
                                        <div class="manual-title fw-bold text-dark">
                                            {{ $manual->title }}
                                        </div>
                                    </td>
                                    <td class="text-center text-muted small">
                                        {{ $manual->updated_at->format('d/m/Y H:i') }}
                                    </td>

                                </tr>

                                {{-- LEVEL 2: SUB MODUL --}}
                                @foreach($manual->children as $sub)
                                    <tr class="level-sub">
                                        <td class="text-center col-action bgs-col-action">
                                            <div class="d-flex justify-content-center action-btn-group">
                                                <a href="{{ route('user-manuals.edit', $sub->id) }}"
                                                   class="btn btn-warning btn-sm text-dark" title="Edit Sub-Modul">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('user-manuals.destroy', $sub->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus sub-modul ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
<td class="text-muted ps-4 small">
                                            <i class="bi bi-arrow-return-right me-2 text-warning"></i>
                                            <span class="text-uppercase font-monospace">{{ $sub->module_name }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark badge-custom">Sub-Modul</span>
                                        </td>
                                        <td class="ps-3 fw-semibold text-dark">
                                            {{ $sub->title }}
                                        </td>
                                        <td class="text-center text-muted small">
                                            {{ $sub->updated_at->format('d/m/Y H:i') }}
                                        </td>

                                    </tr>

                                    {{-- LEVEL 3: FITUR / SUB-SUB MODUL --}}
                                    @foreach($sub->children as $feature)
                                        <tr class="level-feature">
                                            <td class="text-center col-action bgs-col-action">
                                                <div class="d-flex justify-content-center action-btn-group">
                                                    <a href="{{ route('user-manuals.edit', $feature->id) }}"
                                                       class="btn btn-warning btn-sm text-dark" title="Edit Fitur">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('user-manuals.destroy', $feature->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus panduan fitur spesifik ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
<td class="text-muted ps-5 small">
                                                <i class="bi bi-arrow-return-right me-1 text-success"></i>
                                                <i class="bi bi-arrow-return-right me-2 text-success"></i>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success badge-custom">Fitur</span>
                                            </td>
                                            <td class="ps-5 text-muted style-italic">
                                                <i class="bi bi-lightning-charge-fill text-success me-1"></i>{{ $feature->title }}
                                            </td>
                                            <td class="text-center text-muted small">
                                                {{ $feature->updated_at->format('d/m/Y H:i') }}
                                            </td>

                                        </tr>
                                    @endforeach
                                @endforeach

                            @empty
                                <tr>
                                    <td colspan="5" class="text-center empty-state">
                                        <i class="bi bi-inbox-fill fs-2 d-block mb-2 text-muted"></i>
                                        There is no Documentation Hierarchy data available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection