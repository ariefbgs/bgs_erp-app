@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-products-show bgs-mode-screen')
@section('title', 'Product Detail')
@section('content')

<div class="card product-card">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center bgs-page-header">
        <h4 class="mb-0">
            Product Detail
        </h4>
        <div>
            <a href="{{ route('products.index') }}"
               class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i>
                Back
            </a>
            <a href="{{ route('products.edit', $product->id) }}"
               class="btn btn-warning btn-sm text-white">
                <i class="bi bi-pencil"></i>
                Edit
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- PRODUCT IMAGE --}}
            <div class="col-md-4 mb-4">
                @php
                    $imageUrl = null;
                    $imagePath = $product->image ?? '';

                    if (!empty($imagePath)) {
                        $cleanPath = ltrim($imagePath, '/');
                        $cleanPath = str_replace('\\', '/', $cleanPath);
                        
                        if (!str_contains($cleanPath, 'uploads/products/')) {
                            $cleanPath = 'uploads/products/' . basename($cleanPath);
                        }

                        // Coba dengan public_path
                        $fullPath = public_path($cleanPath);
                        if (file_exists($fullPath)) {
                            $imageUrl = asset($cleanPath);
                        } else {
                            // Fallback tanpa prefix
                            $imageUrl = asset($cleanPath);
                        }
                    }
                @endphp

                @if($imageUrl)
                    <img src="{{ $imageUrl }}"
                        alt="{{ $product->name }}"
                        class="product-image w-100 bgs-u-03e442319e20"

                        loading="lazy">
                @else
                    <div class="border rounded d-flex align-items-center justify-content-center bgs-u-495d3738b0d9"
                       >
                        <div class="text-center text-muted">
                            <i class="bi bi-image bgs-u-4ce5d042480c"></i>
                            <div class="mt-2">No Image</div>
                        </div>
                    </div>
                @endif
            </div>
            {{-- PRODUCT INFORMATION --}}
            <div class="col-md-8">
                <div class="section-title">
                    Product Information
                </div>
                <table class="table table-detail bgs-detail-table align-middle">
                    <tr>
                        <th>Product Code</th>
                        <td>
                            <strong>
                                {{ $product->product_code }}
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <th>Product Name</th>
                        <td>
                            {{-- e() digunakan untuk mengamankan teks, nl2br untuk mengubah enter menjadi <br> --}}
                            {!! $product->name ? nl2br(e($product->name)) : '-' !!}
                        </td>
                    </tr>
                    <tr>
                        <th>Customer Product Code</th>
                        <td>
                            {{ $product->product_code2 ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Customer Product Name</th>
                        <td>
                            {{-- e() digunakan untuk mengamankan teks, nl2br untuk mengubah enter menjadi <br> --}}
                            {!! $product->name2 ? nl2br(e($product->name2)) : '-' !!}
                        </td>
                    </tr>
                    <tr>
                        <th>Specification</th>
                        <td>
                            {{-- e() digunakan untuk mengamankan teks, nl2br untuk mengubah enter menjadi <br> --}}
                            {!! $product->specification ? nl2br(e($product->specification)) : '-' !!}
                        </td>
                    </tr>
                    <tr>
                        <th>Brand</th>
                        <td>
                            {{ $product->brand ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>Unit</th>
                        <td>
                            {{ ucfirst($product->unit) }}
                        </td>
                    </tr>
                    <tr>
                        <th>Selling Price</th>
                        <td>

                            <span class="price-text">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </span>

                        </td>
                    </tr>
                    <tr>
                        <th>Purchase Price</th>
                        <td>
                            <span class="fw-bold text-primary">
                                Rp {{ number_format($product->purchase_price, 0, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Stock</th>
                        <td>
                            @if($product->stock > 0)
                                <span class="badge bg-success stock-badge">
                                    {{ number_format($product->stock, 0, ',', '.') }}
                                    {{ ucfirst($product->unit) }}
                                </span>
                            @else
                                <span class="badge bg-danger stock-badge">
                                    Out of Stock
                                </span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Remarks</th>
                        <td>
                            {!! nl2br(e($product->description ?? '-')) !!}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection