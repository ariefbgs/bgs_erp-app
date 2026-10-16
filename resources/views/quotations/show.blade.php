@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-quotations-show bgs-mode-screen')

@section('title', 'Detail Quotation')

@section('content')


<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header card-header-custom py-3 bgs-page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="page-title fw-bold fs-5">
                    <i class="bi bi-file-earmark-text-fill me-2"></i> Detail Quotation
                </div>
                <div class="d-flex gap-2">
                    @if($quotation->status === 'draft')
<form action="{{ route('quotations.approve', $quotation->id) }}"
      method="POST"
      class="d-inline"
      onsubmit="return confirm('Approve this quotation?');">
    @csrf
    <button type="submit" class="btn btn-success">
        <i class="bi bi-check-circle"></i> Approve
    </button>
</form>
@endif
<a href="{{ route('quotations.print', $quotation->id) }}" 
                        id="btn-print-quotation"
                        target="_blank" 
                        class="btn btn-light btn-sm fw-bold px-3">
                            <i class="bi bi-printer-fill me-1"></i> Print Quotation
                    </a>
                    <a href="{{ route('quotations.edit', $quotation->id) }}" class="btn btn-warning btn-sm fw-bold px-3 text-dark shadow-sm">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </a>
                    <a href="{{ route('quotations.index') }}" class="btn btn-outline-light btn-sm fw-bold px-3">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card-body p-4">
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="h-100 p-3 rounded-3 info-box-bg">
                        <div class="section-sub-title"><i class="bi bi-file-earmark-text-fill me-2"></i>Quotation Info</div>
                        <table class="table info-table bgs-detail-table align-middle">
                            <tr>
                                <td class="label">Quotation #</td>
                                <td class="value">: {{ $quotation->quotation_number }}</td>
                            </tr>
                            <tr>
                                <td class="label">Date</td>
                                <td class="value">: {{ date('d M Y', strtotime($quotation->date)) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Valid Until</td>
                                <td class="value">: {!! $quotation->valid_until ? date('d M Y', strtotime($quotation->valid_until)) : '<span class="text-muted">N/A</span>' !!}</td>
                            </tr>
                            <tr>
                                <td class="label">Payment Terms</td>
                                <td class="value">: {{ $quotation->payment_terms ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Delivery Time</td>
                                <td class="value">: {{ $quotation->delivery_time ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Status</td>
                                <td class="value" id="status-badge-container">: 
                                    @if($quotation->status == 'draft')
                                        <span class="badge bg-secondary badge-custom" id="current-status-badge">Draft</span>
                                    @elseif($quotation->status == 'sent')
                                        <span class="badge bg-primary badge-custom">Sent</span>
                                    @elseif($quotation->status == 'approved')
                                        <span class="badge bg-success badge-custom">Approved</span>
                                    @else
                                        <span class="badge bg-danger badge-custom">Expired</span>
                                    @endif
                                </td>
                            </tr>
                            {{-- PENEMPATAN READ-ONLY INDICATOR --}}
                            <tr>
                                <td class="label">Print Images?</td>
                                <td class="value">: 
                                    @if($quotation->show_image_on_print)
                                        <span class="text-success fw-bold small">
                                            <i class="bi bi-check-circle-fill me-1"></i> Yes, Display Images
                                        </span>
                                    @else
                                        <span class="text-secondary fw-bold small">
                                            <i class="bi bi-x-circle-fill me-1"></i> No, Hide Images
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="h-100 p-3 rounded-3 info-box-bg">
                        <div class="section-sub-title"><i class="bi bi-building-fill me-2"></i>Customer Info</div>
                        <table class="table info-table bgs-detail-table align-middle">
                            <tr>
                                <td class="label">Company Name</td>
                                <td class="value fw-bold text-dark">: {{ $quotation->customer->name }}</td>
                            </tr>
                            <tr>
                                <td class="label">Address</td>
                                <td class="value">: {!! nl2br(e($quotation->customer->address ?? '-')) !!}</td>
                            </tr>
                            <tr>
                                <td class="label">Contact</td>
                                <td class="value">: {{ $quotation->customer->phone ?? '-' }} / {{ $quotation->customer->email ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">NPWP / Tax ID</td>
                                <td class="value">: {{ $quotation->customer->tax_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">PIC Attention</td>
                                <td class="value">: <span class="text-primary fw-semibold fs-6">{{ $quotation->customer->pic_quotation_name ?? '-' }}</span><br><span class="text-muted small ms-2 fs-6">{{ $quotation->customer->pic_quotation_phone ?? '-' }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="mb-5">
                <div class="section-sub-title"><i class="bi bi-box-seam-fill me-2"></i>Line Items / Product Details</div>
                <div class="border rounded-3 overflow-hidden shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-modern align-middle m-0 bgs-detail-table">
                            <thead>
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th width="12%">Brand</th>
                                    <th width="38%">Product Name & Details</th>
                                    <th class="text-center" width="6%">Qty</th>
                                    <th class="text-center" width="6%">Unit</th>
                                    <th class="text-end" width="14%">Price</th>
                                    <th class="text-end" width="14%">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($quotation->details as $index => $detail)
                                <tr>
                                    <td class="text-center text-muted fw-bold">{{ $index + 1 }}</td>
                                    <td><span class="badge bg-white text-dark border px-2 py-1 fs-6">{{ $detail->product->brand ?? '-' }}</span></td>
                                    <td>
                                        <div class="product-name-title">{{ $detail->product->name ?? '-' }}</div>
                                        
                                        @if($detail->specification)
                                            <div class="meta-box meta-box-spec">
                                                <div class="meta-label"><i class="bi bi-gear-fill me-1"></i> Specification</div>
                                                <div class="meta-value">{{ $detail->specification }}</div>
                                            </div>
                                        @endif
                                        
                                        @if($detail->description)
                                            <div class="meta-box meta-box-desc">
                                                <div class="meta-label meta-label-desc"><i class="bi bi-info-circle-fill me-1"></i> Description / Remarks</div>
                                                <div class="meta-value">{{ $detail->description }}</div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold fs-6">{{ number_format($detail->quantity, 0, ',', '.') }}</td>
                                    <td class="text-center text-muted fs-6">{{ $detail->product->unit }}</td>
                                    <td class="text-end fs-6">Rp {{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-dark fs-6">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="row g-4 text-start mt-2">
                <div class="col-md-7">
                    @if($quotation->notes)
                        <div class="p-3 border rounded-3 bg-white h-100 info-box-bg">
                            <div class="section-sub-title mb-2"><i class="bi bi-pencil-square me-2"></i>Terms & Remarks</div>
                            <p class="text-secondary small mb-0 bgs-u-1d172bf7f784">{{ $quotation->notes }}</p>
                        </div>
                    @endif
                </div>
                
                {{-- REVISI: FULL BREAKDOWN SUMMARY CARD --}}
                <div class="col-md-5">
                    <div class="summary-card">
                        {{-- 1. Subtotal Gross --}}
                        <div class="summary-line">
                            <span class="main-label">Subtotal Item Gross</span>
                            <span class="fw-bold">Rp {{ number_format($quotation->subtotal, 0, ',', '.') }}</span>
                        </div>
                        
                        {{-- 2. Potongan Diskon --}}
                        <div class="summary-line">
                            <span class="main-label">Discount ({{ number_format($quotation->discount_percent, 2, ',', '.') }}%)</span>
                            <span class="text-warning fw-bold">- Rp {{ number_format($quotation->discount_amount, 0, ',', '.') }}</span>
                        </div>
                        
                        {{-- 3. DPP (Subtotal After Discount) --}}
                        <div class="summary-line bgs-u-69936becd9ee">
                            <span class="main-label">Subtotal After Discount (DPP)</span>
                            <span class="fw-semibold text-white">Rp {{ number_format($quotation->dpp, 0, ',', '.') }}</span>
                        </div>
                        
                        {{-- 4. Pajak PPN --}}
                        <div class="summary-line">
                            <span class="main-label text-info">VAT / PPN ({{ number_format($quotation->tax_percent, 2, ',', '.') }}%)</span>
                            <span class="text-info fw-bold">+ Rp {{ number_format($quotation->tax_amount, 0, ',', '.') }}</span>
                        </div>
                        
                        {{-- 5. Pajak PPh 23 --}}
                        <div class="summary-line">
                            <span class="main-label text-danger">PPh Income Tax 23 ({{ number_format($quotation->pph23_percent, 2, ',', '.') }}%)</span>
                            <span class="text-danger fw-bold">- Rp {{ number_format($quotation->pph23_amount, 0, ',', '.') }}</span>
                        </div>
                        
                        {{-- 6. Grand Total --}}
                        <div class="summary-line grand-total">
                            <span>Grand Total Due</span>
                            <span class="total-rp">Rp {{ number_format($quotation->total, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>


@endsection