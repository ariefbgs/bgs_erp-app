<style>
    /* R1E-02G3B: sticky Action header must match table header */
    .table-modern thead th.col-action {
        background-color: #0f4c75 !important;
        color: #ffffff !important;
    }
</style>

<style>
    /* R1E-02G3 dedicated attachment column */
    .po-customer-table .col-attachment {
        min-width: 95px;
        width: 95px;
        text-align: center;
        white-space: nowrap;
    }

    .po-customer-table .attachment-missing {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 700;
        color: #ffffff;
        background-color: #dc3545;
        border: 1px solid #dc3545;
        border-radius: 0.375rem;
        padding: 4px 8px;
        line-height: 1;
        white-space: nowrap;
    }
</style>

<style>
    /* R1E-02G2 refinements */


    /* Sticky body cells remain opaque while scrolling */
    .po-customer-table tbody .col-action {
        background-color: #ffffff;
    }

    /* Normalize all action buttons */
    .po-customer-table .action-btn-group .btn {
        width: 34px;
        height: 34px;
        min-width: 34px;
        min-height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        line-height: 1;
    }

    .po-customer-table .action-btn-group form {
        display: inline-flex;
        margin: 0;
    }

    /* Compact attachment indicator */
    .po-customer-table .attachment-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        text-decoration: none;
        border-radius: 5px;
    }

    .po-customer-table .attachment-icon i {
        font-size: 1rem;
    }
</style>

<style>
    /* PO Customer index horizontal-table usability */
    .po-customer-table-wrap {
        overflow-x: auto;
        position: relative;
    }

    .po-customer-table {
        min-width: 1400px;
        table-layout: auto;
    }

    .po-customer-table th,
    .po-customer-table td {
        vertical-align: middle;
    }

    /* PO number: wider and avoid excessive row height */
    .po-customer-table .col-po-number {
        min-width: 200px;
        width: 200px;
        white-space: nowrap;
    }

    /* Customer: wider, controlled wrapping */
    .po-customer-table .col-customer {
        min-width: 220px;
        width: 220px;
        white-space: normal;
        line-height: 1.25;
    }

    /*
     * Sticky/frozen Action column.
     * It remains visible while the middle/right columns scroll horizontally.
     */
    .po-customer-table .col-action {
        position: sticky;
        left: 0;
        z-index: 4;
        min-width: 150px;
        width: 150px;
        white-space: nowrap;
        box-shadow: 3px 0 5px rgba(0, 0, 0, 0.06);
    }

    .po-customer-table thead .col-action {
        z-index: 6;
    }

    /* Ensure sticky cell has solid background over scrolling content */
    .po-customer-table tbody .col-action {
        background-color: #fff;
    }
</style>
@extends('layouts.app')
@section('title', 'Customer PO Management')
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

    /* Header dengan gradasi warna Ocean Blue mengikuti modul manajemen transaksi */
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
    .po-number {
        font-weight: 600;
        color: #0284c7; /* Warna Ocean Blue penanda link/entitas utama */
        display: block;
        margin-bottom: 2px;
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

    /* Style Khusus untuk Badge Status File Attachment */
    .attachment-badge {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .attachment-yes {
        background-color: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }
    .attachment-yes:hover {
        background-color: #bae6fd;
        color: #0284c7;
    }
    .attachment-no {
        background-color: #f1f5f9;
        color: #94a3b8;
        border: 1px solid #e2e8f0;
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h4>
                <i class="bi bi-file-earmark-text-fill me-2"></i>
                Customer PO Management
            </h4>
            <a href="{{ route('po-customers.create') }}" class="btn btn-light-custom btn-sm px-3 py-2">
                <i class="bi bi-plus-circle-fill me-1"></i>
                Add New Customer PO
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
                <form method="GET" action="{{ route('po-customers.index') }}">
                    <div class="row g-3">
                        {{-- INPUT PENCARIAN --}}
                        <div class="col-md-4">
                            <label class="filter-label">Search Keyword</label>
                            <input type="text"
                                   name="search"
                                   class="form-control py-2"
                                   placeholder="Type PO # or Customer Name..."
                                   value="{{ request('search') }}">
                        </div>

                        {{-- PO STATUS FILTER --}}
                        <div class="col-md-3">
                            <label class="filter-label">PO Status</label>
                            <select name="status" class="form-select py-2">
                                <option value="">All Status</option>
                                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                                <option value="proceed" {{ request('status') == 'proceed' ? 'selected' : '' }}>Proceed</option>
                                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>

                        {{-- INVOICE STATUS FILTER --}}
                        <div class="col-md-3">
                            <label class="filter-label">Invoice Status</label>
                            <select name="invoice_status" class="form-select py-2">
                                <option value="">All Invoice Status</option>
                                <option value="issue yet" {{ request('invoice_status') == 'issue yet' ? 'selected' : '' }}>Issue Yet</option>
                                <option value="partial" {{ request('invoice_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="completed" {{ request('invoice_status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ request('invoice_status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                <option value="paid" {{ request('invoice_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                        </div>

                        {{-- TOMBOL SUBMIT --}}
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary py-2 w-100 fw-bold" style="background-color: #0284c7; border-color: #0284c7;">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                            <a href="{{ route('po-customers.index') }}" class="btn btn-outline-secondary py-2 px-3" title="Reset Filters">
                                <i class="bi bi-arrow-repeat"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- TABEL DATA --}}
            <div class="border rounded-3 overflow-hidden shadow-sm mb-3">
                <div class="table-responsive po-customer-table-wrap">
                    <table class="table table-modern align-middle m-0 po-customer-table">
                        <thead>
                            <tr>
                                <th width="4%" class="text-center">No</th>
                                <th width="9%" class="text-center col-action">Actions</th>
                                <th class="col-customer">Customer</th>
                                <th class="col-po-number">PO #</th>
             <th class="col-attachment">Attachment</th> {{-- Ditambahkan lebar kolom agar space memadai --}}
                                <th width="9%" class="text-center">PO Date</th>
                                <th width="9%" class="text-center">Delivery</th>
                                <th width="11%" class="text-end">PO Total</th>
                                <th width="11%" class="text-end">Invoiced</th>
                                <th width="11%" class="text-end">Remaining</th>
                                <th width="8%" class="text-center">PO Status</th>
             <th width="9%" class="text-center">Procurement</th>
                                <th width="8%" class="text-center">Invoice</th>

                            </tr>
                        </thead>
                        <tbody>
                            @forelse($poCustomers as $index => $po)
                                <tr>
                                    {{-- NOMOR URUT --}}
                                    <td class="text-center text-muted fw-bold">
                                        {{ $poCustomers->firstItem() + $index }}
                                    </td>

                                    {{-- ACTION BUTTONS --}}
                                    <td class="text-center col-action">
                                        <div class="d-flex justify-content-center action-btn-group">
                                            <a href="{{ route('po-customers.show', $po->id) }}"
                                               class="btn btn-info btn-sm text-white"
                                               title="View Detail" style="background-color: #0ea5e9; border-color: #0ea5e9;">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('po-customers.edit', $po->id) }}"
                                               class="btn btn-warning btn-sm text-dark"
                                               title="Edit Data">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('po-customers.destroy', $po->id) }}"
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
                                        </div>
                                    </td>

                                    {{-- NAMA CUSTOMER --}}
                                    <td class="col-customer">
                                        <div class="customer-name">
                                            {{ $po->customer->name ?? '-' }}
                                        </div>
                                    </td>

                                    {{-- NOMOR PO --}}
                                    <td class="col-po-number">
                                        <span class="po-number">
                                            {{ $po->po_number }}
                                        </span>
                                    </td>

                                    {{-- ATTACHMENT --}}
                                    <td class="col-attachment">
                                        @if($po->attachment)
                                            <a href="{{ route('po-customers.view-image', $po->id) }}"
                                               target="_blank"
                                               class="attachment-icon attachment-yes"
                                               title="View attachment">
                                                <i class="bi bi-paperclip"></i>
                                            </a>
                                        @else
                                            <span class="attachment-missing">Belum Ada</span>
                                        @endif
                                    </td>

                                    {{-- TANGGAL PO --}}
                                    <td class="text-center">
                                        {{ $po->po_date ? date('d/m/Y', strtotime($po->po_date)) : '-' }}
                                    </td>

                                    {{-- TANGGAL DELIVERY --}}
                                    <td class="text-center">
                                        {{ $po->delivery_date ? date('d/m/Y', strtotime($po->delivery_date)) : '-' }}
                                    </td>

                                    {{-- PO TOTAL --}}
                                    <td class="text-end total-column">
                                        Rp {{ number_format($po->total ?? 0, 0, ',', '.') }}
                                    </td>

                                    {{-- INVOICED --}}
                                    <td class="text-end total-column text-muted">
                                        Rp {{ number_format($po->total_invoiced ?? 0, 0, ',', '.') }}
                                    </td>

                                    {{-- REMAINING --}}
                                    <td class="text-end total-column">
                                        @if(($po->remaining_total ?? 0) > 0)
                                            <span class="text-danger">
                                                Rp {{ number_format($po->remaining_total ?? 0, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-success">Rp 0</span>
                                        @endif
                                    </td>

                                    {{-- PO STATUS BADGE --}}
                                    <td class="text-center">
                                        @if($po->status == 'received')
                                            <span class="badge bg-primary badge-custom">Received</span>
                                        @elseif($po->status == 'proceed')
                                            <span class="badge bg-warning text-dark badge-custom">Proceed</span>
                                        @elseif($po->status == 'delivered')
                                            <span class="badge bg-success badge-custom">Delivered</span>
                                        @elseif($po->status == 'cancelled')
                                            <span class="badge bg-danger badge-custom">Cancelled</span>
                                        @endif
                                    </td>

                                                     {{-- PROCUREMENT STATUS BADGE --}}
                 <td class="text-center">
                     @if($po->procurement_status == 'pending')
                         <span class="badge bg-secondary badge-custom">Pending</span>
                     @elseif($po->procurement_status == 'partial')
                         <span class="badge bg-warning text-dark badge-custom">Partial</span>
                     @elseif($po->procurement_status == 'fully_procured')
                         <span class="badge bg-success badge-custom">Fully Procured</span>
                     @else
                         <span class="badge bg-secondary badge-custom">Pending</span>
                     @endif
                 </td>
{{-- INVOICE STATUS BADGE --}}
                                    <td class="text-center">
                                        @if($po->invoice_status == 'issue yet')
                                            <span class="badge bg-secondary badge-custom">Issue Yet</span>
                                        @elseif($po->invoice_status == 'partial')
                                            <span class="badge bg-warning text-dark badge-custom">Partial</span>
                                        @elseif($po->invoice_status == 'completed')
                                            <span class="badge bg-success badge-custom">Completed</span>
                                        @elseif($po->invoice_status == 'paid')
                                            <span class="badge bg-primary badge-custom">Paid</span>
                                        @elseif($po->invoice_status == 'cancelled')
                                            <span class="badge bg-danger badge-custom">Cancelled</span>
                                        @endif
                                    </td>


                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center empty-state">
                                        <i class="bi bi-inbox-fill fs-2 d-block mb-2 text-muted"></i>
                                        There is no Customer PO data available.
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
                    Showing {{ $poCustomers->firstItem() ?? 0 }} to {{ $poCustomers->lastItem() ?? 0 }} of {{ $poCustomers->total() }} entries
                </div>
                <div>
                    {{ $poCustomers->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


