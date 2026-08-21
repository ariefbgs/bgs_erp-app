@extends('layouts.app')

@section('title', 'Sales Invoice Management')

@section('content')

<style>
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
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #dee2e6;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    .action-buttons .btn {
        margin: 2px;
    }

    .badge {
        font-size: 12px;
        padding: 6px 8px;
    }

    .card-header h4 {
        margin-bottom: 0;
    }
</style>

<div class="card shadow-sm">

    {{-- HEADER --}}
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4>Data Sales Invoice</h4>

        <a href="{{ route('invoice-customers.create') }}"
           class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle"></i>
            Create New Invoice
        </a>
    </div>

    <div class="card-body">

        {{-- SUCCESS MESSAGE --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ERROR MESSAGE --}}
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- SEARCH & FILTER --}}
        <div class="search-filter-bar">

            <form method="GET"
                  action="{{ route('invoice-customers.index') }}">

                <div class="row g-3">

                    {{-- SEARCH --}}
                    <div class="col-md-3">
                        <label class="form-label">
                            Search
                        </label>

                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Invoice Number / Customer"
                               value="{{ request('search') }}">
                    </div>

                    {{-- TYPE --}}
                    <div class="col-md-2">
                        <label class="form-label">
                            Type
                        </label>

                        <select name="type"
                                class="form-select">

                            <option value="">
                                All
                            </option>

                            <option value="proforma"
                                {{ request('type') == 'proforma' ? 'selected' : '' }}>
                                Proforma
                            </option>

                            <option value="sales"
                                {{ request('type') == 'sales' ? 'selected' : '' }}>
                                Sales Invoice
                            </option>

                        </select>
                    </div>

                    {{-- STATUS --}}
                    <div class="col-md-2">
                        <label class="form-label">
                            Status
                        </label>

                        <select name="status"
                                class="form-select">

                            <option value="">
                                All
                            </option>

                            <option value="proforma"
                                {{ request('status') == 'proforma' ? 'selected' : '' }}>
                                Proforma
                            </option>

                            <option value="partial"
                                {{ request('status') == 'partial' ? 'selected' : '' }}>
                                Partial
                            </option>

                            <option value="completed"
                                {{ request('status') == 'completed' ? 'selected' : '' }}>
                                Completed
                            </option>

                            <option value="cancelled"
                                {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                                Cancelled
                            </option>

                        </select>
                    </div>

                    {{-- PAYMENT STATUS --}}
                    <div class="col-md-2">
                        <label class="form-label">
                            Payment Status
                        </label>

                        <select name="payment_status"
                                class="form-select">

                            <option value="">
                                All
                            </option>

                            <option value="sent"
                                {{ request('payment_status') == 'sent' ? 'selected' : '' }}>
                                Sent
                            </option>

                            <option value="paid"
                                {{ request('payment_status') == 'paid' ? 'selected' : '' }}>
                                Paid
                            </option>

                        </select>
                    </div>

                    {{-- BUTTON --}}
                    <div class="col-md-3 d-flex align-items-end">

                        <button type="submit"
                                class="btn btn-primary me-2">

                            <i class="bi bi-search"></i>
                            Search
                        </button>

                        <a href="{{ route('invoice-customers.index') }}"
                           class="btn btn-secondary">

                            <i class="bi bi-arrow-repeat"></i>
                            Reset
                        </a>

                    </div>

                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th width="3%" class="text-center">No</th>
                        <th width="12%" class="text-center">
                            Invoice #
                        </th>
                        <th width="8%" class="text-center">
                            Invoice Date
                        </th>
                        <th width="8%" class="text-center">
                            Due Date
                        </th>
                        <th width="10%" class="text-center">
                            Customer
                        </th>
                        <th width="10%" class="text-center">
                            PO Customer #
                        </th>
                        <th width="10%" class="text-center">
                            Total
                        </th>
                        <th width="10%" class="text-center">
                            Remaining
                        </th>
                        {{-- PENAMBAHAN HEADER KOLOM TAX ATTACHMENT INDIKATOR --}}
                        <th width="5%" class="text-center">
                            Tax File
                        </th>
                        <th width="10%" class="text-center">
                            Type
                        </th>
                        <th width="8%" class="text-center">
                            Status
                        </th>
                        <th width="8%" class="text-center">
                            Payment
                        </th>
                        <th width="12%" class="text-center">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $index => $inv)
                        <tr>
                            {{-- NO --}}
                            <td class="text-center">
                                {{ $invoices->firstItem() + $index }}
                            </td>
                            {{-- INVOICE NUMBER --}}
                            <td>
                                {{ $inv->invoice_number }}
                            </td>
                            {{-- INVOICE DATE --}}
                            <td class="text-center">
                                {{ \Carbon\Carbon::parse($inv->invoice_date)->format('d/m/Y') }}
                            </td>
                            {{-- DUE DATE --}}
                            <td class="text-center">
                                @if($inv->due_date)
                                    {{ \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            {{-- CUSTOMER --}}
                            <td>
                                {{ $inv->poCustomer->customer->name ?? '-' }}
                            </td>
                            {{-- PO NUMBER --}}
                            <td class="text-center">
                                {{ $inv->poCustomer->po_number ?? '-' }}
                            </td>
                            {{-- TOTAL --}}
                            <td class="total-column">
                                Rp {{ number_format($inv->total, 0, ',', '.') }}
                            </td>
                            {{-- REMAINING --}}
                            <td class="total-column">
                                Rp {{ number_format($inv->remaining_amount, 0, ',', '.') }}
                            </td>
                            {{-- PENAMBAHAN VALUE KOLOM TAX ATTACHMENT INDIKATOR --}}
                            <td class="text-center">
                                @if(!empty($inv->tax_invoice_attachment))
                                    <span class="badge bg-success" title="Tax Invoice Attached">
                                        <i class="bi bi-paperclip fs-6"></i>
                                    </span>
                                @else
                                    <span class="text-muted" title="No Attachment">—</span>
                                @endif
                            </td>
                            {{-- TYPE --}}
                            <td class="text-center">
                                @if($inv->type == 'proforma')
                                    <span class="badge bg-secondary">
                                        Proforma
                                    </span>
                                @else
                                    <span class="badge bg-primary">
                                        Sales Invoice
                                    </span>
                                @endif
                            </td>
                            {{-- STATUS --}}
                            <td class="text-center">
                                @switch($inv->status)
                                    @case('proforma')
                                        <span class="badge bg-secondary">
                                            Proforma
                                        </span>
                                        @break
                                    @case('partial')
                                        <span class="badge bg-warning text-dark">
                                            Partial
                                        </span>
                                        @break
                                    @case('completed')
                                        <span class="badge bg-success">
                                            Completed
                                        </span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-danger">
                                            Cancelled
                                        </span>
                                        @break
                                    @default
                                        <span class="badge bg-dark">
                                            Unknown
                                        </span>
                                @endswitch
                            </td>
                            {{-- PAYMENT STATUS --}}
                            <td class="text-center">
                                @if($inv->payment_status == 'sent')
                                    <span class="badge bg-secondary">
                                        Sent
                                    </span>
                                @elseif($inv->payment_status == 'paid')
                                    <span class="badge bg-primary">
                                        Paid
                                    </span>
                                @else
                                    <span class="badge bg-dark">
                                        -
                                    </span>
                                @endif
                            </td>
                            {{-- ACTIONS --}}
                            <td class="text-center action-buttons">
                                {{-- VIEW --}}
                                <a href="{{ route('invoice-customers.show', $inv->id) }}"
                                   class="btn btn-info btn-sm"
                                   title="View">
                                   <i class="bi bi-eye"></i>
                                </a>
                                {{-- EDIT --}}
                                @if($inv->status != 'cancelled')
                                    <a href="{{ route('invoice-customers.edit', $inv->id) }}"
                                       class="btn btn-warning btn-sm"
                                       title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                                {{-- CANCEL --}}
                                @if(
                                    $inv->status != 'cancelled' &&
                                    $inv->payment_status != 'paid'
                                )
                                    <form action="{{ route('invoice-customers.cancel', $inv->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure want to cancel this invoice?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-secondary btn-sm"
                                                title="Cancel">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                @endif
                                {{-- DELETE --}}
                                @if(
                                    $inv->status == 'cancelled' &&
                                    $inv->payment_status != 'paid'
                                )
                                    <form action="{{ route('invoice-customers.destroy', $inv->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this invoice permanently?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted py-4">
                                There is no invoice data yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- PAGINATION --}}
        <div class="d-flex justify-content-end mt-3">
            {{ $invoices->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection