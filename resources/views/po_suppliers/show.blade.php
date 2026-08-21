@extends('layouts.app')
@section('title', 'PO Supplier Details')
@section('content')
<div class="card">
    <div class="card-header bg-info text-white">
        <h4>PO Supplier Details</h4>
        <div class="card-tools">
            <a href="{{ route('po-suppliers.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('po-suppliers.edit', $poSupplier->id) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('po-suppliers.print', $poSupplier->id) }}" class="btn btn-primary btn-sm" target="_blank">
                <i class="bi bi-printer"></i> Print PDF
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Informasi PO Supplier -->
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th width="35%">PO Supplier #</th>
                        <td>{{ $poSupplier->po_supplier_number }}</td>
                    </tr>
                    <tr>
                        <th>PO Date</th>
                        <td>{{ date('d/m/Y', strtotime($poSupplier->po_date)) }}</td>
                    </tr>
                    <tr>
                        <th>Expected Date</th>
                        <td>{{ $poSupplier->expected_date ? date('d/m/Y', strtotime($poSupplier->expected_date)) : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($poSupplier->status == 'draft')
                                <span class="badge bg-secondary"><i class="bi bi-file-earmark-text"></i> Draft</span>
                            @elseif($poSupplier->status == 'sent')
                                <span class="badge bg-primary"><i class="bi bi-envelope-paper"></i> Sent</span>
                            @elseif($poSupplier->status == 'confirmed')
                                <span class="badge bg-info"><i class="bi bi-check2-circle"></i> Confirmed</span>
                            @elseif($poSupplier->status == 'received')
                                <span class="badge bg-success"><i class="bi bi-box-seam"></i> Received</span>
                            @elseif($poSupplier->status == 'cancelled')
                                <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Cancelled</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($poSupplier->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Remarks</th>
                        <td>{{ $poSupplier->notes ?? '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th>PO Customer</th>
                        <td>{{ $poSupplier->poCustomer->po_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Customer</th>
                        <td>{{ $poSupplier->customer->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Supplier</th>
                        <td>{{ $poSupplier->supplier->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Supplier Code</th>
                        <td>{{ $poSupplier->supplier->supplier_code ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <hr>
        <h5>Product Details</h5>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Brand</th>
                        <th class="text-center">Code</th>
                        <th class="text-center">Product Name</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-center">Unit</th>
                        <th class="text-center">Purchase Price</th>
                        <th class="text-center">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($poSupplier->details as $index => $detail)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center">{{ $detail->product->brand ?? '-' }}</td>
                        <td class="text-center">{{ $detail->product->product_code }}</td>
                        <td>{{ $detail->product->name }}</td>
                        <td class="text-center">{{ number_format($detail->quantity, 0, ',', '.') }}</td>
                        <td class="text-left">{{ $detail->product->unit }}</td>
                        <td class="text-end">Rp {{ number_format($detail->purchase_price, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="7" class="text-end">Subtotal:</th>
                        <th class="text-end">Rp {{ number_format($poSupplier->subtotal, 0, ',', '.') }}</th>
                    </tr>
                    @if($poSupplier->discount_amount > 0)
                    <tr>
                        <th colspan="7" class="text-end">Diskon ({{ $poSupplier->discount_percent ?? 0 }}%):</th>
                        <th class="text-end">Rp {{ number_format($poSupplier->discount_amount, 0, ',', '.') }}</th>
                    </tr>
                    @endif
                    @if($poSupplier->tax_amount > 0)
                    <tr>
                        <th colspan="7" class="text-end">PPN ({{ $poSupplier->tax_percent ?? 0 }}%):</th>
                        <th class="text-end">Rp {{ number_format($poSupplier->tax_amount, 0, ',', '.') }}</th>
                    </tr>
                    @endif
                    <tr class="table-primary">
                        <th colspan="7" class="text-end"><strong>Grand Total:</strong></th>
                        <th class="text-end"><strong>Rp {{ number_format($poSupplier->total, 0, ',', '.') }}</strong></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($poSupplier->notes)
        <div class="mt-3">
            <h5>Remarks:</h5>
            <p>{{ $poSupplier->notes }}</p>
        </div>
        @endif
    </div>
</div>
@endsection