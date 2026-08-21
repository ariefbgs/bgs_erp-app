@extends('layouts.app')
@section('title', 'Document Receipt Management')
@section('content')
<style>
    .table th,
    .table td {
        vertical-align: middle;
    }

    .text-right {
        text-align: right;
    }

    .total-column {
        text-align: right;
        font-weight: 600;
        white-space: nowrap;
    }

    .search-filter-bar {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 18px;
        margin-bottom: 20px;
    }

    .table thead th {
        white-space: nowrap;
        font-size: 13px;
    }

    .badge {
        font-size: 11px;
        padding: 6px 10px;
    }

    .action-btn .btn {
        margin: 2px;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    .card-header h4 {
        margin-bottom: 0;
    }

    .customer-name {
        font-weight: 600;
    }

    .empty-state {
        padding: 40px 20px;
        color: #6c757d;
    }

    .filter-label {
        font-weight: 600;
        margin-bottom: 5px;
    }
</style>

<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4>
            <i class="bi bi-file-earmark-text"></i>
            Document Receipt Management
        </h4>
        <a href="{{ route('document-receipts.create') }}" class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle"></i>
            Add New Document Receipt
        </a>
    </div>

    <div class="card-body">
        {{-- SUCCESS MESSAGE --}}
        @if(session('success'))
            <div id="success-alert" class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

        {{-- SEARCH & FILTER --}}
        <div class="search-filter-bar">
            <form method="GET" action="{{ route('document-receipts.index') }}">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="filter-label">Search</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Receipt # / Customer Name"
                               value="{{ request('search') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="filter-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2 w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('document-receipts.index') }}" class="btn btn-secondary" title="Reset Filters">
                            <i class="bi bi-arrow-repeat"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="20%" class="text-center">Customer</th>
                        <th width="15%" class="text-center">Receipt #</th>
                        <th width="12%" class="text-center">Receipt Date</th>
                        <th width="10%" class="text-center">Status</th>
                        <th width="10%" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $index => $receipt)
                        <tr>
                            <td class="text-center">{{ $receipts->firstItem() + $index }}</td>
                            <td>{{ $receipt->deliveryOrder->poCustomer->customer->name ?? '-' }}</td>
                            <td>{{ $receipt->receipt_number }}</td>
                            <td class="text-center">
                                {{ $receipt->receipt_date ? \Carbon\Carbon::parse($receipt->receipt_date)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="text-center">
                                @if($receipt->status == 'draft')
                                    <span class="badge bg-secondary">Draft</span>
                                @elseif($receipt->status == 'sent')
                                    <span class="badge bg-primary">Sent</span>
                                @else
                                    <span class="badge bg-success">Delivered</span>
                                @endif
                            </td>
                            <td class="text-center action-btn">
                                <a href="{{ route('document-receipts.show', $receipt->id) }}" class="btn btn-info btn-sm" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('document-receipts.edit', $receipt->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('document-receipts.destroy', $receipt->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this receipt?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center empty-state">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No receipt data available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- PAGINATION --}}
            <div class="mt-3 d-flex justify-content-end">
                {{ $receipts->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection