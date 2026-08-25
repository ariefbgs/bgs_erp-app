@extends('layouts.app')
@section('title', 'Detail PO Customer')
@section('content')
<style>
    body { background-color: #f1f5f9; }
    .main-card { border: none; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: #fff; overflow: hidden; }
    .card-header-custom { background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; padding: 1.5rem; border: none; }
    .card-header-custom h4 { color: white; font-weight: 700; margin-bottom: 0; }
    .section-sub-title { font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; color: #475569; font-weight: 700; margin-bottom: 1rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; display: inline-block; }
    .section-sub-title i { color: #0ea5e9; }
    .info-box-bg { background-color: #f8fafc; border: 1px solid #e2e8f0; }
    .info-table { margin-bottom: 0; }
    .info-table td { padding: 0.65rem 0.5rem; border: none; font-size: 0.92rem; vertical-align: top; }
    .info-table .label { font-weight: 600; color: #1e293b; width: 40%; }
    .info-table .value { color: #334155; }
    .table-modern thead th { background-color: #0369a1; color: #f0f9ff; font-weight: 600; text-transform: uppercase; font-size: 0.78rem; letter-spacing: 0.5px; padding: 14px; border: none; }
    .table-modern tbody tr:nth-child(even) { background-color: #fcfdfe; }
    .table-modern tbody td { padding: 1rem 0.75rem; vertical-align: middle; font-size: 0.9rem; color: #334155; border-color: #f1f5f9; }
    .product-name-title { font-weight: 600; color: #059669; font-size: 0.95rem; }
    .summary-card { background-color: #1e293b; border-radius: 8px; padding: 1.25rem; color: #f1f5f9; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .summary-line { display: flex; justify-content: space-between; padding: 0.5rem 0; font-size: 0.9rem; color: #cbd5e1; }
    .summary-line .main-label { font-weight: 500; }
    .summary-line.grand-total { 
        border-top: 2px solid #334155; 
        margin-top: 0.5rem; 
        padding-top: 1rem; 
        font-size: 1.2rem; 
        font-weight: 700; 
        color: #fff; }
    .grand-total .total-rp {
         color: #38bdf8;
        }
    .badge-custom { 
        padding: 0.5em 0.9em; 
        font-size: 0.75rem; 
        font-weight: 700; 
        border-radius: 20px; 
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
    }
    .btn-light { 
        background-color: rgba(255,255,255,0.2); 
        border: 1px solid rgba(255,255,255,0.3); 
        color: white; }
    .btn-light:hover {
         background-color: rgba(255,255,255,0.3); 
         border: 1px solid rgba(255,255,255,0.4); 
         color: white; 
    }
    .text-secondary {
    color: #334155 !important; /* Contoh: Mengubah ke Slate 700 (abu-abu yang lebih gelap & tajam) */
    font-family: Arial, Helvetica, sans-serif; /* Jika ingin menyamakan jenis huruf */
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header card-header-custom py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h4><i class="bi bi-file-earmark-text-fill me-2"></i> Detail PO Customer</h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('po-customers.edit', $poCustomer->id) }}" class="btn btn-warning btn-sm fw-bold px-3 text-dark shadow-sm"><i class="bi bi-pencil-square me-1"></i> Edit PO</a>
                    <a href="{{ route('po-customers.index') }}" class="btn btn-outline-light btn-sm fw-bold px-3"><i class="bi bi-arrow-left me-1"></i> Back</a>
                </div>
            </div>
        </div>
        
        <div class="card-body p-4">
            <div class="row g-4 mb-5">
                {{-- LEFT INFO: PO INFORMATION --}}
                <div class="col-md-6">
                    <div class="h-100 p-3 rounded-3 info-box-bg">
                        <div class="section-sub-title"><i class="bi bi-info-circle-fill me-2"></i>PO Information</div>
                        <table class="table info-table">
                            <tr><td class="label">PO #</td><td class="value fw-bold text-dark">: {{ $poCustomer->po_number }}</td></tr>
                            <tr><td class="label">Customer</td><td class="value">: {{ $poCustomer->customer->name ?? '-' }}</td></tr>
                            <tr><td class="label">PO Date</td><td class="value">: {{ $poCustomer->po_date ? date('d M Y', strtotime($poCustomer->po_date)) : '-' }}</td></tr>
                            <tr><td class="label">Delivery Date</td><td class="value">: {{ $poCustomer->delivery_date ? date('d M Y', strtotime($poCustomer->delivery_date)) : '-' }}</td></tr>
                            <tr><td class="label">Payment Terms</td><td class="value fw-semibold text-dark">: {{ $poCustomer->payment_terms ?? '-' }}</td></tr>
                            <tr><td class="label">Delivery Time</td><td class="value">: {{ $poCustomer->delivery_time ?? '-' }}</td></tr>
                            <tr><td class="label">PO Status</td><td class="value">: @switch(strtolower($poCustomer->status)) @case('received')<span class="badge bg-primary badge-custom">Received</span>@break @case('proceed')<span class="badge bg-warning text-dark badge-custom">Proceed</span>@break @case('delivered')<span class="badge bg-success badge-custom">Delivered</span>@break @case('cancelled')<span class="badge bg-danger badge-custom">Cancelled</span>@break @default<span class="badge bg-secondary badge-custom">{{ ucfirst($poCustomer->status) }}</span>@endswitch</td></tr>
                            <tr><td class="label">Invoice Status</td><td class="value">: @switch(strtolower($poCustomer->invoice_status)) @case('issue yet')<span class="badge bg-info text-dark badge-custom">Issue Yet</span>@break @case('partial')<span class="badge bg-warning text-dark badge-custom">Partial</span>@break @case('completed')<span class="badge bg-success badge-custom">Completed</span>@break @case('cancelled')<span class="badge bg-danger badge-custom">Cancelled</span>@break @default<span class="badge bg-secondary badge-custom">{{ ucfirst($poCustomer->invoice_status) }}</span>@endswitch</td></tr>
                            <tr>
                                <td class="label">Attachment</td>
                                <td class="value">: 
                                    @if($poCustomer->attachment)
                                        <div class="d-flex flex-column gap-2 align-items-start">
                                            <a href="{{ route('po-customers.view-image', $poCustomer->id) }}" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold py-1 px-2 shadow-sm">
                                                <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> View / Download
                                            </a>

                                            @if(in_array(strtolower(pathinfo($poCustomer->attachment, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']))
                                                <div class="mt-2 border rounded p-1 bg-white shadow-sm" style="max-width: 300px;">
                                                    <img src="{{ route('po-customers.view-image', $poCustomer->id) }}" alt="PO Attachment" class="img-fluid rounded" style="max-height: 200px; object-fit: contain;">
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">No attachment</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                {{-- RIGHT INFO: CUSTOMER DETAILS + SHIPPING ADDRESS --}}
                <div class="col-md-6">
                    <div class="h-100 p-3 rounded-3 info-box-bg">
                        <div class="section-sub-title"><i class="bi bi-building-fill me-2"></i>Customer Details & Shipping</div>
                        <table class="table info-table">
                            @if($poCustomer->quotation)
                            <tr><td class="label">Reference Quotation</td><td class="value fw-semibold text-primary">: {{ $poCustomer->quotation->quotation_number }}</td></tr>
                            @endif
                            <tr><td class="label">PIC Attention</td><td class="value fw-semibold text-dark">: {{ $poCustomer->customer->pic_quotation_name ?? '-' }}</td></tr>
                            <tr><td class="label">PIC Contact Phone</td><td class="value">: {{ $poCustomer->customer->pic_quotation_phone ?? '-' }}</td></tr>
                            <tr><td class="label">Email Address</td><td class="value">: {{ $poCustomer->customer->email ?? '-' }}</td></tr>
                            <tr><td class="label">Customer Address</td><td class="value">: <div class="d-inline-block text-secondary" style="white-space: pre-line; line-height: 1.4;">{!! nl2br(e($poCustomer->customer->address ?? '-')) !!}</div></td></tr>
                            <tr><td class="label">Shipping Address (PO)</td><td class="value">: <div class="d-inline-block text-secondary" style="white-space: pre-line; line-height: 1.4;">{!! nl2br(e($poCustomer->shipping_address ?? '-')) !!}</div></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- LINE ITEMS --}}
            <div class="mb-5">
                <div class="section-sub-title"><i class="bi bi-box-seam-fill me-2"></i>Product Specification Details</div>
                <div class="border rounded-3 overflow-hidden shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-modern align-middle m-0">
                            <thead>
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th class="text-center" width="15%">Brand</th>
                                    <th class="text-center" width="40%">Customer Product Name</th>
                                    <th class="text-center" width="8%">Qty</th>
                                    <th class="text-center" width="8%">Unit</th>
                                    <th class="text-end" width="12%">Price</th>
                                    <th class="text-end" width="12%">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($poCustomer->details as $index => $detail)
                                <tr>
                                    <td class="text-center text-secondary" style="font-size: 15px;">{{ $index+1 }}</td>
                                    <td><span class="text-center text-secondary" style="font-size: 15px;">{{ $detail->product->brand ?? '-' }}</span></td>
                                    <td><div class="text-center text-secondary" style="font-size: 15px;">{{ $detail->product->name2 ?? '-' }}</div></td>
                                    <td class="text-center text-secondary" style="font-size: 15px;">{{ number_format($detail->quantity,0,',','.') }}</td>
                                    <td class="text-center text-secondary" style="font-size: 15px;">{{ $detail->product->unit ?? '-' }}</td>
                                    <td class="text-end text-secondary" style="font-size: 15px;">Rp {{ number_format($detail->unit_price,0,',','.') }}</td>
                                    <td class="text-end fw-bold text-dark" style="font-size: 15px;">Rp {{ number_format($detail->subtotal,0,',','.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox text-muted fs-3 d-block mb-2"></i>No product detail available.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- REMARKS & FINANCIAL SUMMARY --}}
            <div class="row g-4 text-start mt-2">
                <div class="col-md-7">
                    @if(!empty($poCustomer->notes))
                    <div class="p-3 border rounded-3 bg-white h-100 info-box-bg">
                        <div class="section-sub-title mb-2"><i class="bi bi-pencil-square me-2"></i>Remarks & Internal Notes</div>
                        <p class="text-secondary" style="white-space: pre-line; line-height: 1.6;">{!! nl2br(e($poCustomer->notes)) !!}</p>
                    </div>
                    @endif
                </div>
                <div class="col-md-5">
                    <div class="summary-card">
                        <div class="summary-line"><span class="main-label">Subtotal</span><span class="fw-bold">Rp {{ number_format($poCustomer->subtotal ?? 0,0,',','.') }}</span></div>
                        <div class="summary-line"><span class="main-label">Discount ({{ $poCustomer->discount_percent ?? 0 }}%)</span><span class="text-warning fw-bold">- Rp {{ number_format($poCustomer->discount_amount ?? 0,0,',','.') }}</span></div>
                        @php $dppLain = ($poCustomer->subtotal ?? 0) - ($poCustomer->discount_amount ?? 0); @endphp
                        <div class="summary-line" style="background-color:rgba(255,255,255,0.05); border-radius:4px; padding:0.5rem 8px; margin:2px 0;"><span class="main-label text-info">DPP Nilai Lain</span><span class="text-info fw-bold">Rp {{ number_format($dppLain,0,',','.') }}</span></div>
                        <div class="summary-line"><span class="main-label">PPN ({{ $poCustomer->tax_percent ?? 0 }}%)</span><span class="fw-bold">+ Rp {{ number_format($poCustomer->tax_amount ?? 0,0,',','.') }}</span></div>
                        <div class="summary-line"><span class="main-label">PPh 23 ({{ $poCustomer->pph_percent ?? 0 }}%)</span><span class="text-danger fw-bold">- Rp {{ number_format($poCustomer->pph_amount ?? 0,0,',','.') }}</span></div>
                        <div class="summary-line grand-total"><span>Grand Total Invoice</span><span class="total-rp">Rp {{ number_format($poCustomer->total ?? 0,0,',','.') }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection