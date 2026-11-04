@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-customers-index bgs-mode-index')
@section('title', 'Customer Management')
@section('content')


<div class="card main-card">
    <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
        <h4>
            <i class="bi bi-file-earmark-text"></i>
            Customer Management
        </h4>
        <a href="{{ route('customers.create') }}"
           class="btn btn-light-custom btn-sm">
            <i class="bi bi-plus-circle"></i>
            Add New Customer
        </a>
    </div>

    <div class="card-body p-4">
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
            <div class="alert alert-danger border-0 shadow-sm mb-4 bgs-u-f812d16479e9">
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
        <div class="table-responsive quotation-table-wrap">
            <table class="table table-modern table-hover align-middle quotation-table bgs-index-table bgs-actions-compact">
                <thead>
                    <tr>
                        <th width="4%" class="text-center">No</th>
                        <th class="text-center col-action">Actions</th>
                        <th width="15%" class="text-center">Company Name</th>
                        <th width="10%" class="text-center">PIC</th>
                        <th width="10%" class="text-center">Phone</th>
                        <th width="20%" class="text-center">Shipping Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $index => $customer)
                        <tr>
                            {{-- NO --}}
                            <td class="text-center">
                                {{ $customers->firstItem() + $index }}
                            </td>
                        <td class="text-center col-action">
                            <div class="d-flex justify-content-center action-btn-group">
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
                                                        </div>
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