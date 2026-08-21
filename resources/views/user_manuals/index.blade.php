@extends('layouts.app')
@section('title', 'User Manual Management')
@section('content')
<style>
    /* Mengubah Background dasar halaman agar senada dengan halaman show */
    body {
        background-color: #f1f5f9;
    }

    /* Desain Card Utama */
    .main-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        background: #fff;
        overflow: hidden;
    }
    
    /* Header dengan gradasi warna Ocean Blue */
    .card-header-custom {
        background: linear-gradient(135deg, #0284c7, #075985);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    .card-header-custom h4 {
        color: white;
        font-weight: 700;
        margin-bottom: 0;
    }

    /* Search & Filter Bar Custom */
    .search-filter-bar {
        background-color: #f8fafc; /* Abu-abu dasar super terang */
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 25px;
    }
    .filter-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #475569; /* Grey lebih tua tapi terang */
        font-weight: 700;
        margin-bottom: 6px;
    }

    /* Styling Tabel Modern */
    .table-modern thead th {
        background-color: #0c4a6e; /* Biru Gelap Profesional */
        color: #f0f9ff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.78rem;
        letter-spacing: 0.5px;
        padding: 14px 10px;
        border: none;
        white-space: nowrap;
    }
    .table-modern tbody tr {
        transition: background-color 0.2s ease;
    }
    .table-modern tbody tr.level-modul {
        background-color: #f8fafc;
        border-left: 4px solid #0284c7;
    }
    .table-modern tbody tr.level-sub {
        border-left: 4px solid #f59e0b;
    }
    .table-modern tbody tr.level-feature {
        border-left: 4px solid #10b981;
        background-color: #f9fafb;
    }
    .table-modern tbody tr:hover {
        background-color: #f1f5f9 !important; /* Highlight halus saat di-hover */
    }
    .table-modern tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        font-size: 0.9rem;
        color: #334155;
        border-color: #f1f5f9;
    }
    
    /* Teks Komponen di Dalam Tabel */
    .module-code {
        font-weight: 700;
        color: #0284c7;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .manual-title {
        font-weight: 600;
        color: #1e293b; /* Kontras tinggi */
    }

    /* Badge Status Custom (Pills Bulat & Berwarna Tegas) */
    .badge-custom {
        padding: 0.5em 0.9em;
        font-size: 0.72rem;
        font-weight: 700;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: block;
        width: 100%;
        text-center;
    }

    /* Action Buttons Penyelarasan */
    .action-btn-group .btn {
        padding: 5px 10px;
        font-size: 0.85rem;
        border-radius: 6px;
        margin: 0 2px;
    }
    
    /* Empty State */
    .empty-state {
        padding: 50px 20px;
        color: #64748b;
        font-weight: 500;
    }

    /* Tombol Header & Custom Input */
    .btn-light-custom {
        background-color: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        font-weight: 600;
    }
    .btn-light-custom:hover {
        background-color: rgba(255, 255, 255, 0.3);
        color: white;
    }
    .form-control, .form-select {
        border-color: #cbd5e1;
    }
    .form-control:focus, .form-select:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
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
                <div id="success-alert" class="alert alert-success border-0 shadow-sm d-flex align-items-center fade show mb-4" style="background-color: #f0fdf4; color: #15803d; border-left: 4px solid #16a34a !important; border-radius: 6px;">
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size: 0.8rem;"></button>
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
                <form method="GET" action="{{ route('user-manuals.index') }}">
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
                            <button type="submit" class="btn btn-primary py-2 w-100 fw-bold" style="background-color: #0284c7; border-color: #0284c7;">
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
                    <table class="table table-modern align-middle m-0">
                        <thead>
                            <tr>
                                <th width="20%">Module Code</th>
                                <th width="15%" class="text-center">Hierarchy Level</th>
                                <th width="45%">Documentation Title</th>
                                <th width="10%" class="text-center">Last Updated</th>
                                <th width="10%" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($manuals as $manual)
                                {{-- LEVEL 1: MODUL UTAMA --}}
                                <tr class="level-modul">
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
                                    <td class="text-center">
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
                                </tr>

                                {{-- LEVEL 2: SUB MODUL --}}
                                @foreach($manual->children as $sub)
                                    <tr class="level-sub">
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
                                        <td class="text-center">
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
                                    </tr>

                                    {{-- LEVEL 3: FITUR / SUB-SUB MODUL --}}
                                    @foreach($sub->children as $feature)
                                        <tr class="level-feature">
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
                                            <td class="text-center">
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