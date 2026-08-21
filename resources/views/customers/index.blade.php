@extends('layouts.app')
@section('title', 'Customer Management')
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
    /* Menghapus custom CSS pagination & button global yang merusak Bootstrap */
</style>

<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4>
            <i class="bi bi-file-earmark-text"></i>
            Customer Management
        </h4>
        <a href="{{ route('customers.create') }}"
           class="btn btn-light btn-sm">
            <i class="bi bi-plus-circle"></i>
            Add New Customer
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
                  action="{{ route('customers.index') }}">
                <div class="row g-3">
                    {{-- SEARCH --}}
                    <div class="col-md-5">
                        <label class="filter-label">
                            Search
                        </label>

                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Customer Name"
                               value="{{ request('search') }}">
                    </div>

                    {{-- BUTTON --}}
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit"
                                class="btn btn-primary me-2 w-100">
                            <i class="bi bi-search"></i>
                            Search
                        </button>

                        <a href="{{ route('customers.index') }}"
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
                        <th width="4%" class="text-center">No</th>
                        <th width="15%" class="text-center">Company Name</th>
                        <th width="10%" class="text-center">PIC</th>
                        <th width="10%" class="text-center">Phone</th>
                        <th width="20%" class="text-center">Shipping Address</th>
                        <th width="7%" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $index => $customer)
                        <tr>
                            {{-- NO --}}
                            <td class="text-center">
                                {{ $customers->firstItem() + $index }}
                            </td>
                            {{-- CUSTOMER --}}
                            <td>
                                <div class="customer-name">
                                    {{ $customer->name ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <div>{{ $customer->pic_quotation_name ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $customer->pic_quotation_phone ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{!! nl2br(e($customer->shipping_address ?? '-')) !!}</div>
                            </td>                           
                            
                            {{-- ACTION --}}
                            <td class="text-center action-btn">
                                <a href="{{ route('customers.show', $customer->id) }}"
                                   class="btn btn-info btn-sm"
                                   title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('customers.edit', $customer->id) }}"
                                   class="btn btn-warning btn-sm"
                                   title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('customers.destroy', $customer->id) }}"
                                      method="POST"
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            title="Delete"
                                            onclick="return confirm('Are you sure you want to delete this Customer?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            {{-- Diubah menjadi colspan 9 agar pas dengan jumlah kolom di <thead> --}}
                            <td colspan="9" class="text-center empty-state">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                There is no Customer data available.
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
                {{ $customers->firstItem() ?? 0 }}
                to
                {{ $customers->lastItem() ?? 0 }}
                of
                {{ $customers->total() }}
                entries
            </div>
            <div>
                {{-- Pagination bawaan Laravel murni berbasis Bootstrap --}}
                {{ $customers->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection