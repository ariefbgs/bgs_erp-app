@extends('layouts.app')
@section('title', 'Delivery Order Management')
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
            Delivery Order Management
        </h4>
        <a href="{{ route('delivery-orders.create') }}" class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle"></i>
            Add New Delivery Order
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
            <form method="GET" action="{{ route('delivery-orders.index') }}">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="filter-label">Search</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="DO # / Customer Name"
                               value="{{ request('search') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="filter-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') == 'draft' ? 'selected' : '' }}>Pending</option>
                            <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial Shipment</option>
                            <option value="shipped" {{ request('status') == 'sent' ? 'selected' : '' }}>Shipped</option>
                            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2 w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('delivery-orders.index') }}" class="btn btn-secondary" title="Reset Filters">
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
                        <th width="15%" class="text-center">PO Customer #</th>
                        <th width="15%" class="text-center">DO #</th>
                        <th width="12%" class="text-center">Delivery Date</th>
                        <th width="10%" class="text-center">Status</th>
                        <th width="10%" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dos as $index => $do)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $do->poCustomer->customer->name ?? '-' }}</td>
                            <td>{{ $do->poCustomer->po_number ?? '-' }}</td>
                            <td>{{ $do->do_number ?? '-' }}</td>
                            <td>{{ date('d/m/Y', strtotime($do->delivery_date)) }}</td>
                            <td>
                                @if($do->status == 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($do->status == 'partial')
                                    <span class="badge bg-primary">Partial Shipment</span>
                                @elseif($do->status == 'shipped')
                                    <span class="badge bg-primary">Shipped</span>
                                @elseif($do->status == 'delivered')
                                    <span class="badge bg-success">Delivered</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <!-- Tombol Detail -->
                                <a href="{{ route('delivery-orders.show', $do->id) }}" class="btn btn-info btn-sm btn-action" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <!-- Tombol Edit (hanya jika status pending) -->
                                @if(in_array($do->status, ['pending', 'partial']))
                                    <a href="{{ route('delivery-orders.edit', $do->id) }}" class="btn btn-warning btn-sm btn-action" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @else
                                    <button class="btn btn-secondary btn-sm btn-action" disabled title="Not editable">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                @endif
                                <!-- Tombol Hapus (hanya jika status pending) -->
                                @if($do->status == 'pending')
                                    <form action="{{ route('delivery-orders.destroy', $do->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm btn-action" onclick="return confirm('Are you sure you want to delete this DO?')" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <button class="btn btn-secondary btn-sm btn-action" disabled title="Not deletable">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">There is no data available for Delivery Order</td></tr>
                    @endforelse
                </tbody>
            </table>

            {{-- PAGINATION --}}
            <div class="mt-3 d-flex justify-content-end">
                {{ $dos->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection