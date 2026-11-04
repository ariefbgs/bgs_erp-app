






@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-po-customers-index bgs-mode-index')
@section('title', 'Customer PO Management')
@section('content')


<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
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
                <form method="GET" action="{{ route('po-customers.index') }}" class="bgs-filter-form">
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
                            <button type="submit" class="btn btn-primary py-2 w-100 fw-bold bgs-u-40db451c03f9">
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
                    <table class="table table-modern align-middle m-0 po-customer-table bgs-index-table">
                        <thead>
                            <tr>
                                <th width="4%" class="text-center">No</th>
                                <th width="9%" class="text-center col-action bgs-col-action">Actions</th>
                                <th class="col-customer">Customer</th>
                                <th class="col-po-number">PO #</th>
             <th class="col-attachment">Attachment</th> {{-- Ditambahkan lebar kolom agar space memadai --}}
                                <th width="9%" class="text-center">PO Date</th>
                                <th width="9%" class="text-center">Delivery</th>
                                <th width="11%" class="text-end">PO Amount</th>
                                <th width="11%" class="text-end">Invoiced Amount</th>
                                <th width="11%" class="text-end">Remaining Amount</th>
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
                                               class="btn btn-info btn-sm text-white bgs-u-7dfad3480fc0"
                                               title="View Detail">
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
                                        Rp {{ number_format($po->po_commercial_amount ?? $po->subtotal ?? 0, 0, ',', '.') }}
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
                <div class="small fw-bold bgs-u-7e4cfe51c78c">
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


