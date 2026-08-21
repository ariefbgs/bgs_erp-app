@extends('layouts.app')
@section('title', 'Supplier PO Management')
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
    .po-number {
        font-weight: 600;
        color: #0d6efd;
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
    .summary-card {
        border-radius: 10px;
        border: 1px solid #e9ecef;
        padding: 12px;
        background: #fff;
        margin-bottom: 15px;
    }
</style>

<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4>
            <i class="bi bi-file-earmark-text"></i>
            Supplier PO Management
        </h4>
        <a href="{{ route('po-suppliers.create') }}"
           class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle"></i>
            Add New Supplier PO
        </a>
    </div>
    <div class="card-body">
        {{-- SUCCESS MESSAGE --}}
        @if(session('success'))
            <div id="success-alert"
                 class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i>
                {{ session('success') }}
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                </button>
            </div>

            <script>
                setTimeout(() =>
                {
                    let alert =
                        document.getElementById('success-alert');
                    if (alert)
                    {
                        let bsAlert =
                            bootstrap.Alert.getOrCreateInstance(alert);
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

        {{-- SEARCH & FILTER --}}
        <div class="search-filter-bar">
            <form method="GET"
                  action="{{ route('po-suppliers.index') }}">
                <div class="row g-3">
                    {{-- SEARCH --}}
                    <div class="col-md-5">
                        <label class="filter-label">
                            Search
                        </label>

                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="PO # / Supplier Name"
                               value="{{ request('search') }}">
                    </div>
                    {{-- PO STATUS --}}
                    <div class="col-md-3">
                        <label class="filter-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    {{-- RECEIVE STATUS --}}
                    <div class="col-md-2">
                        <label class="filter-label">Receive Status</label>
                        <select name="receive_status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" {{ request('receive_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="partial" {{ request('receive_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="completed" {{ request('receive_status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>

                    {{-- BUTTON --}}
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit"
                                class="btn btn-primary me-2 w-100">
                            <i class="bi bi-search"></i>
                            Search
                        </button>

                        <a href="{{ route('po-suppliers.index') }}"
                           class="btn btn-secondary" title="Reset Filters">
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
                        <th width="18%" class="text-center">
                            Supplier
                        </th>
                        <th width="12%" class="text-center">
                            PO #
                        </th>
                        <th width="10%" class="text-center">
                            PO Date
                        </th>
                        <th width="10%" class="text-center">
                            PO Customer #
                        </th>
                        <th width="10%" class="text-center">
                            PO Total
                        </th>
                        <th width="8%" class="text-center">
                            PO Status
                        </th>
                        <th width="8%" class="text-center">
                            Receipt Status
                        </th>
                        <th width="9%" class="text-center">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($poSuppliers as $index => $po)
                        <tr>
                            {{-- NO --}}
                            <td class="text-center">
                                {{ $poSuppliers->firstItem() + $index }}
                            </td>
                            {{-- SUPPLIER --}}
                            <td>
                                <div class="supplier-name">
                                    {{ $po->supplier->name ?? '-' }}
                                </div>
                            </td>
                            {{-- PO NUMBER --}}
                            <td>
                                <div class="po-number">
                                    {{ $po->po_supplier_number }}
                                </div>
                            </td>
                            {{-- PO DATE --}}
                            <td class="text-center">
                                {{ $po->po_date
                                    ? date('d/m/Y', strtotime($po->po_date))
                                    : '-' }}
                            </td>
                            {{-- PO CUSTOMER NUMBER --}}
                            <td>
                                {{ $po->poCustomer->po_number ?? '-' }}
                            </td>
                            {{-- TOTAL --}}
                            <td class="total-column">
                                Rp {{ number_format($po->total ?? 0, 0, ',', '.') }}
                            </td>
                            {{-- PO STATUS --}}
                            <td class="text-center">
                                @if($po->status == 'draft')
                                    <span class="badge bg-primary">Draft</span>
                                @elseif($po->status == 'sent')
                                    <span class="badge bg-warning text-dark">Sent</span>
                                @elseif($po->status == 'confirmed')
                                    <span class="badge bg-success">Confirmed</span>
                                @elseif($po->status == 'received')
                                    <span class="badge bg-success">Received</span>
                                @else($po->status == 'cancelled')
                                    <span class="badge bg-danger">Cancelled</span>
                                @endif
                            </td>
                            {{-- RECEIPT STATUS --}}
                            <td class="text-center">
                                @if($po->receipt_status == 'pending')
                                    <span class="badge bg-secondary">Pending</span>
                                @elseif($po->receipt_status == 'partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @elseif($po->receipt_status == 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @else($po->receipt_status == 'cancelled')
                                    <span class="badge bg-danger">Cancelled</span>
                                @endif
                            </td>
                            
                            {{-- ACTION --}}
                            <td class="text-center action-btn">
                                <a href="{{ route('po-suppliers.show', $po->id) }}"
                                   class="btn btn-info btn-sm"
                                   title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('po-suppliers.edit', $po->id) }}"
                                   class="btn btn-warning btn-sm"
                                   title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('po-suppliers.destroy', $po->id) }}"
                                      method="POST"
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            title="Delete"
                                            onclick="return confirm('Are you sure you want to delete this PO?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11"
                                class="text-center empty-state">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                There is no Supplier PO data available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- PAGINATION --}}
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted small">
                Showing
                {{ $poSuppliers->firstItem() ?? 0 }}
                to
                {{ $poSuppliers->lastItem() ?? 0 }}
                of
                {{ $poSuppliers->total() }}
                entries
            </div>
            <div>
                {{ $poSuppliers->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection