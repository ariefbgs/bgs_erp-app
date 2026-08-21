@extends('layouts.app')
@section('title', 'Edit Expense')

@section('content')
<style>
    body { background-color: #f1f5f9; }
    .main-card { border: none; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: #fff; overflow: hidden; }
    .card-header-custom { background: linear-gradient(135deg, #10b981, #047857); color: white; padding: 1.5rem; border: none; }
    .card-header-custom h4 { color: white; font-weight: 700; margin-bottom: 0; }
    .form-label { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 700; margin-bottom: 6px; }
    .section-sub-title { font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px; color: #1e293b; font-weight: 700; margin-bottom: 1.25rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; }
    .section-sub-title i { color: #10b981; }
    .info-group-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; height: 100%; }
    .form-control, .form-select { border-color: #cbd5e1; padding: 0.5rem 0.75rem; }
    .form-control:focus, .form-select:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.15); }
    .btn-save { background: linear-gradient(135deg, #10b981, #047857); border: none; color: white; }
    .btn-save:hover { background: linear-gradient(135deg, #059669, #065f46); color: white; }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h4><i class="bi bi-pencil-square me-2"></i>Edit Expense</h4>
            <a href="{{ route('expense.index') }}" class="btn btn-light btn-sm fw-bold px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>

        <div class="card-body p-4">
            {{-- Error & Success Messages --}}
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm d-flex fade show mb-4" style="background-color:#fef2f2; color:#991b1b; border-left:4px solid #dc2626!important; border-radius:6px;">
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
                <div class="alert alert-success border-0 shadow-sm mb-4" style="background-color: #f0fdf4; color: #15803d; border-left: 4px solid #16a34a !important;">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4" style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                </div>
            @endif

            <form action="{{ route('expense.update', $expense) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Bagian 1: Informasi Utama (2 kolom) --}}
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="info-group-box">
                            <div class="section-sub-title"><i class="bi bi-tag-fill me-2"></i>1. Expense Details</div>

                            <div class="mb-3">
                                <label for="project_name" class="form-label">Project Name</label>
                                <input type="text" class="form-control @error('project_name') is-invalid @enderror" 
                                       id="project_name" name="project_name" value="{{ old('project_name', $expense->project_name ?? '') }}" placeholder="e.g. Project Alpha">
                                @error('project_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="frequency" class="form-label">Frequency <span class="text-danger">*</span></label>
                                    <select class="form-select @error('frequency') is-invalid @enderror" id="frequency" name="frequency" required>
                                        <option value="">-- Select --</option>
                                        @foreach(\App\Models\Expense::frequencies() as $freq)
                                            <option value="{{ $freq }}" {{ old('frequency', $expense->frequency ?? '') == $freq ? 'selected' : '' }}>{{ $freq }}</option>
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
                                            <option value="{{ $type }}" {{ old('expense_type', $expense->expense_type ?? '') == $type ? 'selected' : '' }}>{{ $type }}</option>
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
                                       id="amount" name="amount" value="{{ old('amount', $expense->amount ?? '') }}" placeholder="0.00" required>
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
                                       id="expense_date" name="expense_date" value="{{ old('expense_date', isset($expense->expense_date) ? $expense->expense_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                                @error('expense_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="expense_doc_no" class="form-label">Document Number</label>
                                <input type="text" class="form-control @error('expense_doc_no') is-invalid @enderror" 
                                    id="expense_doc_no" name="expense_doc_no" value="{{ old('expense_doc_no', $expense->expense_doc_no) }}" 
                                    placeholder="e.g. INV-2026-001">
                                @error('expense_doc_no')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="expense_doc_date" class="form-label">Document Date</label>
                                <input type="date" class="form-control @error('expense_doc_date') is-invalid @enderror" 
                                    id="expense_doc_date" name="expense_doc_date" value="{{ old('expense_doc_date', optional($expense->expense_doc_date)->format('Y-m-d')) }}">
                                @error('expense_doc_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="period_month" class="form-label">Period Month (YYYY-MM)</label>
                                <input type="month" class="form-control @error('period_month') is-invalid @enderror" 
                                       id="period_month" name="period_month" value="{{ old('period_month', $expense->period_month ?? '') }}">
                                @error('period_month')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Leave empty if not applicable.</small>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="4" placeholder="Additional notes...">{{ old('description', $expense->description ?? '') }}</textarea>
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
                            @if($expense->attachment)
                                <div class="mb-2">
                                    <a href="{{ asset('storage/' . $expense->attachment) }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="bi bi-file-earmark-fill me-1"></i> Current File
                                    </a>
                                </div>
                            @endif
                            <div class="mb-3">
                                <label for="attachment" class="form-label">Upload New File (max 2MB, jpg/png/pdf/doc)</label>
                                <input type="file" class="form-control @error('attachment') is-invalid @enderror" 
                                       id="attachment" name="attachment">
                                @error('attachment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Leave empty to keep current file.</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="col-12 mt-4 pt-3 border-top d-flex gap-2">
                    <button type="submit" class="btn btn-save px-4 py-2 fw-bold text-white">
                        <i class="bi bi-save-fill me-1"></i> Update Expense
                    </button>
                    <a href="{{ route('expense.index') }}" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bootstrap Icons --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endsection