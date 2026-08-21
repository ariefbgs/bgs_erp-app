<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem ERP - Beranda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-section {
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 80px 0;
        }
        .features-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            transition: all 0.25s ease-in-out;
            text-decoration: none !important;
        }
        .features-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0,0,0,0.05);
            border-color: #cbd5e1;
        }
        .icon-circle {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom sticky-top">
        <div class="container py-1">
            <a class="navbar-brand fw-bold text-dark text-uppercase tracking-wider" href="#">
                <i class="bi bi-box-seam-fill text-primary me-2"></i>Enterprise ERP
            </a>
            <div class="d-flex align-items-center">
                <span class="text-muted me-3 small d-none d-sm-inline">
                    <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }} ({{ Auth::user()->email }})
                </span>
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="btn btn-sm btn-outline-danger fw-semibold">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </nav>

    <header class="hero-section text-center text-md-start">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-7 mb-4 mb-md-0">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold mb-3">Sistem Terintegrasi v1.0</span>
                    <h1 class="display-5 fw-bold text-dark mb-3">Selamat Datang di Portal Kerja BGS ERP</h1>
                    <p class="lead text-muted mb-4">Akses semua modul manajemen operasional, logistik, pengeluaran, hingga penerbitan dokumen resmi perusahaan BGS dalam satu platform terpadu.</p>
                    <div class="d-flex gap-2 justify-content-center justify-content-md-start">
                        <a href="#modul-kerja" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">Selamat Bekerja <i class="bi bi-arrow-right ms-1"></i></a>
                    </div>
                </div>
                <div class="col-md-5 text-center d-none d-md-block">
                    <div class="p-4 bg-white rounded-4 shadow-sm border border-light text-center">
                        <i class="bi bi-laptop text-primary" style="font-size: 5rem;"></i>
                        <h6 class="fw-bold mt-2 mb-0">Aktivitas Terbuka</h6>
                        <p class="text-muted small">Semua modul siap digunakan sesuai hak akses Anda.</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-5" id="modul-kerja">
        <div class="text-center mb-5">
            <h3 class="fw-bold text-dark">Pilih Modul Kerja Anda</h3>
            <p class="text-muted">Klik kartu di bawah ini untuk langsung menuju manajemen data operasional terkait.</p>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <div class="col">
                <a href="{{ route('customers.index') }}" class="card h-100 p-4 features-card">
                    <div class="icon-circle bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></div>
                    <h5 class="fw-bold text-dark mb-1">Pelanggan</h5>
                    <p class="text-muted small mb-0">Manajemen database customer & cetak alamat.</p>
                </a>
            </div>

            <div class="col">
                <a href="{{ route('quotations.index') }}" class="card h-100 p-4 features-card">
                    <div class="icon-circle bg-success bg-opacity-10 text-success"><i class="bi bi-file-earmark-text"></i></div>
                    <h5 class="fw-bold text-dark mb-1">Quotation</h5>
                    <p class="text-muted small mb-0">Pembuatan, kalkulasi harga, dan cetak penawaran.</p>
                </a>
            </div>

            <div class="col">
                <a href="{{ route('po-customers.index') }}" class="card h-100 p-4 features-card">
                    <div class="icon-circle bg-info bg-opacity-10 text-info"><i class="bi bi-cart-check"></i></div>
                    <h5 class="fw-bold text-dark mb-1">PO Customer</h5>
                    <p class="text-muted small mb-0">Pencatatan pesanan resmi dari pihak pelanggan.</p>
                </a>
            </div>

            <div class="col">
                <a href="{{ route('delivery-orders.index') }}" class="card h-100 p-4 features-card">
                    <div class="icon-circle bg-warning bg-opacity-10 text-warning"><i class="bi bi-truck"></i></div>
                    <h5 class="fw-bold text-dark mb-1">Delivery Order</h5>
                    <p class="text-muted small mb-0">Pemantauan surat jalan & logistik pengiriman.</p>
                </a>
            </div>

            <div class="col">
                <a href="{{ route('invoice-customers.index') }}" class="card h-100 p-4 features-card">
                    <div class="icon-circle bg-dark bg-opacity-10 text-dark"><i class="bi bi-receipt"></i></div>
                    <h5 class="fw-bold text-dark mb-1">Invoice Tagihan</h5>
                    <p class="text-muted small mb-0">Penerbitan faktur pembayaran & status keuangan.</p>
                </a>
            </div>

            <div class="col">
                <a href="{{ route('expenses.index') }}" class="card h-100 p-4 features-card">
                    <div class="icon-circle bg-danger bg-opacity-10 text-danger"><i class="bi bi-credit-card"></i></div>
                    <h5 class="fw-bold text-dark mb-1">Biaya & Pengeluaran</h5>
                    <p class="text-muted small mb-0">Pencatatan klaim pengeluaran kas operasional.</p>
                </a>
            </div>

            <div class="col">
                <a href="{{ route('reports.index') }}" class="card h-100 p-4 features-card">
                    <div class="icon-circle bg-purple bg-opacity-10 text-purple" style="color: #6f42c1; background-color: rgba(111,66,193,0.1);"><i class="bi bi-journal-check"></i></div>
                    <h5 class="fw-bold text-dark mb-1">Laporan</h5>
                    <p class="text-muted small mb-0">Unduh data rekapitulasi sales & purchasing.</p>
                </a>
            </div>
        </div>
    </main>

    <footer class="bg-white border-top py-3 mt-5 text-center text-muted small">
        <p class="mb-0">&copy; {{ date('Y') }} Enterprise ERP System. Hak Cipta Dilindungi.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>