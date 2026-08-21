@extends('layouts.app')
@section('title', 'Detail Delivery Order')
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
                <table class="table table-bordered table-detail">
                    <tr>
                        <th>DO #</th>
                        <td>
                            {{ $do->do_number }}
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
                            {{ $do->shipping_address }}
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
                            @if($do->status == 'pending') <span class="badge bg-warning">Pending</span>
                            @elseif($do->status == 'shipped') <span class="badge bg-primary">Shipped</span>
                            @else <span class="badge bg-success">Delivered</span> @endif
                        </span>
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
                            {{ $do->poCustomer->customer->pic_quotation_phone ?? '-' }}
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
            <table class="table table-bordered table-hover table-product">
                <thead class="table-dark">
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
    </div>
</div>
@endsection