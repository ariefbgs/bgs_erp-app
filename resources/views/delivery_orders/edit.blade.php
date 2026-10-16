@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-delivery-orders-edit bgs-mode-edit')

@section('title', 'Edit Delivery Order')

@section('content')



<div class="card main-card">
    <div class="card-header-custom bgs-page-header">
        <h4>Edit Delivery Order</h4>
    </div>

    <div class="card-body p-4">
        @if(session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Delivery Order belum tersimpan:</strong>
                <ul class="mb-0">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
                <div>Jika sebelumnya memilih attachment, pilih kembali file setelah memperbaiki data.</div>
            </div>
        @endif
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
                    <input type="text" name="do_number" class="form-control" value="{{ old('do_number', $do->do_number) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Delivery Date <span class="text-danger">*</span></label>
                    <input type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date', date('Y-m-d', strtotime($do->delivery_date))) }}" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <input type="text"
                           class="form-control"
                           value="{{ ucfirst($do->status) }}"
                           readonly>
                    <small class="text-muted">
                        New Delivery Orders start as Pending. Delivery confirmation is managed separately.
                    </small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Delivery By</label>
                    <select name="delivery_by" class="form-control">
@php
    $deliveryChoice = old('delivery_by', $do->delivery_by);
    if ($deliveryChoice === 'Wisnu') $deliveryChoice = 'Kurir Sendiri';
    if (in_array($deliveryChoice, ['JNE', 'Courier Online'], true)) $deliveryChoice = 'Kurir Online (JNE/GOJEK/LALAMOVE)';
@endphp
<option value="">-- Pilih Delivery By --</option>
<option value="Kurir Online (JNE/GOJEK/LALAMOVE)" {{ $deliveryChoice === 'Kurir Online (JNE/GOJEK/LALAMOVE)' ? 'selected' : '' }}>Kurir Online (JNE/GOJEK/LALAMOVE)</option>
<option value="Kurir Sendiri" {{ $deliveryChoice === 'Kurir Sendiri' ? 'selected' : '' }}>Kurir Sendiri</option>
</select>
                </div>
            </div>

            <!-- Informasi PO Customer -->
            <div class="card mb-3">
                <div class="card-header bg-light">
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
                                        <textarea name="shipping_address" class="form-control" rows="2">{{ old('shipping_address', $do->shipping_address) }}</textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Receiver Name</th>
                                    <td>
                                        <input type="text" name="receiver_name" class="form-control" value="{{ old('receiver_name', $do->receiver_name) }}">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Produk -->
            <h5 class="section-sub-title">Product Details (Quantity can be updated)</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
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
                                <input type="hidden" name="items[{{ $i }}][po_customer_detail_id]" value="{{ $detail->po_customer_detail_id }}">
                            </td>
                            <td>{{ $detail->product->brand ?? '-' }}</td>
                            <td>
                                @php
                                    $availableQuantity = $deliverableQuantities[$detail->po_customer_detail_id] ?? 0;
                                @endphp
                                <input type="number" name="items[{{ $i }}][quantity]" class="form-control qty-input" value="{{ old('items.' . $i . '.quantity', $detail->quantity) }}" min="0" max="{{ $availableQuantity }}" required>
                                <small class="text-muted">Available: {{ $availableQuantity }}</small>
                            </td>
                        </tr>
                        @endforeach
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
                <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                <small class="text-muted">Leave empty if you do not want to change it. Maximum 5MB. Images will be compressed.</small>
            </div>

            <!-- Remarks -->
            <div class="mt-3">
                <label class="form-label">Remarks</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $do->notes) }}</textarea>
            </div>

            <!-- Button -->
            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('delivery-orders.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection