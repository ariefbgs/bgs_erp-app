@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-goods-receipts-index bgs-mode-index')
@section('title', 'Good Receipt Management')
@section('content')


<div class="card main-card">
    <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
        <h4>
            <i class="bi bi-file-earmark-text"></i>
            Good Receipt Management
        </h4>
        <a href="{{ route('goods-receipts.create') }}" class="btn btn-light-custom btn-sm">
            <i class="bi bi-plus-circle"></i>
            Add New Good Receipt
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
        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm mb-4 bgs-u-f812d16479e9">
                <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
            </div>
        @endif  

        {{-- SEARCH & FILTER --}}
        <div class="search-filter-bar">
            <form method="GET" action="{{ route('goods-receipts.index') }}">
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
                            <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2 w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="{{ route('goods-receipts.index') }}" class="btn btn-secondary" title="Reset Filters">
                            <i class="bi bi-arrow-repeat"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive quotation-table-wrap">
            <table class="table table-modern table-hover align-middle quotation-table bgs-index-table bgs-actions-compact">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th class="text-center col-action">Actions</th>
                        <th width="20%" class="text-center">Supplier</th>
                        <th width="15%" class="text-center">PO Supplier #</th>
                        <th width="15%" class="text-center">PO Date</th>
                        <th width="15%" class="text-center">Receipt #</th>
                        <th width="12%" class="text-center">Receipt Date</th>
                        <th width="10%" class="text-center">Status</th>
                        <th width="10%" class="text-center">Receipt Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $index => $receipt)
                        @php
                            $poSupplier = $receipt->poSupplier;
                            $isPoCompleted = ($poSupplier && $poSupplier->receipt_status == 'completed');
                        @endphp
                        <tr>
                            <td class="text-center">{{ $receipts->firstItem() + $index }}</td>
                            <td class="text-center col-action">
                                <div class="d-flex justify-content-center action-btn-group">
                                {{-- Detail --}}
                                <a href="{{ route('goods-receipts.show', $receipt->id) }}" class="btn btn-info btn-sm" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Edit - selalu aktif --}}
                                <a href="{{ route('goods-receipts.edit', $receipt->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Delete - selalu aktif (controller akan memvalidasi) --}}
                                <form action="{{ route('goods-receipts.destroy', $receipt->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this goods receipt?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>

                                {{-- Cancel - hanya untuk status bukan cancelled --}}
                                @if($receipt->status != 'cancelled')
                                    <form action="{{ route('goods-receipts.cancel', $receipt->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Cancel" onclick="return confirm('Are you sure you want to cancel this receipt?')">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                @endif
                                </div>
                            </td>
                            <td class="col-customer customer-name">{{ $poSupplier->supplier->name ?? '-' }}</td>
                            <td>{{ $poSupplier->po_supplier_number ?? '-' }}</td>
                            <td class="text-center">
                                {{ $poSupplier && $poSupplier->po_date ? \Carbon\Carbon::parse($poSupplier->po_date)->format('d/m/Y') : '-' }}
                            </td>
                            <td>{{ $receipt->receipt_number }}</td>
                            <td class="text-center">
                                {{ $receipt->receipt_date ? \Carbon\Carbon::parse($receipt->receipt_date)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="text-center">
                                @if($receipt->status == 'partial')
                                    <span class="badge badge-custom bg-secondary">Partial</span>
                                @elseif($receipt->status == 'completed')
                                    <span class="badge badge-custom bg-success">Completed</span>
                                @elseif($receipt->status == 'cancelled')
                                    <span class="badge badge-custom bg-danger">Cancelled</span>
                                @else
                                    <span class="badge badge-custom bg-secondary">{{ ucfirst($receipt->status) }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $status = $poSupplier ? $poSupplier->receipt_status : 'pending';
                                @endphp
                                @if($status == 'pending')
                                    <span class="badge badge-custom bg-secondary">Pending</span>
                                @elseif($status == 'partial')
                                    <span class="badge badge-custom bg-warning">Partially Received</span>
                                @elseif($status == 'completed')
                                    <span class="badge badge-custom bg-success">Fully Received</span>
                                @else
                                    <span class="badge badge-custom bg-secondary">{{ ucfirst($status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center empty-state">
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