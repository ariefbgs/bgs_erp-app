@extends('layouts.app')
@section('title', 'Product Management')
@section('content')
<style>
    /* Mengubah Background dasar halaman agar senada dengan halaman show */
    body {
        background-color: #f1f5f9;
    }

    /* Desain Card Utama */
    .main-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        background: #fff;
        overflow: hidden;
    }
    
    /* Header dengan gradasi warna Ocean Blue */
    .card-header-custom {
        background: linear-gradient(135deg, #0284c7, #075985);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    .card-header-custom h4 {
        color: white;
        font-weight: 700;
        margin-bottom: 0;
    }

    /* Search & Filter Bar Custom */
    .search-filter-bar {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 25px;
    }
    .filter-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #475569;
        font-weight: 700;
        margin-bottom: 6px;
    }

    /* Styling Tabel Modern */
    .table-modern thead th {
        background-color: #0c4a6e;
        color: #f0f9ff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.78rem;
        letter-spacing: 0.5px;
        padding: 14px 10px;
        border: none;
        white-space: nowrap;
    }
    .table-modern thead th a {
        color: #f0f9ff !important;
        text-decoration: none;
        display: inline-block;
        width: 100%;
    }
    .table-modern thead th a:hover {
        color: #38bdf8 !important;
    }
    .table-modern tbody tr {
        transition: background-color 0.2s ease;
    }
    .table-modern tbody tr:nth-child(even) {
        background-color: #fcfdfe;
    }
    .table-modern tbody tr:hover {
        background-color: #f1f5f9 !important;
    }
    .table-modern tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        font-size: 0.9rem;
        color: #334155;
        border-color: #f1f5f9;
    }
    
    /* Tombol Header & Custom Input */
    .btn-light-custom {
        background-color: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        font-weight: 600;
    }

    .btn-light-custom:hover {
        background-color: rgba(255, 255, 255, 0.3);
        color: white;
    }

    .form-control, .form-select {
        border-color: #cbd5e1;
    }

    .form-control:focus, .form-select:focus {
        border-color: #38bdf8;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
    }

    .product-image-placeholder {
        width: 55px;
        height: 55px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
        font-size: 12px;
        margin: auto;
    }

    .product-image {
        width: 55px;
        height: 55px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        display: block;
        margin: auto;
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
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
                <div id="success-alert" class="alert alert-success border-0 shadow-sm d-flex align-items-center fade show mb-4" style="background-color: #f0fdf4; color: #15803d; border-left: 4px solid #16a34a !important; border-radius: 6px;">
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size: 0.8rem;"></button>
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
                <div class="alert alert-danger border-0 shadow-sm mb-4" style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
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

                        <div class="col-md-3 mb-3">
                            <label class="filter-label">Filter Stock</label>
                            <select name="stock" class="form-select">
                                <option value="">All Stock</option>
                                <option value="available" {{ request('stock') == 'available' ? 'selected' : '' }}>Available</option>
                                <option value="empty" {{ request('stock') == 'empty' ? 'selected' : '' }}>Empty</option>
                                <option value="low" {{ request('stock') == 'low' ? 'selected' : '' }}>Low Stock</option>
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
                <div class="table-responsive">
                    <table class="table table-modern align-middle m-0">
                        <thead>
                            <tr>
                                <th width="3%" class="text-center">No</th>
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
                                <th width="5%" class="text-center">Stock</th>
                                <th width="15%" class="text-center">Remarks</th>
                                <th width="10%" class="text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($products as $index => $product)
                                <tr>
                                    <td class="text-center">
                                        {{ $products->firstItem() + $index }}
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
                                                    // Karena root web = folder proyek, URL harus diawali 'public/'
                                                    // Contoh: domain.com/public/uploads/products/... 
                                                    $imageUrl = asset('public/' . $cleanPath);
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
                                            <div class="product-image-placeholder" style="display: none;">
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

                                    <td class="text-center">
                                        @if($product->stock <= 0)
                                            <span class="badge bg-danger">Empty</span>
                                        @elseif($product->stock <= 5)
                                            <span class="badge bg-warning text-dark">{{ $product->stock }}</span>
                                        @else
                                            <span class="badge bg-success">{{ $product->stock }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {!! nl2br(e($product->description ?? '-')) !!}
                                    </td>

                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
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
                <div class="small fw-bold" style="color: #475569;">
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