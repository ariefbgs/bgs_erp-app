@extends('layouts.app')
@section('title', 'Detail Document Receipt')
@section('content')
<style>
    .table th,
    .table td {
        vertical-align: middle;
    }
    .table-detail th {
        width: 35%;
        background: #f8f9fa;
        white-space: nowrap;
    }
    .table-product thead th {
        white-space: nowrap;
        font-size: 13px;
    }
    .text-right {
        text-align: right;
    }
    .amount-column {
        text-align: right;
        font-weight: 600;
        white-space: nowrap;
    }
    .card-header h4 {
        margin-bottom: 0;
    }
    .section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
    }
    .badge {
        font-size: 11px;
        padding: 6px 10px;
    }
    .summary-box {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 15px;
        margin-top: 20px;
    }
    .notes-box {
        background: #f8f9fa;
        border-left: 4px solid #0d6efd;
        padding: 15px;
        border-radius: 6px;
    }
    .empty-text {
        color: #6c757d;
    }
</style>
<div class="card shadow-sm border-0">
    {{-- HEADER --}}
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
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
                <table class="table table-bordered table-detail">
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
                            {{ $receipt->deliveryOrder->do_number ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>DO Date</th>
                        <td>
                            {{ $receipt->deliveryOrder->delivery_date ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Shipping Address</th>
                        <td>
                            {{ $receipt->deliveryOrder->shipping_address ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Delivery by</th>
                        <td>
                            {{ $receipt->deliveryOrder->delivery_by ?? '-' }}
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
                <table class="table table-bordered table-detail">
                    <tr>
                        <th>Customer</th>
                        <td>
                            {{ $receipt->deliveryOrder->poCustomer->customer->name ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>PIC Customer</th>
                        <td>
                            {{ $receipt->deliveryOrder->poCustomer->customer->pic_do_name ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>PIC Phone</th>
                        <td>
                            {{ $receipt->deliveryOrder->poCustomer->customer->pic_quotation_phone ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Customer Address</th>
                        <td>
                            {!! nl2br(e($receipt->deliveryOrder->poCustomer->customer->address ?? '-')) !!}
                        </td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>
                            {{ $receipt->deliveryOrder->poCustomer->customer->email ?? '-' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- TANDA TERIMA DOKUMEN (DIAMBIL DARI $receipt)              -->
        <!-- ========================================================= -->
        <hr>
        <h5 class="mb-3">Document Receipt</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th>Description</th>
                        <th width="25%">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Invoice - DIAMBIL DARI $receipt, BUKAN dari deliveryOrder -->
                    <tr>
                        <td class="text-center">1</td>
                        <td>
                            <strong>Invoice</strong><br>
                            {{ $receipt->invoice_number ?? '-' }}
                        </td>
                        <td class="align-middle">1 Rangkap (Asli)</td>
                    </tr>
                    <!-- Faktur Pajak - DIAMBIL DARI $receipt -->
                    <tr>
                        <td class="text-center">2</td>
                        <td>
                            <strong>Faktur Pajak</strong><br>
                            {{ $receipt->tax_invoice_number ?? '-' }}
                        </td>
                        <td class="align-middle">2 Rangkap (Asli + Copy)</td>
                    </tr>
                    <!-- PO -->
                    <tr>
                        <td class="text-center">3</td>
                        <td>
                            <strong>PO Customer</strong><br>
                            <span class="text-primary">{{ $receipt->deliveryOrder->poCustomer->po_number ?? '-' }}</span>
                        </td>
                        <td class="align-middle">1 Rangkap</td> 
                    </tr>
                    <!-- DO -->
                    <tr>
                        <td class="text-center">4</td>
                        <td>
                            <strong>Delivery Order</strong><br>
                            <span class="text-success">{{ $receipt->deliveryOrder->do_number ?? '-' }}</span>
                        </td>
                        <td class="align-middle">3 Rangkap (Asli + Copy)</td>
                    </tr>
                </tbody>
            </table>
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
                <div class="notes-box" style="border-left-color: #198754;">
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