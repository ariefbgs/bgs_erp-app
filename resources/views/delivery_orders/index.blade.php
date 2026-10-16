@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-delivery-orders-index bgs-mode-index')
@section('title', 'Delivery Order Management')
@section('content')


<div class="card main-card">
    <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
        <h4>
            <i class="bi bi-file-earmark-text"></i>
            Delivery Order Management
        </h4>
        <a href="{{ route('delivery-orders.create') }}" class="btn btn-light-custom btn-sm">
            <i class="bi bi-plus-circle"></i>
            Add New Delivery Order
        </a>
    </div>
    <div class="card-body p-4">
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
            <form method="GET" action="{{ route('delivery-orders.index') }}" >
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
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
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
        <div class="table-responsive quotation-table-wrap">
            <table class="table table-modern table-hover align-middle quotation-table bgs-index-table">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="10%" class="text-center col-action">Actions</th>
                        <th width="20%" class="text-center">Customer</th>
                        <th width="15%" class="text-center">PO Customer #</th>
                        <th width="15%" class="text-center">DO #</th>
                        <th width="12%" class="text-center">Delivery Date</th>
                        <th width="10%" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dos as $index => $do)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-center col-action">
                                <div class="d-flex justify-content-center action-btn-group">
                                <!-- Tombol Detail -->
                                <a href="{{ route('delivery-orders.show', $do->id) }}" class="btn btn-info btn-sm btn-action" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <!-- Edit hanya untuk Pending -->
                                @if($do->status === 'pending')
                                    <a href="{{ route('delivery-orders.edit', $do->id) }}" class="btn btn-warning btn-sm btn-action" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @else
                                    <button class="btn btn-secondary btn-sm btn-action" disabled title="Not editable">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                @endif

                                @php
                                    $doUser = Auth::user();
                                    $canMarkDelivered =
                                        $doUser?->hasPermission('delivery_order_mark_delivered') ?? false;
                                    $canReopen =
                                        $doUser?->hasPermission('delivery_order_reopen') ?? false;
                                @endphp

                                @if($do->status === 'pending' && $canMarkDelivered)
                                    <form method="POST"
                                          action="{{ route('delivery-orders.mark-delivered', $do->id) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Mark this Delivery Order as Delivered?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-success btn-sm btn-action"
                                                title="Mark Delivered">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    </form>
                                @endif

                                @if($do->status === 'delivered' && $canReopen)
                                    <form method="POST"
                                          action="{{ route('delivery-orders.reopen', $do->id) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Reopen this Delivery Order to Pending?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-outline-warning btn-sm btn-action"
                                                title="Reopen to Pending">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
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
                                </div>
                            </td>
                            <td class="col-customer customer-name">{{ $do->poCustomer->customer->name ?? '-' }}</td>
                            <td>{{ $do->poCustomer->po_number ?? '-' }}</td>
                            <td>{{ $do->do_number ?? '-' }}</td>
                            <td>{{ date('d/m/Y', strtotime($do->delivery_date)) }}</td>
                            <td>
                                @if($do->status == 'pending')
                                    <span class="badge badge-custom bg-warning">Pending</span>
                                @elseif($do->status == 'delivered')
                                    <span class="badge badge-custom bg-success">Delivered</span>
                                @else
                                    <span class="badge badge-custom bg-secondary">Legacy: {{ ucfirst($do->status) }}</span>
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