@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-products-index bgs-mode-index')
@section('title', 'Product Management')
@section('content')


<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
            <h4>
                <i class="bi bi-box-seam"></i>
                Product Management
            </h4>
            <a href="{{ route('products.create') }}" class="btn btn-light-custom btn-sm px-3 py-2">
                <i class="bi bi-plus-circle"></i>
                Add New Product
            </a>
        </div>

        <div class="card-body p-4">
            {{-- SUCCESS MESSAGE ALERT --}}
            @if(session('success'))
                <div id="success-alert" class="alert alert-success border-0 shadow-sm d-flex align-items-center fade show mb-4 bgs-u-8a1856d11134">
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close ms-auto bgs-u-aae30f4ce161" data-bs-dismiss="alert"></button>
                </div>

                <script>
                    setTimeout(() => {
                        let alert = document.getElementById('success-alert');
                        if (alert) {
                            let bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                            bsAlert.close();
                        }
                    }, 3000);
                </script>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4 bgs-u-f812d16479e9">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                </div>
            @endif  

            {{-- FILTER --}}
            <div class="search-filter-bar">
                <form method="GET" action="{{ route('products.index') }}">
                    <input type="hidden" name="sort" value="{{ request('sort', 'updated_at') }}">
                    <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">

                    <div class="row align-items-end">
                        <div class="col-md-4 mb-3">
                            <label class="filter-label">Search Product</label>
                            <input type="text" name="search" class="form-control" placeholder="Code / Name / Brand" value="{{ request('search') }}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="filter-label">Filter Unit</label>
                            <select name="unit" class="form-select">
                                <option value="">All Unit</option>
                                <option value="pcs" {{ request('unit') == 'pcs' ? 'selected' : '' }}>Pcs</option>
                                <option value="set" {{ request('unit') == 'set' ? 'selected' : '' }}>Set</option>
                                <option value="box" {{ request('unit') == 'box' ? 'selected' : '' }}>Box</option>
                                <option value="kg" {{ request('unit') == 'kg' ? 'selected' : '' }}>Kg</option>
                                <option value="liter" {{ request('unit') == 'liter' ? 'selected' : '' }}>Liter</option>
                                <option value="unit" {{ request('unit') == 'unit' ? 'selected' : '' }}>Unit</option>
                            </select>
                        </div>



                        <div class="col-md-2 mb-3 d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm py-2">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm py-2">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- TABLE --}}
            <div class="border rounded-3 overflow-hidden shadow-sm mb-3">
                <div class="table-responsive quotation-table-wrap">
                    <table class="table table-modern align-middle m-0 quotation-table bgs-index-table bgs-actions-compact">
                        <thead>
                            <tr>
                                <th width="3%" class="text-center">No</th>
                                <th class="text-center col-action">Actions</th>
                                <th width="8%" class="text-center">Image</th>
                                
                                {{-- SORTABLE BRAND --}}
                                <th width="8%">
                                    <a href="{{ route('products.index', array_merge(request()->query(), ['sort' => 'brand', 'direction' => request('sort') == 'brand' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}">
                                        Brand
                                        @if(request('sort') == 'brand')
                                            <i class="bi bi-sort-alpha-{{ request('direction') == 'asc' ? 'down' : 'up-alt' }}"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up text-muted small"></i>
                                        @endif
                                    </a>
                                </th>

                                {{-- SORTABLE PRODUCT NAME --}}
                                <th width="15%">
                                    <a href="{{ route('products.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('sort') == 'name' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}">
                                        Product Name
                                        @if(request('sort') == 'name')
                                            <i class="bi bi-sort-alpha-{{ request('direction') == 'asc' ? 'down' : 'up-alt' }}"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up text-muted small"></i>
                                        @endif
                                    </a>
                                </th>

                                <th width="5%" class="text-center">Unit</th>
                                <th width="9%" class="text-center">Selling Price</th>
                                <th width="9%" class="text-center">Purchase Price</th>
                                <th width="15%" class="text-center">Remarks</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($products as $index => $product)
                                <tr>
                                    <td class="text-center">
                                        {{ $products->firstItem() + $index }}
                                    </td>
                        <td class="text-center col-action">
                                        <div class="d-flex justify-content-center action-btn-group">
                                            <a href="{{ route('products.show', $product->id) }}" class="btn btn-info btn-sm text-white" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-warning btn-sm text-white" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this product?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>

                                    {{-- ========================================================= --}}
                                    {{-- PERBAIKAN: MENAMPILKAN GAMBAR PRODUK DENGAN CARA YANG LEBIH ROBUST --}}
                                    {{-- ========================================================= --}}
                                    <td class="text-center">
                                        @php
                                            $imageUrl = null;
                                            $imagePath = $product->image ?? '';

                                            if (!empty($imagePath)) {
                                                // Normalisasi path (hapus slash di awal dan ganti backslash)
                                                $cleanPath = ltrim($imagePath, '/');
                                                $cleanPath = str_replace('\\', '/', $cleanPath);
                                                
                                                // Pastikan path mengandung 'uploads/products/'
                                                if (!str_contains($cleanPath, 'uploads/products/')) {
                                                    $cleanPath = 'uploads/products/' . basename($cleanPath);
                                                }

                                                // Cek file di public_path('uploads/products/...')
                                                $fullPath = public_path($cleanPath);
                                                
                                                if (file_exists($fullPath)) {
                                                    $imageUrl = asset($cleanPath);
                                                } else {
                                                    // Fallback: coba tanpa 'public/'
                                                    $imageUrl = asset($cleanPath);
                                                }
                                            }
                                        @endphp

                                        @if($imageUrl)
                                            <img src="{{ $imageUrl }}" 
                                                alt="{{ $product->name }}" 
                                                class="product-image"
                                                loading="lazy"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="product-image-placeholder bgs-u-6e22c58a7a6a">
                                                <div class="mt-1 small text-muted">No Image</div>
                                            </div>
                                        @else
                                            <div class="product-image-placeholder">
                                                <div class="mt-1 small text-muted">No Image</div>
                                            </div>
                                        @endif
                                    </td>

                                    <td><strong>{{ $product->brand ?? '-' }}</strong></td>
                                    <td>{{ $product->name2 }}</td>
                                    <td class="text-center text-uppercase">{{ $product->unit }}</td>
                                    <td class="text-end">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</td>


                                    <td>
                                        {!! nl2br(e($product->description ?? '-')) !!}
                                    </td>


                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        There is no product data available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PAGINATION BANNER --}}
            <div class="d-flex justify-content-between align-items-center px-2">
                <div class="small fw-bold bgs-u-7e4cfe51c78c">
                    Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} entries
                </div>
                <div>
                    {{ $products->appends(request()->query())->links() }}
                </div>
            </div>

        </div>
    </div>
</div>
@endsection