@extends('layouts.app')

@section('title', 'Edit Delivery Order & Document Receipt')

@section('content')

<style>
    .qty-input {
        text-align: right;
    }
</style>

<div class="card">
    <div class="card-header bg-warning text-dark">
        <h4>Edit Delivery Order & Document Receipt</h4>
    </div>

    <div class="card-body">
        <form action="{{ route('delivery-orders.update', $do->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Informasi DO -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">PO Customer</label>
                    <input type="text" class="form-control" value="{{ $do->poCustomer->po_number ?? '-' }} - {{ $do->poCustomer->customer->name ?? '' }}" readonly>
                    <input type="hidden" name="po_customer_id" value="{{ $do->po_customer_id }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Delivery No </label>
                    <input type="text" name="do_number" class="form-control" value="{{ $do->do_number }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Delivery Date <span class="text-danger">*</span></label>
                    <input type="date" name="delivery_date" class="form-control" value="{{ date('Y-m-d', strtotime($do->delivery_date)) }}" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="pending" {{ $do->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="partial" {{ $do->status == 'partial' ? 'selected' : '' }}>Partial Shipment</option>
                        <option value="shipped" {{ $do->status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="delivered" {{ $do->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Delivery By</label>
                    <select name="delivery_by" class="form-control">
                        <option value="">Pilih</option>
                        <option value="Wisnu" {{ $do->delivery_by == 'Wisnu' ? 'selected' : '' }}>Wisnu</option>
                        <option value="JNE" {{ $do->delivery_by == 'JNE' ? 'selected' : '' }}>JNE</option>
                        <option value="Courier Online" {{ $do->delivery_by == 'Courier Online' ? 'selected' : '' }}>Courier Online</option>
                    </select>
                </div>
            </div>

            <!-- Informasi PO Customer -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <strong>PO Customer Information</strong>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="35%">No. PO</th>
                                    <td>{{ $do->poCustomer->po_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Customer</th>
                                    <td>{{ $do->poCustomer->customer->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td>{{ $do->poCustomer->customer->address ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th>Shipping Address</th>
                                    <td>
                                        <textarea name="shipping_address" class="form-control" rows="2">{{ $do->shipping_address }}</textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Receiver Name</th>
                                    <td>
                                        <input type="text" name="receiver_name" class="form-control" value="{{ $do->receiver_name }}">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Produk -->
            <h5>Product Details (Quantity can be updated)</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Product</th>
                            <th>Brand</th>
                            <th width="15%">Quantity Shipped</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($do->details as $i => $detail)
                        <tr>
                            <td>
                                {{ $detail->product->name ?? '-' }}
                                <br>
                                <small>{{ $detail->product->product_code ?? '-' }}</small>
                                <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $detail->product_id }}">
                            </td>
                            <td>{{ $detail->product->brand ?? '-' }}</td>
                            <td>
                                <input type="number" name="items[{{ $i }}][quantity]" class="form-control qty-input" value="{{ $detail->quantity }}" min="0" required>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Tanda Terima Dokumen -->
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
                        <!-- Invoice -->
                        <tr>
                            <td class="text-center">1</td>
                            <td>
                                <strong>Invoice</strong><br>
                                <input type="text" name="invoice_number" class="form-control form-control-sm mt-2" value="{{ $do->invoice_number }}" placeholder="No. Invoice">
                            </td>
                            <td class="align-middle">1 Rangkap (Asli)</td>
                        </tr>
                        <!-- Faktur Pajak -->
                        <tr>
                            <td class="text-center">2</td>
                            <td>
                                <strong>Faktur Pajak</strong><br>
                                <input type="text" name="tax_invoice_number" class="form-control form-control-sm mt-2" value="{{ $do->tax_invoice_number }}" placeholder="No. Faktur Pajak">
                            </td>
                            <td class="align-middle">2 Rangkap (Asli + Copy)</td>
                        </tr>
                        <!-- PO -->
                        <tr>
                            <td class="text-center">3</td>
                            <td>
                                <strong>PO Customer</strong><br>
                                <span class="text-primary">{{ $do->poCustomer->po_number ?? '-' }}</span>
                            </td>
                            <td class="align-middle">1 Rangkap</td>
                        </tr>
                        <!-- DO -->
                        <tr>
                            <td class="text-center">4</td>
                            <td>
                                <strong>Delivery Order</strong><br>
                                <span class="text-success">{{ $do->do_number }}</span>
                            </td>
                            <td class="align-middle">3 Rangkap (Asli + Copy)</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Lampiran (Attachment) -->
            <div class="mt-3">
                <label class="form-label">Attachment (PDF/Image) DO that has been signed by the customer</label>
                @if($do->attachment)
                    <div class="mb-2">
                        <a href="{{ Storage::url($do->attachment) }}" target="_blank" class="btn btn-sm btn-info">View Current Attachment</a>
                    </div>
                @endif
                <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <small class="text-muted">Leave empty if you do not want to change it. Maximum 5MB. Images will be compressed.</small>
            </div>

            <!-- Remarks -->
            <div class="mt-3">
                <label>Remarks</label>
                <textarea name="notes" class="form-control" rows="2">{{ $do->notes }}</textarea>
            </div>

            <!-- Button -->
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('delivery-orders.index') }}" class="btn btn-danger">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection