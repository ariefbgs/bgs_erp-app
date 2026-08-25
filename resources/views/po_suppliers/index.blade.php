@extends('layouts.app')
@section('title', 'Supplier PO Management')
@section('content')

<style>
    body {
        background-color: #f1f5f9;
    }

    .main-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        background: #fff;
        overflow: hidden;
    }

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

    .search-filter-bar {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 25px;
    }

    .filter-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #475569;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .table-modern thead th {
        background-color: #0c4a6e;
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
        background-color: #f1f5f9 !important;
    }

    .table-modern tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        font-size: 0.9rem;
        color: #334155;
        border-color: #f1f5f9;
    }

    .supplier-name {
        font-weight: 600;
        color: #1e293b;
    }

    .po-number {
        font-weight: 600;
        color: #0284c7;
        display: block;
        margin-bottom: 2px;
    }

    .total-column {
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
    }

    .badge-custom {
        padding: 0.5em 0.9em;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
    }

    .empty-state {
        padding: 50px 20px;
        color: #64748b;
        font-weight: 500;
    }

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

    .form-control,
    .form-select {
        border-color: #cbd5e1;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
    }

    /*
     * PO Supplier horizontal table follows
     * PO Customer transaction-index baseline.
     */
    .po-supplier-table-wrap {
        overflow-x: auto;
        position: relative;
    }

    .po-supplier-table {
        min-width: 1400px;
        table-layout: auto;
    }

    .po-supplier-table th,
    .po-supplier-table td {
        vertical-align: middle;
    }

    .po-supplier-table .col-supplier {
        min-width: 220px;
        width: 220px;
        white-space: normal;
        line-height: 1.25;
    }

    .po-supplier-table .col-po-number {
        min-width: 200px;
        width: 200px;
        white-space: nowrap;
    }

    .po-supplier-table .col-po-customer {
        min-width: 180px;
        width: 180px;
    }

    /*
     * Frozen Action column.
     */
    .po-supplier-table .col-action {
        position: sticky;
        left: 0;
        z-index: 4;
        min-width: 150px;
        width: 150px;
        white-space: nowrap;
        box-shadow: 3px 0 5px rgba(0, 0, 0, 0.06);
    }

    .po-supplier-table thead .col-action {
        background-color: #0f4c75 !important;
        color: #ffffff !important;
        z-index: 6;
    }

    .po-supplier-table tbody .col-action {
        background-color: #ffffff;
    }

    .po-supplier-table .action-btn-group .btn {
        width: 34px;
        height: 34px;
        min-width: 34px;
        min-height: 34px;
        padding: 0;
        margin: 0 2px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        line-height: 1;
    }

    .po-supplier-table .action-btn-group form {
        display: inline-flex;
        margin: 0;
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">

        <div class="card-header-custom d-flex justify-content-between align-items-center">
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
                     class="alert alert-success border-0 shadow-sm d-flex align-items-center fade show mb-4"
                     style="background-color: #f0fdf4; color: #15803d; border-left: 4px solid #16a34a !important; border-radius: 6px;">
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>

                    <div>{{ session('success') }}</div>

                    <button type="button"
                            class="btn-close ms-auto"
                            data-bs-dismiss="alert"
                            style="font-size: 0.8rem;">
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
                <div class="alert alert-danger border-0 shadow-sm mb-4"
                     style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    {{ session('error') }}
                </div>
            @endif

            {{-- SEARCH & FILTER BAR --}}
            <div class="search-filter-bar">
                <form method="GET"
                      action="{{ route('po-suppliers.index') }}">

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
                                    class="btn btn-primary py-2 w-100 fw-bold"
                                    style="background-color: #0284c7; border-color: #0284c7;">
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

                    <table class="table table-modern align-middle m-0 po-supplier-table">
                        <thead>
                            <tr>
                                <th width="4%" class="text-center">
                                    No
                                </th>

                                <th width="9%"
                                    class="text-center col-action">
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
                                               class="btn btn-info btn-sm text-white"
                                               title="View Detail"
                                               style="background-color: #0ea5e9; border-color: #0ea5e9;">
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
                <div class="small fw-bold"
                     style="color: #475569;">
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