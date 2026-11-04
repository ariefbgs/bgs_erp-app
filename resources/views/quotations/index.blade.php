

@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-quotations-index bgs-mode-index')
@section('title', 'Quotation Management')
@section('content')


<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
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

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4 bgs-u-f812d16479e9">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                </div>
            @endif

            {{-- SEARCH & FILTER BAR --}}
            <div class="search-filter-bar">
                <form method="GET" action="{{ route('quotations.index') }}" class="bgs-filter-form">
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
                            <button type="submit" class="btn btn-primary py-2 w-100 fw-bold bgs-u-40db451c03f9">
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
                <div class="table-responsive quotation-table-wrap">
                    <table class="table table-modern quotation-table align-middle m-0 bgs-index-table">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th class="text-center col-action bgs-col-action">Actions</th>
                                <th class="col-customer">Customer</th>
                                <th class="col-quotation-number">Quotation #</th>
                                <th class="text-center">Quotation Date</th>
                                <th class="text-center">Valid Until</th>
                                <th class="text-end">Quotation Total</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quotations as $index => $quotation)
                                <tr>
                                    {{-- NOMOR URUT --}}
                                    <td class="text-center text-muted fw-bold">
                                        {{ $quotations->firstItem() + $index }}
                                    </td>

{{-- ACTION BUTTONS --}}
                                    <td class="text-center col-action">
                                        <div class="d-flex justify-content-center action-btn-group">
                                            <a href="{{ route('quotations.show', $quotation->id) }}"
                                               class="btn btn-info btn-sm text-white bgs-u-7dfad3480fc0"
                                               title="View Detail">
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

                                    {{-- NAMA CUSTOMER --}}
                                    <td class="col-customer">
                                        <div class="customer-name">
                                            {{ $quotation->customer->name ?? '-' }}
                                        </div>
                                    </td>

                                    {{-- NOMOR QUOTATION --}}
                                    <td class="col-quotation-number">
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
                <div class="small fw-bold bgs-u-7e4cfe51c78c">
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
