@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-expense-index bgs-mode-index')
@section('title', 'Expense Management')
@section('content')

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
            <h4>
                <i class="bi bi-file-earmark-text-fill me-2"></i>
                Expense Management
            </h4>
            <a href="{{ route('expense.create') }}" class="btn btn-light-custom btn-sm px-3 py-2">
                <i class="bi bi-plus-circle-fill me-1"></i>
                Add New Expense
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
                <form method="GET" action="{{ route('expense.index') }}">
                    <div class="row g-3">
                        {{-- INPUT PENCARIAN --}}
                        <div class="col-md-4">
                            <label class="filter-label">Search Keyword</label>
                            <input type="text"
                                   name="search"
                                   class="form-control py-2"
                                   placeholder="Type Project..."
                                   value="{{ request('search') }}">
                        </div>
                        
                        {{-- Frequency FILTER --}}
                        <div class="col-md-3">
                            <label class="filter-label">Frequency</label>
                            <select name="frequency" class="form-select py-2">
                                <option value="">All Frequency</option>
                                <option value="Daily" {{ request('frequency') == 'Daily' ? 'selected' : '' }}>Daily</option>
                                <option value="Weekly" {{ request('frequency') == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="Monthly" {{ request('frequency') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="Quaterly" {{ request('frequency') == 'Quaterly' ? 'selected' : '' }}>Quaterly</option>
                                <option value="Semesterly" {{ request('frequency') == 'Semesterly' ? 'selected' : '' }}>Semesterly</option>
                                <option value="Annual" {{ request('frequency') == 'Annual' ? 'selected' : '' }}>Annual</option>
                                <option value="One Time" {{ request('frequency') == 'One-Time' ? 'selected' : '' }}>One Time</option>
                            </select>
                        </div>

                        {{-- EXPENSE TYPE FILTER --}}
                        <div class="col-md-3">
                            <label class="filter-label">Expense Type</label>
                            <select name="expense_type" class="form-select py-2">
                                <option value="">All Expense Type</option>
                                <option value="Operational" {{ request('invoice_status') == 'Operational' ? 'selected' : '' }}>Operational</option>
                                <option value="Project" {{ request('invoice_status') == 'Project' ? 'selected' : '' }}>Project</option>
                                <option value="Subscription" {{ request('invoice_status') == 'Subscription' ? 'selected' : '' }}>Subscription</option>
                                <option value="Asset" {{ request('invoice_status') == 'Asset' ? 'selected' : '' }}>Asset</option>
                            </select>
                        </div>

                        {{-- TOMBOL SUBMIT --}}
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary py-2 w-100 fw-bold bgs-u-40db451c03f9">
                                <i class="bi bi-search me-1"></i> Filter
                            </button>
                            <a href="{{ route('expense.index') }}" class="btn btn-outline-secondary py-2 px-3" title="Reset Filters">
                                <i class="bi bi-arrow-repeat"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- TABEL DATA --}}
            <div class="border rounded-3 overflow-hidden shadow-sm mb-3">
                <div class="table-responsive quotation-table-wrap">
                    <table class="table table-modern align-middle m-0 quotation-table bgs-index-table bgs-actions-compact">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th class="text-center col-action">Actions</th>
                                <th class="text-center">Expense No</th>
                                <th class="text-center">Expense Type</th>
                                <th class="text-center">Expense Doc No</th>
                                <th class="text-center">Expense Doc Date</th>
                                <th class="text-end">Period</th>
                                <th class="text-end">Project</th>
                                <th class="text-end">Frequency</th>
                                <th class="text-center">Expense Type</th>
                                <th class="text-center">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expense as $index => $exp)
                                <tr>
                                    {{-- NOMOR URUT --}}
                                    <td class="text-center text-muted fw-bold">
                                        {{ $expense->firstItem() + $index }}
                                    </td>
                                    <td class="text-center col-action">
                                        <div class="d-flex justify-content-center action-btn-group">
                                            <a href="{{ route('expense.show', $exp->id) }}"
                                               class="btn btn-info btn-sm text-white bgs-u-7dfad3480fc0"
                                               title="View Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('expense.edit', $exp->id) }}"
                                               class="btn btn-warning btn-sm text-dark"
                                               title="Edit Data">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('expense.destroy', $exp->id) }}"
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
                                    
                                    <td>
                                        <div class="expense_no">
                                            {{ $exp->expense_no ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="expense_date">
                                            {{ $exp->expense_date ? date('d/m/Y', strtotime($exp->expense_date)) : '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="expense_doc_no">
                                            {{ $exp->expense_doc_no ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="expense_doc_date">
                                            {{ $exp->expense_doc_date ? date('d/m/Y', strtotime($exp->expense_doc_date)) : '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="period_month">
                                            {{ $exp->period_month ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="project_name">
                                            {{ $exp->project_name ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="frequency">
                                            {{ $exp->frequency ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="expense_type">
                                            {{ $exp->expense_type ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="amount">
                                            Rp {{ number_format($exp->amount ?? 0, 0, ',', '.') }}
                                        </div>
                                    </td>

                                    {{-- ACTION BUTTONS --}}
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center empty-state">
                                        <i class="bi bi-inbox-fill fs-2 d-block mb-2 text-muted"></i>
                                        There is no Expense data available.
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
                    Showing {{ $expense->firstItem() ?? 0 }} to {{ $expense->lastItem() ?? 0 }} of {{ $expense->total() }} entries
                </div>
                <div>
                    {{ $expense->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection