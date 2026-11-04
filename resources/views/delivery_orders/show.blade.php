@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-delivery-orders-show bgs-mode-screen')
@section('title', 'Detail Delivery Order')
@section('content')

<div class="card shadow-sm border-0">
    {{-- HEADER --}}
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center bgs-page-header">
        <h4>
            <i class="bi bi-file-earmark-text"></i>
            Detail Delivery Order
        </h4>
        <div>
            <a href="{{ route('delivery-orders.index') }}"
               class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i>
                Back
            </a>
            <a href="{{ route('delivery-orders.edit', $do->id) }}"
               class="btn btn-warning btn-sm">
                <i class="bi bi-pencil"></i>
                Edit
            </a>
            <a href="{{ route('delivery-orders.print', $do->id) }}"
               class="btn btn-primary btn-sm"
               target="_blank">
                <i class="bi bi-printer"></i> Print DO
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
                        <th>DO #</th>
                        <td>
                            {{ $do->do_number }}
                        </td>
                    </tr>
                    <tr>
                        <th>PO Customer #</th>
                        <td>
                            {{ $do->poCustomer->po_number ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>DO Date</th>
                        <td>
                            {{ $do->delivery_date }}
                        </td>
                    </tr>
                    <tr>
                        <th>Shipping Address</th>
                        <td>
                            {!! nl2br(e($do->shipping_address ?? '-')) !!}
                        </td>
                    </tr>
                    <tr>
                        <th>Receiver Name</th>
                        <td>
                            {{ $do->receiver_name ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Delivery by</th>
                        <td>
                            {{ $do->delivery_by }}
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($do->status == 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($do->status == 'delivered')
                                <span class="badge bg-success">Delivered</span>
                            @else
                                <span class="badge bg-secondary">Legacy: {{ ucfirst($do->status) }}</span>
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
                            {{ $do->poCustomer->customer->name }}
                        </td>
                    </tr>
                    <tr>
                        <th>PIC Customer</th>
                        <td>
                            {{ $do->poCustomer->customer->pic_do_name ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>PIC Phone</th>
                        <td>
                            {{ $do->poCustomer->customer->pic_do_phone ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Customer Address</th>
                        <td>
                            {!! nl2br(e($do->poCustomer->customer->address ?? '-')) !!}
                        </td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>
                            {{ $do->poCustomer->customer->email ?? '-' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        {{-- PRODUCT DETAIL --}}
        <hr>
        <div class="section-title">
            Product Detail
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-product bgs-detail-table align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">
                            No
                        </th>
                        <th width="8%" class="text-center">
                            Brand
                        </th>
                        <th width="10%" class="text-center">
                            Product Code
                        </th>
                        <th width="10%" class="text-center">
                            Customer Code
                        </th>
                        <th width="12%" class="text-center">
                            Product Name
                        </th>
                        <th width="12%" class="text-center">
                            Customer Product Name
                        </th>
                        <th width="8%" class="text-center">
                            Qty
                        </th>
                        <th width="8%" class="text-center">
                            Unit
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($do->details as $index => $detail)
                        <tr>
                            <td class="text-center">
                                {{ $index + 1 }}
                            </td>
                            <td>
                                {{ $detail->product->brand ?? '-' }}
                            </td>
                            <td>
                                {{ $detail->product->product_code ?? '-' }}
                            </td>
                            <td>
                                {{ $detail->product->product_code2 ?? '-' }}
                            </td>
                            <td>
                                {{ $detail->product->name ?? '-' }}
                            </td>
                            <td>
                                {{ $detail->product->name2 ?? '-' }}
                            </td>
                            <td class="text-center">
                                {{ number_format($detail->quantity, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                {{ $detail->product->unit ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10"
                                class="text-center text-muted py-4">
                                No product detail available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ADDITIONAL INFORMATION --}}
        <hr>

        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="section-title">
                    Attachment
                </div>

                @if($do->attachment)
                    <a href="{{ Storage::url($do->attachment) }}"
                       target="_blank"
                       class="btn btn-info btn-sm">
                        <i class="bi bi-paperclip"></i>
                        View Attachment
                    </a>
                @else
                    <div class="empty-text">
                        No attachment available.
                    </div>
                @endif
            </div>

            <div class="col-md-6 mb-3">
                <div class="section-title">
                    Remarks
                </div>

                <div class="notes-box">
                    @if(!empty($do->notes))
                        {!! nl2br(e($do->notes)) !!}
                    @else
                        <span class="empty-text">
                            No remarks.
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection