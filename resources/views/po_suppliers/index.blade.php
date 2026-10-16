@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-po-suppliers-index bgs-mode-index')
@section('title', 'Supplier PO Management')
@section('content')



<div class="container-fluid py-4">
    <div class="card main-card">

        <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
            <h4>
                <i class="bi bi-file-earmark-text-fill me-2"></i>
                Supplier PO Management
            </h4>

            <a href="{{ route('po-suppliers.create') }}"
               class="btn btn-light-custom btn-sm px-3 py-2">
                <i class="bi bi-plus-circle-fill me-1"></i>
                Add New Supplier PO
            </a>
        </div>

        <div class="card-body p-4">

            @if(session('success'))
                <div id="success-alert"
                     class="alert alert-success border-0 shadow-sm d-flex align-items-center fade show mb-4 bgs-u-8a1856d11134"
                    >
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>

                    <div>{{ session('success') }}</div>

                    <button type="button"
                            class="btn-close ms-auto bgs-u-aae30f4ce161"
                            data-bs-dismiss="alert"
                           >
                    </button>
                </div>

                <script>
                    setTimeout(() => {
                        let alert = document.getElementById('success-alert');

                        if (alert) {
                            let bsAlert =
                                bootstrap.Alert.getOrCreateInstance(alert);

                            bsAlert.close();
                        }
                    }, 2500);
                </script>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4 bgs-u-f812d16479e9"
                    >
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    {{ session('error') }}
                </div>
            @endif

            {{-- SEARCH & FILTER BAR --}}
            <div class="search-filter-bar">
                <form method="GET"
                      action="{{ route('po-suppliers.index') }}" class="bgs-filter-form">

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="filter-label">
                                Search Keyword
                            </label>

                            <input type="text"
                                   name="search"
                                   class="form-control py-2"
                                   placeholder="Type PO # or Supplier Name..."
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="filter-label">
                                PO Status
                            </label>

                            <select name="status"
                                    class="form-select py-2">
                                <option value="">All Status</option>
                                <option value="draft"
                                    {{ request('status') == 'draft' ? 'selected' : '' }}>
                                    Draft
                                </option>
                                <option value="sent"
                                    {{ request('status') == 'sent' ? 'selected' : '' }}>
                                    Sent
                                </option>
                                <option value="confirmed"
                                    {{ request('status') == 'confirmed' ? 'selected' : '' }}>
                                    Confirmed
                                </option>
                                <option value="received"
                                    {{ request('status') == 'received' ? 'selected' : '' }}>
                                    Received
                                </option>
                                <option value="cancelled"
                                    {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                                    Cancelled
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="filter-label">
                                Receipt Status
                            </label>

                            <select name="receive_status"
                                    class="form-select py-2">
                                <option value="">All</option>
                                <option value="pending"
                                    {{ request('receive_status') == 'pending' ? 'selected' : '' }}>
                                    Pending
                                </option>
                                <option value="partial"
                                    {{ request('receive_status') == 'partial' ? 'selected' : '' }}>
                                    Partial
                                </option>
                                <option value="completed"
                                    {{ request('receive_status') == 'completed' ? 'selected' : '' }}>
                                    Completed
                                </option>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit"
                                    class="btn btn-primary py-2 w-100 fw-bold bgs-u-40db451c03f9"
                                   >
                                <i class="bi bi-search me-1"></i>
                                Filter
                            </button>

                            <a href="{{ route('po-suppliers.index') }}"
                               class="btn btn-outline-secondary py-2 px-3"
                               title="Reset Filters">
                                <i class="bi bi-arrow-repeat"></i>
                            </a>
                        </div>

                    </div>
                </form>
            </div>

            {{-- TABLE --}}
            <div class="border rounded-3 overflow-hidden shadow-sm mb-3">
                <div class="table-responsive po-supplier-table-wrap">

                    <table class="table table-modern align-middle m-0 po-supplier-table bgs-index-table">
                        <thead>
                            <tr>
                                <th width="4%" class="text-center">
                                    No
                                </th>

                                <th width="9%"
                                    class="text-center col-action bgs-col-action">
                                    Actions
                                </th>

                                <th class="col-supplier">
                                    Supplier
                                </th>

                                <th class="col-po-number">
                                    PO #
                                </th>

                                <th width="9%" class="text-center">
                                    PO Date
                                </th>

                                <th class="col-po-customer">
                                    PO Customer #
                                </th>

                                <th width="11%" class="text-end">
                                    PO Total
                                </th>

                                <th width="9%" class="text-center">
                                    PO Status
                                </th>

                                <th width="10%" class="text-center">
                                    Receipt Status
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($poSuppliers as $index => $po)
                                <tr>

                                    <td class="text-center text-muted fw-bold">
                                        {{ $poSuppliers->firstItem() + $index }}
                                    </td>

                                    <td class="text-center col-action">
                                        <div class="d-flex justify-content-center action-btn-group">

                                            @php
    $hasActiveGoodsReceipt = (int) ($po->goods_receipts_count ?? 0) > 0;
    $canEdit = in_array($po->status, ['draft', 'confirmed'], true)
        && !$hasActiveGoodsReceipt;
    $canDelete = $po->status === 'draft'
        && !$hasActiveGoodsReceipt;
@endphp
<a href="{{ route('po-suppliers.show', $po->id) }}"
                                               class="btn btn-info btn-sm text-white bgs-u-7dfad3480fc0"
                                               title="View Detail"
                                              >
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            @if ($canEdit)
<a href="{{ route('po-suppliers.edit', $po->id) }}"
                                               class="btn btn-warning btn-sm text-dark"
                                               title="Edit Data">
                                                <i class="bi bi-pencil"></i>
                                            </a>
@endif

                                            @if ($canDelete)
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
@endif

                                        </div>
                                    </td>

                                    <td class="col-supplier">
                                        <div class="supplier-name">
                                            {{ $po->supplier->name ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="col-po-number">
                                        <span class="po-number">
                                            {{ $po->po_supplier_number }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        {{ $po->po_date
                                            ? date('d/m/Y', strtotime($po->po_date))
                                            : '-' }}
                                    </td>

                                    <td class="col-po-customer">
                                        {{ $po->poCustomer->po_number ?? '-' }}
                                    </td>

                                    <td class="text-end total-column">
                                        Rp {{ number_format($po->total ?? 0, 0, ',', '.') }}
                                    </td>

                                    <td class="text-center">
                                        @if($po->status == 'draft')
                                            <span class="badge bg-primary badge-custom">
                                                Draft
                                            </span>
                                        @elseif($po->status == 'sent')
                                            <span class="badge bg-warning text-dark badge-custom">
                                                Sent
                                            </span>
                                        @elseif($po->status == 'confirmed')
                                            <span class="badge bg-success badge-custom">
                                                Confirmed
                                            </span>
                                        @elseif($po->status == 'received')
                                            <span class="badge bg-success badge-custom">
                                                Received
                                            </span>
                                        @elseif($po->status == 'cancelled')
                                            <span class="badge bg-danger badge-custom">
                                                Cancelled
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        @if($po->receipt_status == 'pending')
                                            <span class="badge bg-secondary badge-custom">
                                                Pending
                                            </span>
                                        @elseif($po->receipt_status == 'partial')
                                            <span class="badge bg-warning text-dark badge-custom">
                                                Partial
                                            </span>
                                        @elseif($po->receipt_status == 'completed')
                                            <span class="badge bg-success badge-custom">
                                                Completed
                                            </span>
                                        @elseif($po->receipt_status == 'cancelled')
                                            <span class="badge bg-danger badge-custom">
                                                Cancelled
                                            </span>
                                        @endif
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9"
                                        class="text-center empty-state">
                                        <i class="bi bi-inbox-fill fs-2 d-block mb-2 text-muted"></i>
                                        There is no Supplier PO data available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>

            {{-- PAGINATION --}}
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2">
                <div class="small fw-bold bgs-u-7e4cfe51c78c"
                    >
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
</div>

@endsection