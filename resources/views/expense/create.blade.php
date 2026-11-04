@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-expense-create bgs-mode-create')
@section('title', 'Add New Expense')

@section('content')


<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
            <h4><i class="bi bi-plus-circle-fill me-2"></i>Add New Expense</h4>
            <a href="{{ route('expense.index') }}" class="btn btn-light btn-sm fw-bold px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>

        <div class="card-body p-4">
            {{-- Error & Success Messages --}}
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm d-flex fade show mb-4 bgs-u-e172afc8903e">
                    <div class="me-2"><i class="bi bi-exclamation-triangle-fill fs-5"></i></div>
                    <div>
                        <strong class="d-block mb-1">Please fix the following validation errors:</strong>
                        <ul class="mb-0 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4 bgs-u-be237583235b">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4 bgs-u-f812d16479e9">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                </div>
            @endif

            <form action="{{ route('expense.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Bagian 1: Informasi Utama (2 kolom) --}}
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="info-group-box">
                            <div class="section-sub-title"><i class="bi bi-tag-fill me-2"></i>1. Expense Details</div>

                            <div class="mb-3">
                                <label for="project_name" class="form-label">Project Name</label>
                                <input type="text" class="form-control @error('project_name') is-invalid @enderror" 
                                       id="project_name" name="project_name" value="{{ old('project_name') }}" placeholder="e.g. Project Alpha">
                                @error('project_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="expense_no" class="form-label">Expense Number</label>
                                <input type="text" class="form-control bgs-u-525c53445e47" id="expense_no"
                                    value="{{ $defaultExpenseNumber ?? '' }}" readonly>
                                <small class="text-muted">Auto-generated, will be finalized upon saving.</small>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="frequency" class="form-label">Frequency <span class="text-danger">*</span></label>
                                    <select class="form-select @error('frequency') is-invalid @enderror" id="frequency" name="frequency" required>
                                        <option value="">-- Select --</option>
                                        @foreach(\App\Models\Expense::frequencies() as $freq)
                                            <option value="{{ $freq }}" {{ old('frequency') == $freq ? 'selected' : '' }}>{{ $freq }}</option>
                                        @endforeach
                                    </select>
                                    @error('frequency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="expense_type" class="form-label">Expense Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('expense_type') is-invalid @enderror" id="expense_type" name="expense_type" required>
                                        <option value="">-- Select --</option>
                                        @foreach(\App\Models\Expense::expenseTypes() as $type)
                                            <option value="{{ $type }}" {{ old('expense_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                    @error('expense_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label for="amount" class="form-label">Amount (Rp) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" 
                                       id="amount" name="amount" value="{{ old('amount') }}" placeholder="0.00" required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-group-box">
                            <div class="section-sub-title"><i class="bi bi-calendar3-fill me-2"></i>2. Date & Period</div>

                            <div class="mb-3">
                                <label for="expense_date" class="form-label">Expense Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('expense_date') is-invalid @enderror" 
                                       id="expense_date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required>
                                @error('expense_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="expense_doc_no" class="form-label">Document Number</label>
                                <input type="text" class="form-control @error('expense_doc_no') is-invalid @enderror" 
                                    id="expense_doc_no" name="expense_doc_no" value="{{ old('expense_doc_no') }}" 
                                    placeholder="e.g. INV-2026-001">
                                @error('expense_doc_no')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="expense_doc_date" class="form-label">Document Date</label>
                                <input type="date" class="form-control @error('expense_doc_date') is-invalid @enderror" 
                                    id="expense_doc_date" name="expense_doc_date" value="{{ old('expense_doc_date') }}">
                                @error('expense_doc_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="period_month" class="form-label">Period Month (YYYY-MM)</label>
                                <input type="month" class="form-control @error('period_month') is-invalid @enderror" 
                                       id="period_month" name="period_month" value="{{ old('period_month', date('Y-m')) }}">
                                @error('period_month')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Leave empty if not applicable.</small>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="4" placeholder="Additional notes...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bagian 2: Attachment & Actions --}}
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="info-group-box">
                            <div class="section-sub-title"><i class="bi bi-paperclip me-2"></i>3. Attachment</div>
                            <div class="mb-3">
                                <label for="attachment" class="form-label">Upload File (max 2MB, jpg/png/pdf/doc)</label>
                                <input type="file" class="form-control @error('attachment') is-invalid @enderror" 
                                       id="attachment" name="attachment">
                                @error('attachment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="col-12 mt-4 pt-3 border-top d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
                        <i class="bi bi-save-fill me-1"></i> Save Expense
                    </button>
                    <a href="{{ route('expense.index') }}" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bootstrap Icons (jika belum ada) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endsection