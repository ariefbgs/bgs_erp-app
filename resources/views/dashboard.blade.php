@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<style>
    :root {
        --subtle-primary: rgba(13, 110, 253, 0.08);
        --subtle-success: rgba(25, 135, 84, 0.08);
        --subtle-info: rgba(13, 202, 240, 0.08);
        --subtle-warning: rgba(255, 193, 7, 0.08);
        --subtle-secondary: rgba(108, 117, 125, 0.08);
        --subtle-dark: rgba(33, 37, 41, 0.08);
        --subtle-danger: rgba(220, 53, 69, 0.08);
        --subtle-purple: rgba(111, 66, 193, 0.08);
    }

    /* Optimasi Mobile */
    @media (max-width: 768px) {
        .container-fluid { padding-left: 10px !important; padding-right: 10px !important; }
        .dashboard-card { margin-bottom: 10px; }
        .chart-card { min-height: 300px; }
        .icon-box { width: 40px !important; height: 40px !important; font-size: 1.1rem !important; }
        h4 { font-size: 1.1rem; }
        h5 { font-size: 1rem; }
    }

    /* Memastikan Canvas Chart tidak meluap */
    .chart-body { position: relative; height: 250px; width: 100%; }

    /* Perbaikan agar tabel di HP tidak berantakan */
    .table-responsive { 
        overflow-x: auto; 
        -webkit-overflow-scrolling: touch; 
    }

    .recent-table { min-width: 500px; } /* Mencegah kolom terlalu gepeng di HP */

    body {
        background-color: #f8f9fa;
    }

    /* Modern Card Style */
    .dashboard-card {
        background: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 16px;
        transition: all 0.25s ease-in-out;
        cursor: pointer;
        text-decoration: none !important;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    
    .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.06) !important;
        border-color: rgba(0, 0, 0, 0.12);
    }

    /* Icon Containers dengan warna lembut */
    .icon-box {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    /* Table Design Upgrade */
    .table-card {
        border: 1px solid #e9ecef;
        border-radius: 16px;
        overflow: hidden;
        background: #ffffff;
    }

    .table-card .card-header {
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 1.25rem;
    }

    .recent-table th {
        font-weight: 600;
        text-uppercase: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: #6c757d;
        background-color: #f8f9fa;
        padding: 10px 15px;
    }

    .recent-table td {
        padding: 12px 15px;
        vertical-align: middle;
        font-size: 0.85rem;
        color: #495057;
    }

    /* Badge Soft Styles */
    .badge-soft {
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 6px;
    }
    
    /* Chart Container */
    .chart-card {
        border: 1px solid #e9ecef;
        border-radius: 16px;
        background: #ffffff;
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-white border rounded-3 p-4 shadow-sm d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="text-dark fw-bold mb-1">Selamat datang kembali, {{ Auth::user()->name }}!</h4>
                    <p class="text-muted mb-0">Berikut adalah ringkasan performa aktivitas operasional dan finansial ERP Anda.</p>
                </div>
                <div class="text-end d-none d-md-block">
                    <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
                        <i class="bi bi-calendar3 me-2"></i>{{ date('d M Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold text-secondary mb-3">Ringkasan Arus Keuangan & Biaya</h5>
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <div class="col">
            <a href="{{ route('invoice-customers.index', ['status' => 'paid']) }}" class="card dashboard-card h-100 p-3 shadow-sm border-start border-success border-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-uppercase tracking-wider small fw-bold text-success">Total Invoice Paid (In)</span>
                    <div class="icon-box text-success" style="background-color: var(--subtle-success);"><i class="bi bi-cash-stack"></i></div>
                </div>
                <div>
                    <h3 class="fw-bold text-success mb-1">Rp {{ number_format($totalInvoicePaid, 0, ',', '.') }}</h3>
                    <p class="mb-0 text-muted small">Total dana masuk berhasil dicairkan</p>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('expense.index') }}" class="card dashboard-card h-100 p-3 shadow-sm border-start border-danger border-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-uppercase tracking-wider small fw-bold text-danger">Total Biaya / Pengeluaran (Out)</span>
                    <div class="icon-box text-danger" style="background-color: var(--subtle-danger);"><i class="bi bi-credit-card"></i></div>
                </div>
                <div>
                    <h3 class="fw-bold text-danger mb-1">Rp {{ number_format($totalExpenses ?? 0, 0, ',', '.') }}</h3>
                    <p class="mb-0 text-muted small">Biaya operasional, gaji, & pengeluaran lain</p>
                </div>
            </a>
        </div>

        <div class="col">
            <div class="card dashboard-card h-100 p-3 shadow-sm border-start border-purple border-3" style="cursor: default;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-uppercase tracking-wider small fw-bold text-purple" style="color: #6f42c1 !important;">Estimasi Profit Bersih</span>
                    <div class="icon-box text-purple" style="background-color: var(--subtle-purple); color: #6f42c1;"><i class="bi bi-wallet2"></i></div>
                </div>
                <div>
                    @php $netProfit = $totalInvoicePaid - ($totalExpenses ?? 0); @endphp
                    <h3 class="fw-bold mb-1 {{ $netProfit >= 0 ? 'text-dark' : 'text-danger' }}">
                        Rp {{ number_format($netProfit, 0, ',', '.') }}
                    </h3>
                    <p class="mb-0 text-muted small">Kalkulasi: (Invoice Paid - Total Biaya)</p>
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold text-secondary mb-3 mt-4">Ringkasan Dokumen & Transaksi Logistik</h5>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-6 g-2 mb-4">
        <div class="col">
            <a href="{{ route('quotations.index') }}" class="card dashboard-card h-100 p-3 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase tracking-wider text-muted fw-bold" style="font-size: 0.75rem;">Quotation</span>
                    <div class="icon-box text-primary" style="background-color: var(--subtle-primary); width: 38px; height: 38px; font-size: 1.1rem;"><i class="bi bi-file-text"></i></div>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-1">{{ number_format($totalQuotation) }}</h4>
                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">Rp {{ number_format($totalQuotationValue, 0, ',', '.') }}</p>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('po-customers.index') }}" class="card dashboard-card h-100 p-3 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase tracking-wider text-muted fw-bold" style="font-size: 0.75rem;">PO Customer</span>
                    <div class="icon-box text-success" style="background-color: var(--subtle-success); width: 38px; height: 38px; font-size: 1.1rem;"><i class="bi bi-cart-check"></i></div>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-1">{{ number_format($totalPoCustomer) }}</h4>
                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">Rp {{ number_format($totalPoCustomerValue, 0, ',', '.') }}</p>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('po-suppliers.index') }}" class="card dashboard-card h-100 p-3 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase tracking-wider text-muted fw-bold" style="font-size: 0.75rem;">PO Supplier</span>
                    <div class="icon-box text-info" style="background-color: var(--subtle-info); width: 38px; height: 38px; font-size: 1.1rem;"><i class="bi bi-truck"></i></div>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-1">{{ number_format($totalPoSupplier) }}</h4>
                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">Rp {{ number_format($totalPoSupplierValue, 0, ',', '.') }}</p>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('goods-receipts.index') }}" class="card dashboard-card h-100 p-3 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase tracking-wider text-muted fw-bold" style="font-size: 0.75rem;">Goods Receipt</span>
                    <div class="icon-box text-warning" style="background-color: var(--subtle-warning); width: 38px; height: 38px; font-size: 1.1rem;"><i class="bi bi-box-seam"></i></div>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-1">{{ number_format($totalGoodsReceipt) }}</h4>
                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">Logistik Masuk</p>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('delivery-orders.index') }}" class="card dashboard-card h-100 p-3 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase tracking-wider text-muted fw-bold" style="font-size: 0.75rem;">Delivery Order</span>
                    <div class="icon-box text-secondary" style="background-color: var(--subtle-secondary); width: 38px; height: 38px; font-size: 1.1rem;"><i class="bi bi-truck"></i></div>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-1">{{ number_format($totalDeliveryOrder) }}</h4>
                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">Pengiriman Barang</p>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('invoice-customers.index') }}" class="card dashboard-card h-100 p-3 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase tracking-wider text-muted fw-bold" style="font-size: 0.75rem;">Sales Invoice</span>
                    <div class="icon-box text-dark" style="background-color: var(--subtle-dark); width: 38px; height: 38px; font-size: 1.1rem;"><i class="bi bi-receipt"></i></div>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-1">{{ number_format($totalInvoice) }}</h4>
                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">Rp {{ number_format($totalInvoiceValue, 0, ',', '.') }}</p>
                </div>
            </a>
        </div>
    </div>

    <h5 class="fw-bold text-secondary mb-3 mt-4">Analitik & Tren Performa</h5>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm chart-card h-100">
                <div class="card-header bg-white"><h6 class="card-title fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-steps text-primary me-2"></i>Tren Penawaran (Quotation)</h6></div>
                <div class="card-body"><canvas id="quotationChart" style="height: 240px;"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm chart-card h-100">
                <div class="card-header bg-white"><h6 class="card-title fw-bold mb-0 text-dark"><i class="bi bi-graph-up text-success me-2"></i>Kesehatan Keuangan (Invoice vs Terbayar vs Biaya)</h6></div>
                <div class="card-body"><canvas id="invoiceChart" style="height: 240px;"></canvas></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm chart-card h-100">
                <div class="card-header bg-white"><h6 class="card-title fw-bold mb-0 text-dark"><i class="bi bi-pie-chart text-danger me-2"></i>Alokasi Kategori Biaya</h6></div>
                <div class="card-body"><canvas id="expensePieChart" style="height: 240px;"></canvas></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm chart-card h-100">
                <div class="card-header bg-white"><h6 class="card-title fw-bold mb-0 text-dark"><i class="bi bi-arrow-left-right text-info me-2"></i>Perbandingan PO Customer vs Supplier</h6></div>
                <div class="card-body"><canvas id="poComparisonChart" style="height: 240px;"></canvas></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm chart-card h-100">
                <div class="card-header bg-white"><h6 class="card-title fw-bold mb-0 text-dark"><i class="bi bi-box-seam text-warning me-2"></i>Alur Logistik (GR vs DO)</h6></div>
                <div class="card-body"><canvas id="logisticChart" style="height: 240px;"></canvas></div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold text-secondary mb-3 mt-4">Aktivitas Terkini</h5>
    
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm table-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Quotation Terbaru</h6>
                    <a href="{{ route('quotations.index') }}" class="btn btn-sm btn-light border text-primary fw-semibold small">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 recent-table">
                        <thead>
                            <tr><th>No. Quotation</th><th>Customer</th><th>Tanggal</th><th>Total</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentQuotations as $q)
                            <tr>
                                <td class="fw-semibold text-primary">{{ $q->quotation_number }}</td>
                                <td>{{ $q->customer->name }}</td>
                                <td>{{ $q->date ? date('d/m/Y', strtotime($q->date)) : '-' }}</td>
                                <td class="fw-semibold">Rp {{ number_format($q->total, 0, ',', '.') }}</td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary badge-soft">{{ strtoupper($q->status) }}</span></td>
                            </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow-sm table-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-receipt text-dark me-2"></i>Invoice Terbaru</h6>
                    <a href="{{ route('invoice-customers.index') }}" class="btn btn-sm btn-light border text-primary fw-semibold small">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 recent-table">
                        <thead>
                            <tr><th>No. Invoice</th><th>Customer</th><th>Tgl Invoice</th><th>Total</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentInvoices as $inv)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->poCustomer->customer->name ?? '-' }}</td>
                                <td>{{ $inv->invoice_date ? date('d/m/Y', strtotime($inv->invoice_date)) : '-' }}</td>
                                <td class="fw-semibold">Rp {{ number_format($inv->total, 0, ',', '.') }}</td>
                                <td><span class="badge bg-success bg-opacity-10 text-success badge-soft">{{ strtoupper($inv->status) }}</span></td>
                            </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm table-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-credit-card text-danger me-2"></i>Pengeluaran / Biaya Terbaru</h6>
                    <a href="{{ route('expense.index') }}" class="btn btn-sm btn-light border text-primary fw-semibold small">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 recent-table">
                        <thead>
                            <tr><th>Kategori</th><th>Keterangan</th><th>Tanggal</th><th>Jumlah</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentExpenses ?? [] as $exp)
                            <tr>
                                <td class="fw-semibold">
                                    <span class="badge bg-danger bg-opacity-10 text-danger badge-soft">
                                        {{ $exp->expense_type ?? 'Umum' }}
                                    </span>
                                </td>
                                <td>{{ Str::limit($exp->description, 30) }}</td>
                                <td>{{ $exp->expense_date ? $exp->expense_date->format('d/m/Y') : '-' }}</td>
                                <td class="fw-semibold text-danger">Rp {{ number_format($exp->amount, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data biaya resmi</td></tr>
                            @endforelse
                            </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm table-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-cart-check text-success me-2"></i>PO Customer Terbaru</h6>
                    <a href="{{ route('po-customers.index') }}" class="btn btn-sm btn-light border text-primary fw-semibold small">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 recent-table">
                        <thead>
                            <tr><th>No. PO Customer</th><th>Customer</th><th>Tanggal</th><th>Total Nilai</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentPoCustomers ?? [] as $poC)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $poC->po_number }}</td>
                                <td>{{ $poC->customer->name ?? '-' }}</td>
                                <td>{{ $poC->date ? date('d/m/Y', strtotime($poC->date)) : '-' }}</td>
                                <td class="fw-semibold text-success">Rp {{ number_format($poC->total_value, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm table-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-truck text-info me-2"></i>PO Supplier Terbaru</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 recent-table">
                        <thead>
                            <tr><th>No. PO Supplier</th><th>Tanggal</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentPoSuppliers ?? [] as $poS)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $poS->po_supplier_number }}</td>
                                <td>{{ $poS->date ? date('d/m/Y', strtotime($poS->date)) : '-' }}</td>
                                <td class="fw-semibold text-info">Rp {{ number_format($poS->total_value, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">Belum ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm table-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam text-warning 'me-2'"></i>Goods Receipt Terbaru</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 recent-table">
                        <thead>
                            <tr><th>No. GR</th><th>Tanggal</th><th>Penerima</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentGoodsReceipts ?? [] as $gr)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $gr->gr_number }}</td>
                                <td>{{ $gr->received_date ? date('d/m/Y', strtotime($gr->received_date)) : '-' }}</td>
                                <td>{{ $gr->received_by ?? '-' }}</td>
                            </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">Belum ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm table-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-truck text-secondary me-2"></i>Delivery Order Terbaru</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 recent-table">
                        <thead>
                            <tr><th>No. DO</th><th>Tanggal</th><th>Driver</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentDeliveryOrders ?? [] as $do)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $do->do_number }}</td>
                                <td>{{ $do->delivery_date ? date('d/m/Y', strtotime($do->delivery_date)) : '-' }}</td>
                                <td>{{ $do->driver_name ?? '-' }}</td>
                            </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">Belum ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Siapkan labels bulan
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const year = new Date().getFullYear();

        // Konfigurasi dasar
        Chart.defaults.font.family = "'Segoe UI', 'Helvetica Neue', Arial, sans-serif";
        Chart.defaults.color = '#6c757d';

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { boxWidth: 10, usePointStyle: true }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: { 
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1e6) return (value / 1e6).toFixed(1) + ' Jt';
                            if (value >= 1e3) return (value / 1e3).toFixed(0) + ' Rb';
                            return value;
                        }
                    }
                }
            }
        };

        // =======================================================
        // 1. QUOTATION CHART (line)
        // =======================================================
        const quotationCounts = @json(array_values($monthlyQuotationCount));
        const quotationTotals = @json(array_values($monthlyQuotationTotal)).map(v => v / 1000000); // dalam juta

        new Chart(document.getElementById('quotationChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        label: 'Jumlah Quotation',
                        data: quotationCounts,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.05)',
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Nilai Quotation (Juta)',
                        data: quotationTotals,
                        borderColor: '#fd7e14',
                        backgroundColor: 'transparent',
                        tension: 0.3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                ...chartOptions,
                scales: {
                    x: { grid: { display: false } },
                    y: { 
                        beginAtZero: true,
                        title: { display: true, text: 'Jumlah' }
                    },
                    y1: {
                        position: 'right',
                        title: { display: true, text: 'Juta Rp' },
                        grid: { display: false },
                        beginAtZero: true
                    }
                }
            }
        });

        // =======================================================
        // 2. KEUANGAN CHART (line with YTD cumulative)
        // =======================================================
        const invoicePaidYTD = @json(array_values($monthlyInvoicePaid));
        const expenseYTD = @json(array_values($monthlyExpense));

        new Chart(document.getElementById('invoiceChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        label: 'Pendapatan Terbayar (YTD)',
                        data: invoicePaidYTD,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Biaya / Pengeluaran (YTD)',
                        data: expenseYTD,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                ...chartOptions,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 10, usePointStyle: true } }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => 'Rp ' + (value/1000000).toFixed(1) + ' Jt'
                        }
                    }
                }
            }
        });

        // =======================================================
        // 3. EXPENSE PIE CHART (kategori biaya)
        // =======================================================
        const expenseLabels = @json($expenseCategories);
        const expenseData = @json($expenseCategoryTotals);
        const pieColors = ['#6f42c1', '#fd7e14', '#0dcaf0', '#e83e8c', '#dc3545', '#198754', '#ffc107', '#adb5bd'];

        new Chart(document.getElementById('expensePieChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: expenseLabels.length ? expenseLabels : ['Belum ada data'],
                datasets: [{
                    data: expenseLabels.length ? expenseData : [1],
                    backgroundColor: expenseLabels.length ? pieColors : ['#e9ecef']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, usePointStyle: true }
                    }
                }
            }
        });

        // =======================================================
        // 4. PO COMPARISON CHART (bar)
        // =======================================================
        const poCustomerMonthly = @json(array_values($monthlyPoCustomer)).map(v => v / 1000000);
        const poSupplierMonthly = @json(array_values($monthlyPoSupplier)).map(v => v / 1000000);

        new Chart(document.getElementById('poComparisonChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        label: 'PO Customer (Juta)',
                        data: poCustomerMonthly,
                        backgroundColor: '#0dcaf0',
                        borderRadius: 4
                    },
                    {
                        label: 'PO Supplier (Juta)',
                        data: poSupplierMonthly,
                        backgroundColor: '#6c757d',
                        borderRadius: 4
                    }
                ]
            },
            options: chartOptions
        });

        // =======================================================
        // 5. LOGISTIC CHART (line)
        // =======================================================
        const grMonthly = @json(array_values($monthlyGoodsReceipt));
        const doMonthly = @json(array_values($monthlyDeliveryOrder));

        new Chart(document.getElementById('logisticChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [
                    {
                        label: 'Volume Goods Receipt',
                        data: grMonthly,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Volume Delivery Order',
                        data: doMonthly,
                        borderColor: '#adb5bd',
                        backgroundColor: 'rgba(173, 181, 189, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: chartOptions
        });
    });
</script>
@endsection