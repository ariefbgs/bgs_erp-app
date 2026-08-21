@extends('layouts.app')
@section('title', 'Detail Sales Invoice')
@section('content')
<style>
    .table td,
    .table th {
        vertical-align: middle;
    }
    .info-table th {
        width: 35%;
        background-color: #f8f9fa;
    }
    .amount-text {
        text-align: right;
        white-space: nowrap;
    }
    .card-tools .btn {
        margin-left: 5px;
    }
</style>
<div class="card shadow-sm">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Detail Sales Invoice</h4>
        <div class="card-tools">
            <a href="{{ route('invoice-customers.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('invoice-customers.edit', $invoice->id) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('invoice-customers.print', $invoice->id) }}" class="btn btn-primary btn-sm" target="_blank">
                <i class="bi bi-printer"></i> Print PDF
            </a>
            <a href="{{ route('invoice-customers.print_invoice', $invoice->id) }}" class="btn btn-success btn-sm" target="_blank">
                <i class="bi bi-file-earmark-pdf"></i> Print Invoice
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <table class="table table-bordered info-table">
                    <tr><th>Invoice Number</th><td>{{ $invoice->invoice_number }}</td></tr>
                    <tr><th>Invoice Date</th><td>{{ date('d/m/Y', strtotime($invoice->invoice_date)) }}</td></tr>
                    <tr><th>Due Date</th><td>{{ $invoice->due_date ? date('d/m/Y', strtotime($invoice->due_date)) : '-' }}</td></tr>
                    <tr>
                        <th>Invoice Type</th>
                        <td>
                            @if($invoice->type == 'proforma')
                                <span class="badge bg-secondary">Proforma</span>
                            @else
                                <span class="badge bg-primary">Sales Invoice</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th>Payment Terms</th><td>{{ $invoice->payment_terms ?? '-' }}</td></tr>
                    <tr><th>Delivery Time</th><td>{{ $invoice->delivery_time ?? '-' }}</td></tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($invoice->status == 'proforma')
                                <span class="badge bg-secondary">Proforma</span>
                            @elseif($invoice->status == 'partial')
                                <span class="badge bg-warning">Partial</span>
                            @elseif($invoice->status == 'completed')
                                <span class="badge bg-success">Completed</span>
                            @elseif($invoice->status == 'cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Payment Status</th>
                        <td>
                            @if($invoice->payment_status == 'sent')
                                <span class="badge bg-secondary">Sent</span>
                            @elseif($invoice->payment_status == 'paid')
                                <span class="badge bg-success">Paid</span>
                            @else
                                <span class="badge bg-secondary">-</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th>Remarks</th><td>{{ $invoice->notes ?? '-' }}</td></tr>
                </table>
            </div>
            <div class="col-md-6 mb-3">
                <table class="table table-bordered info-table">
                    <tr><th>Customer Name</th><td>{{ $invoice->poCustomer->customer->name ?? '-' }}</td></tr>
                    <tr><th>PO Customer Number</th><td>{{ $invoice->poCustomer->po_number ?? '-' }}</td></tr>
                    <tr>
                        <th>PO Customer Status</th>
                        <td>
                            @if($invoice->poCustomer->status == 'issue yet')
                                <span class="badge bg-secondary">Issue Yet</span>
                            @elseif($invoice->poCustomer->status == 'partial')
                                <span class="badge bg-warning">Partial</span>
                            @elseif($invoice->poCustomer->status == 'completed')
                                <span class="badge bg-success">Completed</span>
                            @elseif($invoice->poCustomer->status == 'paid')
                                <span class="badge bg-primary">Paid</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($invoice->poCustomer->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th>Tax Invoice Number</th><td>{{ $invoice->tax_invoice_number ?? '-' }}</td></tr>
                    <tr>
                        <th>Tax Invoice Attachment</th>
                        <td>
                            @if(!empty($invoice->tax_invoice_attachment))
                                <a href="{{ route('tax-invoice.view', $invoice->id) }}" target="_blank" class="btn btn-sm btn-info text-white">
                                    <i class="bi bi-file-earmark-arrow-down-fill"></i> View File
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th>Total Item</th><td>{{ $invoice->details->count() }} Item</td></tr>
                </table>
            </div>
        </div>
        <hr>
        <h5 class="mb-3">Product Details</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center" width="5%">No</th>
                        <th class="text-center">Brand</th>
                        <th class="text-center">Product Code</th>
                        <th class="text-center">Product Name</th>
                        <th class="text-center" width="10%">Qty</th>
                        <th class="text-center" width="10%">Unit</th>
                        <th class="text-center" width="15%">Unit Price</th>
                        <th class="text-center" width="15%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoice->details as $index => $detail)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $detail->product->brand ?? '-' }}</td>
                        <td>{{ $detail->product->product_code }}</td>
                        <td>{{ $detail->product->name }}</td>
                        <td class="text-center">{{ number_format($detail->quantity, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $detail->product->unit ?? '-' }}</td>
                        <td class="amount-text">Rp {{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                        <td class="amount-text">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">No product data available</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr><th colspan="7" class="text-end">Subtotal</th><th class="amount-text">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</th></tr>
                    @if($invoice->dp_amount > 0)
                    <tr><th colspan="6" class="text-end">Payment</th><th class="text-center">{{ $invoice->dp_percent ?? 0 }}%</th><th class="amount-text">Rp {{ number_format($invoice->dp_amount, 0, ',', '.') }}</th></tr>
                    @endif
                    @if($invoice->discount_amount > 0)
                    <tr><th colspan="6" class="text-end">Discount</th><th class="text-center">{{ $invoice->discount_percent ?? 0 }}%</th><th class="amount-text text-danger">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</th></tr>
                    @endif
                    @if($invoice->tax_amount > 0)
                    <tr>
                        <th colspan="6" class="text-end">PPN</th>
                        <!--<th class="text-center">{{ $invoice->tax_percent ?? 0 }}%</th>-->
                        <th class="text-center"> </th>
                        <th class="amount-text">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</th>
                    </tr>
                    @endif
                    @if($invoice->pph23_amount > 0)
                    <tr>
                        <th colspan="6" class="text-end">PPH23</th>
                        <!--<th class="text-center">{{ $invoice->pph23_percent ?? 0 }}%</th>-->
                        <th class="text-center"> </th>
                        <th class="amount-text text-danger">- Rp {{ number_format($invoice->pph23_amount, 0, ',', '.') }}</th>
                    </tr>
                    @endif
                    <tr class="table-primary">
                        <th colspan="7" class="text-end"><strong>Grand Total</strong></th>
                        <th class="amount-text"><strong>Rp {{ number_format($invoice->total, 0, ',', '.') }}</strong></th>
                    </tr>
                    @if($invoice->remaining_amount > 0)
                    <tr><th colspan="7" class="text-end">Remaining Amount</th><th class="amount-text">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</th></tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection