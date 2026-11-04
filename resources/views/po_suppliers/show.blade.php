@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-po-suppliers-show bgs-mode-screen')

@section('title', 'Detail PO Supplier')

@section('content')




<div class="container-fluid py-4">

    <div class="card main-card">

        {{-- =====================================================
             HEADER
        ====================================================== --}}
        <div class="card-header card-header-custom py-3 bgs-page-header">

            <div class="d-flex justify-content-between align-items-center">

                <h4>
                    <i class="bi bi-file-earmark-text-fill me-2"></i>
                    Detail PO Supplier
                </h4>

                <div class="d-flex gap-2">
{{-- BGS Confirm PO action --}}
@auth
@if(auth()->user()->canUseAdminFeatures() && $poSupplier->status === 'draft')
<form method="POST"
      action="{{ route('po-suppliers.update-status', $poSupplier->id) }}"
      onsubmit="return confirm('Konfirmasi PO Supplier ini untuk diproses?')">
    @csrf
    <input type="hidden" name="status" value="confirmed">
    <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
        Confirm PO
    </button>
</form>
@endif
@endauth

                    <a
                        href="{{ route('po-suppliers.edit', $poSupplier->id) }}"
                        class="btn btn-warning btn-sm fw-bold px-3 text-dark shadow-sm"
                    >
                        <i class="bi bi-pencil-square me-1"></i>
                        Edit PO
                    </a>

                    <a
                        href="{{ route('po-suppliers.print', $poSupplier->id) }}"
                        class="btn btn-light btn-sm fw-bold px-3 shadow-sm"
                        target="_blank"
                    >
                        <i class="bi bi-printer-fill me-1"></i>
                        Print PDF
                    </a>

                    <a
                        href="{{ route('po-suppliers.index') }}"
                        class="btn btn-outline-light btn-sm fw-bold px-3"
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        Back
                    </a>

                </div>

            </div>

        </div>


        <div class="card-body p-4">

            {{-- =================================================
                 HEADER INFORMATION
            ================================================== --}}
            <div class="row g-4 mb-5">

                {{-- LEFT --}}
                <div class="col-md-6">

                    <div class="h-100 p-3 rounded-3 info-box-bg">

                        <div class="section-sub-title">
                            <i class="bi bi-diagram-3-fill me-2"></i>
                            Customer & PO Relationship
                        </div>

                        <table class="table info-table bgs-detail-table align-middle">

                            <tr>
                                <td class="label">Customer</td>
                                <td class="value">
                                    :
                                    {{ $poSupplier->customer->customer_code ?? '' }}
                                    @if(!empty($poSupplier->customer->customer_code))
                                        -
                                    @endif
                                    {{ $poSupplier->customer->name ?? '-' }}
                                </td>
                            </tr>

                            <tr>
                                <td class="label">PO Customer</td>
                                <td class="value fw-semibold text-primary">
                                    :
                                    {{ $poSupplier->poCustomer->po_number ?? '-' }}
                                </td>
                            </tr>

                        </table>

                    </div>

                </div>


                {{-- RIGHT --}}
                <div class="col-md-6">

                    <div class="h-100 p-3 rounded-3 info-box-bg">

                        <div class="section-sub-title">
                            <i class="bi bi-file-earmark-text-fill me-2"></i>
                            Supplier PO Registry
                        </div>

                        <table class="table info-table bgs-detail-table align-middle">

                            <tr>
                                <td class="label">PO Supplier #</td>
                                <td class="value fw-bold text-dark">
                                    :
                                    {{ $poSupplier->po_supplier_number }}
                                </td>
                            </tr>

                            <tr>
                                <td class="label">Supplier</td>
                                <td class="value">
                                    :
                                    {{ $poSupplier->supplier->supplier_code ?? '' }}
                                    @if(!empty($poSupplier->supplier->supplier_code))
                                        -
                                    @endif
                                    {{ $poSupplier->supplier->name ?? '-' }}
                                </td>
                            </tr>

                            <tr>
                                <td class="label">PO Date</td>
                                <td class="value">
                                    :
                                    {{
                                        $poSupplier->po_date
                                            ? date(
                                                'd M Y',
                                                strtotime($poSupplier->po_date)
                                            )
                                            : '-'
                                    }}
                                </td>
                            </tr>

                            <tr>
                                <td class="label">PO Status</td>
                                <td class="value">
                                    :

                                    @switch(strtolower($poSupplier->status ?? ''))

                                        @case('draft')
                                            <span class="badge bg-secondary badge-custom">
                                                Draft
                                            </span>
                                            @break

                                        @case('sent')
                                            <span class="badge bg-primary badge-custom">
                                                Sent
                                            </span>
                                            @break

                                        @case('confirmed')
                                            <span class="badge bg-info text-dark badge-custom">
                                                Confirmed
                                            </span>
                                            @break

                                        @case('received')
                                            <span class="badge bg-success badge-custom">
                                                Received
                                            </span>
                                            @break

                                        @case('cancelled')
                                            <span class="badge bg-danger badge-custom">
                                                Cancelled
                                            </span>
                                            @break

                                        @default
                                            <span class="badge bg-secondary badge-custom">
                                                {{ ucfirst($poSupplier->status ?? '-') }}
                                            </span>

                                    @endswitch

                                </td>
                            </tr>

                            <tr>
                                <td class="label">Receipt Status</td>
                                <td class="value">
                                    :

                                    @php
                                        $receiptStatus = strtolower(
                                            $poSupplier->receipt_status
                                            ?? 'pending'
                                        );
                                    @endphp

                                    @switch($receiptStatus)

                                        @case('complete')
                                        @case('completed')
                                            <span class="badge bg-success badge-custom">
                                                Complete
                                            </span>
                                            @break

                                        @case('partial')
                                            <span class="badge bg-warning text-dark badge-custom">
                                                Partial
                                            </span>
                                            @break

                                        @case('pending')
                                            <span class="badge bg-secondary badge-custom">
                                                Pending
                                            </span>
                                            @break

                                        @default
                                            <span class="badge bg-secondary badge-custom">
                                                {{ ucfirst($receiptStatus) }}
                                            </span>

                                    @endswitch

                                </td>
                            </tr>

                        </table>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 PRODUCT DETAILS
            ================================================== --}}
            <div class="mb-5">

                <div class="section-sub-title">
                    <i class="bi bi-box-seam-fill me-2"></i>
                    Product Detail
                </div>

                <div class="border rounded-3 overflow-hidden shadow-sm">

                    <div class="table-responsive">

                        <table class="table table-modern align-middle m-0 bgs-detail-table">

                            <thead>
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th class="text-center" width="13%">Brand</th>
                                    <th class="text-center" width="13%">Code</th>
                                    <th width="25%">Product Name</th>
                                    <th class="text-center" width="8%">Qty</th>
                                    <th class="text-center" width="8%">Unit</th>
                                    <th class="text-end" width="14%">
                                        Purchase Price
                                    </th>
                                    <th class="text-end" width="14%">
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse(
                                    $poSupplier->details
                                    as $index => $detail
                                )

                                    <tr>

                                        <td class="text-center">
                                            {{ $index + 1 }}
                                        </td>

                                        <td class="text-center">
                                            {{ $detail->product->brand ?? '-' }}
                                        </td>

                                        <td class="text-center">
                                            {{
                                                $detail->product->product_code
                                                ?? '-'
                                            }}
                                        </td>

                                        <td>
                                            {{
                                                $detail->product->name
                                                ?? '-'
                                            }}
                                        </td>

                                        <td class="text-center">
                                            {{
                                                number_format(
                                                    $detail->quantity ?? 0,
                                                    0,
                                                    ',',
                                                    '.'
                                                )
                                            }}
                                        </td>

                                        <td class="text-center">
                                            {{
                                                $detail->product->unit
                                                ?? '-'
                                            }}
                                        </td>

                                        <td class="text-end">
                                            Rp {{
                                                number_format(
                                                    $detail->purchase_price ?? 0,
                                                    0,
                                                    ',',
                                                    '.'
                                                )
                                            }}
                                        </td>

                                        <td class="text-end fw-bold text-dark">
                                            Rp {{
                                                number_format(
                                                    $detail->subtotal ?? 0,
                                                    0,
                                                    ',',
                                                    '.'
                                                )
                                            }}
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td
                                            colspan="8"
                                            class="text-center text-muted py-4"
                                        >
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            No product detail available.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 REMARKS + FINANCIAL SUMMARY
            ================================================== --}}
            @php
                $subtotal = (float) ($poSupplier->subtotal ?? 0);

                $discountPercent =
                    (float) ($poSupplier->discount_percent ?? 0);

                $discountAmount =
                    (float) ($poSupplier->discount_amount ?? 0);

                $afterDiscount =
                    $subtotal - $discountAmount;

                $taxPercent =
                    (float) ($poSupplier->tax_percent ?? 0);

                $taxAmount =
                    (float) ($poSupplier->tax_amount ?? 0);

                $grandTotal =
                    (float) ($poSupplier->total ?? 0);
            @endphp


            <div class="row g-4 text-start mt-2">

                <div class="col-md-7">

                    <div class="p-3 rounded-3 info-box-bg h-100">

                        <div class="section-sub-title mb-2">
                            <i class="bi bi-chat-right-quote-fill me-2"></i>
                            Remarks
                        </div>

                        @if(!empty($poSupplier->notes))

                            <p
                                class="text-secondary mb-0 bgs-u-83260ac9c6c3"

                            >{!! nl2br(e($poSupplier->notes)) !!}</p>

                        @else

                            <span class="text-muted small">
                                No remarks.
                            </span>

                        @endif

                    </div>

                </div>


                <div class="col-md-5">

                    <div class="summary-card">

                        <div class="summary-line">
                            <span class="main-label">
                                Subtotal
                            </span>

                            <span class="fw-bold">
                                Rp {{
                                    number_format(
                                        $subtotal,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                            </span>
                        </div>


                        <div class="summary-line">
                            <span class="main-label">
                                Discount
                                ({{ $discountPercent }}%)
                            </span>

                            <span class="text-warning fw-bold">
                                - Rp {{
                                    number_format(
                                        $discountAmount,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                            </span>
                        </div>


                        <div
                            class="summary-line bgs-u-319a459af405"

                        >
                            <span class="main-label text-info">
                                Subtotal After Discount
                            </span>

                            <span class="text-info fw-bold">
                                Rp {{
                                    number_format(
                                        $afterDiscount,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                            </span>
                        </div>


                        <div class="summary-line">
                            <span class="main-label">
                                VAT ({{ $taxPercent }}%)
                            </span>

                            <span class="fw-bold">
                                + Rp {{
                                    number_format(
                                        $taxAmount,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                            </span>
                        </div>


                        <div class="summary-line grand-total">

                            <span>
                                Grand Total
                            </span>

                            <span class="total-rp">
                                Rp {{
                                    number_format(
                                        $grandTotal,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection