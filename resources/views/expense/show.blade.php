@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-expense-show bgs-mode-screen')
@section('title', 'Expense Details')

@section('content')


<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
            <h4><i class="bi bi-eye-fill me-2"></i>Expense Details</h4>
            <div>
                <a href="{{ route('expense.edit', $expense) }}" class="btn btn-light btn-sm fw-bold px-3 me-2">
                    <i class="bi bi-pencil-fill me-1"></i> Edit
                </a>
                <a href="{{ route('expense.index') }}" class="btn btn-outline-light btn-sm fw-bold px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            {{-- Alert jika ada session --}}
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4 bgs-u-be237583235b">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                </div>
            @endif

            <div class="row g-4">
                {{-- Kolom Kiri: Informasi Utama --}}
                <div class="col-md-6">
                    <div class="info-group-box">
                        <h5 class="section-sub-title mb-3"><i class="bi bi-info-circle-fill me-2 text-success"></i>Expense Information</h5>
                        <dl class="row mb-0">
                            <dt class="col-sm-4 detail-label">ID</dt>
                            <dd class="col-sm-8 detail-value">#{{ $expense->id }}</dd>

                            <dt class="col-sm-4 detail-label">Project Name</dt>
                            <dd class="col-sm-8 detail-value">{{ $expense->project_name ?? '-' }}</dd>

                            <dt class="col-sm-4 detail-label">Frequency</dt>
                            <dd class="col-sm-8 detail-value">
                                @php
                                    $freqColors = [
                                        'Daily' => 'bg-info',
                                        'Weekly' => 'bg-primary',
                                        'Monthly' => 'bg-success',
                                        'Quarterly' => 'bg-warning',
                                        'Semester' => 'bg-secondary',
                                        'Yearly' => 'bg-danger',
                                    ];
                                @endphp
                                <span class="badge {{ $freqColors[$expense->frequency] ?? 'bg-secondary' }} badge-frequency">
                                    {{ $expense->frequency }}
                                </span>
                            </dd>

                            <dt class="col-sm-4 detail-label">Expense Type</dt>
                            <dd class="col-sm-8 detail-value">
                                @php
                                    $typeColors = [
                                        'Operational' => 'bg-primary',
                                        'Maintenance' => 'bg-warning',
                                        'Project' => 'bg-success',
                                        'Emergency' => 'bg-danger',
                                        'Other' => 'bg-secondary',
                                    ];
                                @endphp
                                <span class="badge {{ $typeColors[$expense->expense_type] ?? 'bg-secondary' }} badge-frequency">
                                    {{ $expense->expense_type }}
                                </span>
                            </dd>

                            <dt class="col-sm-4 detail-label">Amount</dt>
                            <dd class="col-sm-8 detail-value fw-bold text-success">Rp {{ number_format($expense->amount, 2) }}</dd>
                        </dl>
                    </div>
                </div>

                {{-- Kolom Kanan: Tanggal, Deskripsi, dll --}}
                <div class="col-md-6">
                    <div class="info-group-box">
                        <h5 class="section-sub-title mb-3"><i class="bi bi-calendar3-fill me-2 text-success"></i>Date & Metadata</h5>
                        <dl class="row mb-0">
                            <dt class="col-sm-4 detail-label">Expense Date</dt>
                            <dd class="col-sm-8 detail-value">{{ $expense->expense_date->format('d-m-Y') }}</dd>

                            <dt class="col-sm-4 detail-label">Document Number</dt>
                            <dd class="col-sm-8 detail-value">{{ $expense->expense_doc_no ?? '-' }}</dd>

                            <dt class="col-sm-4 detail-label">Document Date</dt>
                            <dd class="col-sm-8 detail-value">{{ $expense->expense_doc_date ? $expense->expense_doc_date->format('d-m-Y') : '-' }}</dd>

                            <dt class="col-sm-4 detail-label">Period Month</dt>
                            <dd class="col-sm-8 detail-value">{{ $expense->period_month ?? '-' }}</dd>

                            <dt class="col-sm-4 detail-label">Description</dt>
                            <dd class="col-sm-8 detail-value">{{ $expense->description ?? '-' }}</dd>

                            <dt class="col-sm-4 detail-label">Attachment</dt>
                            <dd class="col-sm-8 detail-value">
                                @if($expense->attachment && Storage::disk('public')->exists($expense->attachment))
                                    <div class="d-flex flex-column gap-2 align-items-start">
                                        <a href="{{ route('expenses.view-image', $expense->id) }}" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold py-1 px-2 shadow-sm">
                                            <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> View / Download
                                        </a>

                                        @php
                                            $extension = strtolower(pathinfo($expense->attachment, PATHINFO_EXTENSION));
                                        @endphp
                                        @if(in_array($extension, ['jpg', 'jpeg', 'png']))
                                            <div class="mt-2 border rounded p-1 bg-white shadow-sm bgs-u-e7cb1ab8aee6">
                                                <img src="{{ route('expenses.view-image', $expense->id) }}" alt="Attachment" class="img-fluid rounded bgs-u-8cebfdf8cd1c">
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Tambahan timestamp (bisa di bawah) --}}
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="info-group-box bgs-u-2736d29d284e">
                        <dl class="row mb-0 small">
                            <dt class="col-sm-2 text-muted">Created At</dt>
                            <dd class="col-sm-4">{{ $expense->created_at->format('d-m-Y H:i:s') }}</dd>
                            <dt class="col-sm-2 text-muted">Updated At</dt>
                            <dd class="col-sm-4">{{ $expense->updated_at->format('d-m-Y H:i:s') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Bootstrap Icons --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endsection