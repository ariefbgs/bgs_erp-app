<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BGS ERP - @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body { overflow-x: hidden; }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            transition: all 0.3s;
        }

        /* Responsive Sidebar */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -250px; /* Sembunyikan sidebar di kiri */
                z-index: 1000;
                width: 250px;
            }
            .sidebar.show {
                left: 0;
            }
            .content {
                padding: 15px !important;
            }
        }

        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: 0.3s;
            font-size: 0.95rem;
        }
        .sidebar a:hover, .sidebar a.active {
            color: #fff;
            background-color: #007bff;
        }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; }

        /* Tampilan Mobile friendly untuk card/container */
        .card { border-radius: 10px; border: none; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="{{ url('/dashboard') }}">
                    {{ config('app.name', 'ERP System') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" id="sidebarToggle">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">Login</a>
                            </li>
                        @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    {{ Auth::user()->name }}
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        Logout
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-2 p-0 sidebar">
                    <div class="nav flex-column">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>

                        <!-- Master Data -->
                        <div class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#masterDataMenu" role="button">
                                <i class="fas fa-database"></i> Master Data <i class="fas fa-chevron-down float-end"></i>
                            </a>
                            <div class="collapse {{ request()->routeIs('customers.*') || request()->routeIs('suppliers.*') || request()->routeIs('products.*') || request()->routeIs('companies.*') ? 'show' : '' }}" id="masterDataMenu">
                                <div class="ms-3">
                                    <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                                        <i class="fas fa-users"></i> Customers
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                                        <i class="fas fa-truck"></i> Suppliers
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                                        <i class="fas fa-box"></i> Products
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}">
                                        <i class="fas fa-building"></i> Company
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Transaksi -->
                        <div class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#transaksiMenu" role="button">
                                <i class="fas fa-exchange-alt"></i> Transaksi <i class="fas fa-chevron-down float-end"></i>
                            </a>
                            <div class="collapse {{ request()->routeIs('quotations.*') || request()->routeIs('po-customers.*') || request()->routeIs('po-suppliers.*') ? 'show' : '' }}" id="transaksiMenu">
                                <div class="ms-3">
                                    <a class="nav-link {{ request()->routeIs('quotations.*') ? 'active' : '' }}" href="{{ route('quotations.index') }}">
                                        <i class="fas fa-file-alt"></i> Quotation
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('po-customers.*') ? 'active' : '' }}" href="{{ route('po-customers.index') }}">
                                        <i class="fas fa-shopping-cart"></i> PO Customer
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('po-suppliers.*') ? 'active' : '' }}" href="{{ route('po-suppliers.index') }}">
                                        <i class="fas fa-truck-loading"></i> PO Supplier
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Warehouse -->
                        <div class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#warehouseMenu" role="button">
                                <i class="fas fa-warehouse"></i> Warehouse <i class="fas fa-chevron-down float-end"></i>
                            </a>
                            <div class="collapse {{ request()->routeIs('goods-receipts.*') ? 'show' : '' }}" id="warehouseMenu">
                                <div class="ms-3">
                                    <a class="nav-link {{ request()->routeIs('goods-receipts.*') ? 'active' : '' }}" href="{{ route('goods-receipts.index') }}">
                                        <i class="fas fa-dolly"></i> Goods Receipt
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Customer -->
                        <div class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#invoiceMenu" role="button">
                                <i class="fas fa-file-invoice"></i> Sales Invoice <i class="fas fa-chevron-down float-end"></i>
                            </a>
                            <div class="collapse {{ request()->routeIs('invoice-customers.*') ? 'show' : '' }}" id="invoiceMenu">
                                <div class="ms-3">
                                    <a class="nav-link {{ request()->routeIs('invoice-customers.*') ? 'active' : '' }}" href="{{ route('invoice-customers.index') }}">
                                        <i class="fas fa-dolly"></i> Sales Invoice
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- Delivery Order -->
                        <div class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#deliveryMenu" role="button">
                                <i class="fas fa-truck"></i> Delivery Order <i class="fas fa-chevron-down float-end"></i>
                            </a>
                            <div class="collapse {{ request()->routeIs('delivery-orders.*') || request()->routeIs('po-customers.*') || request()->routeIs('po-suppliers.*')? 'show' : '' }}" id="deliveryMenu">
                                <div class="ms-3">
                                    <a class="nav-link {{ request()->routeIs('delivery-orders.*') ? 'active' : '' }}" href="{{ route('delivery-orders.index') }}">
                                        <i class="fas fa-dolly"></i> Delivery Order
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('document-receipts.*') ? 'active' : '' }}" href="{{ route('document-receipts.index') }}">
                                        <i class="fas fa-dolly"></i> Document Receipt
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Expense -->
                        <div class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#expenseMenu" role="button">
                                <i class="fas fa-file-alt"></i> Expense <i class="fas fa-chevron-down float-end"></i>
                            </a>
                            <div class="collapse {{ request()->routeIs('expense.*') ? 'show' : '' }}" id="expenseMenu">
                                <div class="ms-3">
                                    <a class="nav-link {{ request()->routeIs('expense.*') ? 'active' : '' }}" href="{{ route('expense.index') }}">
                                        <i class="fas fa-dolly"></i> Expense
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- Backup & Restore -->
                        <div class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#backupMenu" role="button">
                                <i class="fas fa-database"></i> Backup & Restore <i class="fas fa-chevron-down float-end"></i>
                            </a>
                            <div class="collapse {{ request()->routeIs('backup.*') ? 'show' : '' }}" id="backupMenu">
                                <div class="ms-3">
                                    <a class="nav-link {{ request()->routeIs('backup.*') ? 'active' : '' }}" href="{{ route('backup.index') }}">
                                        <i class="fas fa-dolly"></i> Backup & Restore
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-md-10 content">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    </script>
    @stack('scripts')
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.10.0/dist/autoNumeric.min.js"></script>
</body>
</html>
