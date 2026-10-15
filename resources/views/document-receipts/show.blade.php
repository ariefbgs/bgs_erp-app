@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-document-receipts-show bgs-mode-screen')
@section('title', 'Detail Document Receipt')
@section('content')

<div class="card shadow-sm border-0">
    {{-- HEADER --}}
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center bgs-page-header">
        <h4>
            <i class="bi bi-file-earmark-text"></i>
            Detail Document Receipt
        </h4>
        <div>
            <a href="{{ route('document-receipts.index') }}"
               class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i>
                Back
            </a>
            <a href="{{ route('document-receipts.edit', $receipt->id) }}"
               class="btn btn-warning btn-sm">
                <i class="bi bi-pencil"></i>
                Edit
            </a>
            <a href="{{ route('document-receipts.print', $receipt->id) }}"
               class="btn btn-primary btn-sm"
               target="_blank">
                <i class="bi bi-printer"></i> Print Receipt
            </a>
        </div> 
    </div>
    {{-- BODY --}}
    <div class="card-body">
        <div class="row">
            {{-- LEFT INFO --}}
            <div class="col-md-6 mb-3">
                <div class="section-title">
                    DO Information
                </div>
                <table class="table table-detail bgs-detail-table align-middle">
                    <tr>
                        <th>Receipt #</th>
                        <td>
                            {{ $receipt->receipt_number }}
                        </td>
                    </tr>
                    <tr>
                        <th>Receipt Date</th>
                        <td>
                            {{ $receipt->receipt_date }}
                        </td>
                    </tr>
                    <tr>
                        <th>DO #</th>
                        <td>
                            {{ $receipt->deliveryOrders->pluck('do_number')->filter()->implode(', ') ?: '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>DO Date</th>
                        <td>
                            {{ $receipt->deliveryOrders->pluck('delivery_date')->filter()->map(fn($v) => (string) $v)->unique()->implode(', ') ?: '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Shipping Address</th>
                        <td>
                            {{ $receipt->deliveryOrders->pluck('shipping_address')->filter()->unique()->implode(' | ') ?: '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Delivery by</th>
                        <td>
                            {{ $receipt->deliveryOrders->pluck('delivery_by')->filter()->unique()->implode(', ') ?: '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($receipt->status == 'draft')
                                <span class="badge bg-secondary">Draft</span>
                            @elseif($receipt->status == 'sent')
                                <span class="badge bg-primary">Sent</span>
                            @elseif($receipt->status == 'delivered')
                                <span class="badge bg-success">Delivered</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($receipt->status) }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            {{-- RIGHT INFO --}}
            <div class="col-md-6 mb-3">
                <div class="section-title">
                    Customer Information
                </div>
                <table class="table table-detail bgs-detail-table align-middle">
                    <tr>
                        <th>Customer</th>
                        <td>
                            {{ $receipt->poCustomer->customer->name ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>PIC Customer</th>
                        <td>
                            {{ $receipt->poCustomer->customer->pic_invoice_name ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>PIC Phone</th>
                        <td>
                            {{ $receipt->poCustomer->customer->pic_invoice_phone ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Customer Address</th>
                        <td>
                            {!! nl2br(e($receipt->poCustomer->customer->address ?? '-')) !!}
                        </td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>
                            {{ $receipt->poCustomer->customer->email ?? '-' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- TANDA TERIMA DOKUMEN (DIAMBIL DARI $receipt)              -->
        <!-- ========================================================= -->
        <hr>
            <h5 class="mb-3">Daftar Dokumen yang Dikirim</h5>
@include('document-receipts.document-list')
        </div>

        <!-- ========================================================= -->
        <!-- NOTES & ATTACHMENT (jika ada)                             -->
        <!-- ========================================================= -->
        @if(!empty($receipt->notes) || !empty($receipt->attachment))
        <hr>
        <div class="row">
            @if(!empty($receipt->notes))
            <div class="col-md-6">
                <div class="notes-box">
                    <strong><i class="bi bi-chat-left-text"></i> Remarks:</strong>
                    <p class="mb-0 mt-2">{{ $receipt->notes }}</p>
                </div>
            </div>
            @endif
            @if(!empty($receipt->attachment))
            <div class="col-md-6">
                <div class="notes-box bgs-u-951604446a6c">
                    <strong><i class="bi bi-paperclip"></i> Attachment:</strong>
                    <p class="mb-0 mt-2">
                        <a href="{{ asset('storage/' . $receipt->attachment) }}" target="_blank" class="btn btn-sm btn-success">
                            <i class="bi bi-eye"></i> View Attachment
                        </a>
                    </p>
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
