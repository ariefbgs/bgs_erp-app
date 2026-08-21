@extends('layouts.app')
@section('title', 'Quotation Management')
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
    .table-modern tbody tr:nth-child(even) {
        background-color: #fcfdfe;
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
    .customer-name {
        font-weight: 600;
        color: #1e293b; /* Kontras tinggi */
    }
    .quotation-number {
        font-weight: 600;
        color: #0284c7; /* Warna Ocean Blue penanda link/entitas utama */
    }
    .total-column {
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
    }

    /* Badge Status Custom (Pills Bulat & Berwarna Tegas) */
    .badge-custom {
        padding: 0.5em 0.9em;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
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
                <i class="bi bi-file-earmark-text-fill me-2"></i>
                Quotation Management
            </h4>
            <a href="{{ route('quotations.create') }}" class="btn btn-light-custom btn-sm px-3 py-2">
                <i class="bi bi-plus-circle-fill me-1"></i>
                Add New Quotation
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

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4" style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                </div>
            @endif  

            {{-- SEARCH & FILTER BAR --}}
            <div class="search-filter-bar">
                <form method="GET" action="{{ route('quotations.index') }}">
                    <div class="row g-3">
                        {{-- INPUT PENCARIAN --}}
                        <div class="col-md-5">
                            <label class="filter-label">Search Keyword</label>
                            <input type="text"
                                   name="search"
                                   class="form-control py-2"
                                   placeholder="Type Quotation # or Customer Name..."
                                   value="{{ request('search') }}">
                        </div>
                        
                        {{-- STATUS FILTER --}}
                        <div class="col-md-4">
                            <label class="filter-label">Filter Status</label>
                            <select name="status" class="form-select py-2">
                                <option value="">All Status / Semua Status</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rec_po" {{ request('status') == 'rec_po' ? 'selected' : '' }}>PO Received</option>
                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                        </div>

                        {{-- TOMBOL SUBMIT --}}
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary py-2 w-100 fw-bold" style="background-color: #0284c7; border-color: #0284c7;">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                            <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary py-2 px-3" title="Reset Filters">
                                <i class="bi bi-arrow-repeat"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            {{-- TABEL DATA --}}
            <div class="border rounded-3 overflow-hidden shadow-sm mb-3">
                <div class="table-responsive">
                    <table class="table table-modern align-middle m-0">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="22%">Customer</th>
                                <th width="15%">Quotation #</th>
                                <th width="12%" class="text-center">Quotation Date</th>
                                <th width="12%" class="text-center">Valid Until</th>
                                <th width="14%" class="text-end">Quotation Total</th>
                                <th width="10%" class="text-center">Status</th>
                                <th width="10%" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quotations as $index => $quotation)
                                <tr>
                                    {{-- NOMOR URUT --}}
                                    <td class="text-center text-muted fw-bold">
                                        {{ $quotations->firstItem() + $index }}
                                    </td>
                                    
                                    {{-- NAMA CUSTOMER --}}
                                    <td>
                                        <div class="customer-name">
                                            {{ $quotation->customer->name ?? '-' }}
                                        </div>
                                    </td>
                                    
                                    {{-- NOMOR QUOTATION --}}
                                    <td>
                                        <div class="quotation-number">
                                            {{ $quotation->quotation_number }}
                                        </div>
                                    </td>
                                    
                                    {{-- TANGGAL QUOTATION --}}
                                    <td class="text-center">
                                        {{ $quotation->date ? date('d/m/Y', strtotime($quotation->date)) : '-' }}
                                    </td>
                                    
                                    {{-- TANGGAL EXPIRED --}}
                                    <td class="text-center">
                                        {{ $quotation->valid_until ? date('d/m/Y', strtotime($quotation->valid_until)) : '-' }}
                                    </td>
                                    
                                    {{-- TOTAL FINANSIAL --}}
                                    <td class="text-end total-column">
                                        Rp {{ number_format($quotation->total ?? 0, 0, ',', '.') }}
                                    </td>
                                    
                                    {{-- STATUS BADGE MATURITY --}}
                                    <td class="text-center">
                                        @if($quotation->status == 'draft')
                                            <span class="badge bg-secondary badge-custom">Draft</span>
                                        @elseif($quotation->status == 'sent')
                                            <span class="badge bg-primary badge-custom">Sent</span>
                                        @elseif($quotation->status == 'approved')
                                            <span class="badge bg-success badge-custom">Approved</span>
                                        @elseif($quotation->status == 'rec_po')
                                            <span class="badge bg-info text-dark badge-custom">Received PO</span>
                                        @elseif($quotation->status == 'expired')
                                            <span class="badge bg-danger badge-custom">Expired</span>
                                        @endif
                                    </td>

                                    {{-- ACTION BUTTONS --}}
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center action-btn-group">
                                            <a href="{{ route('quotations.show', $quotation->id) }}"
                                               class="btn btn-info btn-sm text-white"
                                               title="View Detail" style="background-color: #0ea5e9; border-color: #0ea5e9;">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('quotations.edit', $quotation->id) }}"
                                               class="btn btn-warning btn-sm text-dark"
                                               title="Edit Data">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('quotations.destroy', $quotation->id) }}"
                                                  method="POST"
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-danger btn-sm"
                                                        title="Delete"
                                                        onclick="return confirm('Are you sure you want to delete this Quotation?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center empty-state">
                                        <i class="bi bi-inbox-fill fs-2 d-block mb-2 text-muted"></i>
                                        There is no Quotation data available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            {{-- PAGINATION BANNER --}}
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2">
                <div class="small fw-bold" style="color: #475569;">
                    Showing {{ $quotations->firstItem() ?? 0 }} to {{ $quotations->lastItem() ?? 0 }} of {{ $quotations->total() }} entries
                </div>
                <div>
                    {{ $quotations->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection