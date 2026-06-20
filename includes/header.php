<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poultry Shop POS - <?= $page_title ?? 'Dashboard' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="/poultry_shop/assets/css/sb-admin-custom.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <style>
        /* Sticky Topbar Styles */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08) !important;
            transition: all 0.3s ease;
            padding: 0.5rem 1.5rem;
        }
        
        /* Add shadow on scroll */
        .topbar.scrolled {
            box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
        }
        
        /* Ensure content doesn't hide behind sticky header */
        #content {
            padding-top: 0;
        }
        
        /* Smooth transition for the header */
        .topbar .navbar-brand,
        .topbar .nav-link,
        .topbar .dropdown-toggle {
            transition: all 0.3s ease;
        }
        
        /* Add a subtle background blur effect when scrolling */
        .topbar.scrolled {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98) !important;
        }
        
        /* Topbar brand text */
        .topbar .navbar-brand {
            font-weight: 600;
            color: #059669;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .topbar {
                padding: 0.5rem 1rem;
            }
            
            .topbar .d-none.d-sm-inline-block {
                display: none !important;
            }
        }
        
        /* Optional: Add a progress bar at top of header */
        .topbar-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(to right, #059669, #10b981);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }
        
        .topbar.scrolled .topbar-progress {
            transform: scaleX(1);
        }
    </style>
</head>
<body id="page-top">

<?php
$expCust = isSectionActive('customers') ? 'show' : '';
$expSupp = isSectionActive('suppliers') ? 'show' : '';
$expFin  = isSectionActive('finance') ? 'show' : '';
$expReports = isSectionActive('reports') ? 'show' : '';
$expStock = isSectionActive('stock') ? 'show' : '';
$collapsed = function($exp) { return $exp ? '' : 'collapsed'; };
$expanded  = function($exp) { return $exp ? 'true' : 'false'; };
?>
<?php
date_default_timezone_set('Asia/Karachi');
?>
<div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar"><div class="sidebar-inner">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="/poultry_shop/dashboard/index.php">
            <div class="sidebar-brand-icon">
                <i class="fas fa-drumstick-bite"></i>
            </div>
            <div class="sidebar-brand-text mx-2">Poultry Shop</div>
        </a>

        <hr class="sidebar-divider my-0">

        <li class="nav-item <?= navActive('index.php') && strpos($_SERVER['REQUEST_URI'], '/dashboard/') ? 'active' : '' ?>">
            <a class="nav-link" href="/poultry_shop/dashboard/index.php">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <hr class="sidebar-divider">
        <div class="sidebar-heading">POS</div>

        <li class="nav-item <?= navActiveDir('pos') ? 'active' : '' ?>">
            <a class="nav-link" href="/poultry_shop/pos/index.php">
                <i class="fas fa-fw fa-cash-register"></i>
                <span>POS Sale</span>
            </a>
        </li>

        <hr class="sidebar-divider">
        <div class="sidebar-heading">Management</div>

        <li class="nav-item <?= navActiveDir('chicken_types') ? 'active' : '' ?>">
            <a class="nav-link" href="/poultry_shop/chicken_types/index.php">
                <i class="fas fa-fw fa-drumstick"></i>
                <span>Chicken Types</span>
            </a>
        </li>
        <li class="nav-item <?= navActiveDir('chicken_rates') ? 'active' : '' ?>">
            <a class="nav-link" href="/poultry_shop/chicken_rates/index.php">
                <i class="fas fa-fw fa-tags"></i>
                <span>Chicken Rates</span>
            </a>
        </li>

        <!-- Customers Accordion -->
        <li class="nav-item">
            <a class="nav-link <?= $collapsed($expCust) ?>" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCustomers" aria-expanded="<?= $expanded($expCust) ?>">
                <i class="fas fa-fw fa-users"></i>
                <span>Customers</span>
            </a>
            <div id="collapseCustomers" class="collapse <?= $expCust ?>" data-bs-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/customers/') ? 'active' : '' ?>" href="/poultry_shop/customers/index.php">View Customers</a>
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'ledger.php' && strpos($_SERVER['REQUEST_URI'], '/customers/') ? 'active' : '' ?>" href="/poultry_shop/customers/ledger.php">Customer Ledger</a>
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'create.php' && strpos($_SERVER['REQUEST_URI'], '/customers/') ? 'active' : '' ?>" href="/poultry_shop/customers/create.php">Create Customer</a>
                </div>
            </div>
        </li>

        <!-- Suppliers Accordion -->
        <li class="nav-item">
            <a class="nav-link <?= $collapsed($expSupp) ?>" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSuppliers" aria-expanded="<?= $expanded($expSupp) ?>">
                <i class="fas fa-fw fa-truck"></i>
                <span>Suppliers</span>
            </a>
            <div id="collapseSuppliers" class="collapse <?= $expSupp ?>" data-bs-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/suppliers/') ? 'active' : '' ?>" href="/poultry_shop/suppliers/index.php">View Suppliers</a>
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'ledger.php' && strpos($_SERVER['REQUEST_URI'], '/suppliers/') ? 'active' : '' ?>" href="/poultry_shop/suppliers/ledger.php">Supplier Ledger</a>
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/supplier_payments/') ? 'active' : '' ?>" href="/poultry_shop/supplier_payments/index.php">Supplier Payments</a>
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'create.php' && strpos($_SERVER['REQUEST_URI'], '/suppliers/') ? 'active' : '' ?>" href="/poultry_shop/suppliers/create.php">Create Supplier</a>
                </div>
            </div>
        </li>

        <li class="nav-item <?= navActiveDir('purchases') ? 'active' : '' ?>">
            <a class="nav-link" href="/poultry_shop/purchases/index.php">
                <i class="fas fa-fw fa-shopping-cart"></i>
                <span>Purchases</span>
            </a>
        </li>
        <li class="nav-item <?= navActiveDir('sales') ? 'active' : '' ?>">
            <a class="nav-link" href="/poultry_shop/sales/index.php">
                <i class="fas fa-fw fa-file-invoice"></i>
                <span>Sales</span>
            </a>
        </li>
        <li class="nav-item <?= navActiveDir('payments') ? 'active' : '' ?>">
            <a class="nav-link" href="/poultry_shop/payments/index.php">
                <i class="fas fa-fw fa-money-bill-wave"></i>
                <span>Payments</span>
            </a>
        </li>
        <li class="nav-item <?= navActiveDir('expenses') ? 'active' : '' ?>">
            <a class="nav-link" href="/poultry_shop/expenses/index.php">
                <i class="fas fa-fw fa-coins"></i>
                <span>Expenses</span>
            </a>
        </li>
        <!-- Stock Accordion -->
        <li class="nav-item">
            <a class="nav-link <?= $collapsed($expStock) ?>" href="#" data-bs-toggle="collapse" data-bs-target="#collapseStock" aria-expanded="<?= $expanded($expStock) ?>">
                <i class="fas fa-fw fa-warehouse"></i>
                <span>Stock</span>
            </a>
            <div id="collapseStock" class="collapse <?= $expStock ?>" data-bs-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'summary.php' && strpos($_SERVER['REQUEST_URI'], '/stock/') ? 'active' : '' ?>" href="/poultry_shop/stock/summary.php">Stock Summary</a>
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/stock/') ? 'active' : '' ?>" href="/poultry_shop/stock/index.php">Stock Ledger</a>
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'manage.php' && strpos($_SERVER['REQUEST_URI'], '/stock/') ? 'active' : '' ?>" href="/poultry_shop/stock/manage.php">Management</a>
                </div>
            </div>
        </li>

        <hr class="sidebar-divider">
        <div class="sidebar-heading">Finance</div>

        <!-- Finance Accordion -->
        <li class="nav-item">
            <a class="nav-link <?= $collapsed($expFin) ?>" href="#" data-bs-toggle="collapse" data-bs-target="#collapseFinance" aria-expanded="<?= $expanded($expFin) ?>">
                <i class="fas fa-fw fa-book"></i>
                <span>Cash & Bank</span>
            </a>
            <div id="collapseFinance" class="collapse <?= $expFin ?>" data-bs-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item <?= strpos($_SERVER['REQUEST_URI'], '/cash_book') !== false ? 'active' : '' ?>" href="/poultry_shop/reports/cash_book.php">Cash Book</a>
                    <a class="collapse-item <?= strpos($_SERVER['REQUEST_URI'], '/bank_book') !== false ? 'active' : '' ?>" href="/poultry_shop/reports/bank_book.php">Bank Book</a>
                </div>
            </div>
        </li>

        <hr class="sidebar-divider">
        <div class="sidebar-heading">Reports</div>

        <!-- Reports Accordion -->
        <li class="nav-item">
            <a class="nav-link <?= $collapsed($expReports) ?>" href="#" data-bs-toggle="collapse" data-bs-target="#collapseReports" aria-expanded="<?= $expanded($expReports) ?>">
                <i class="fas fa-fw fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <div id="collapseReports" class="collapse <?= $expReports ?>" data-bs-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'daily_sales.php' && strpos($_SERVER['REQUEST_URI'], '/reports/') ? 'active' : '' ?>" href="/poultry_shop/reports/daily_sales.php">Daily Report</a>
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'profit_loss.php' && strpos($_SERVER['REQUEST_URI'], '/reports/') ? 'active' : '' ?>" href="/poultry_shop/reports/profit_loss.php">Profit & Loss Report</a>
                    <a class="collapse-item <?= basename($_SERVER['PHP_SELF']) === 'stock_report.php' && strpos($_SERVER['REQUEST_URI'], '/reports/') ? 'active' : '' ?>" href="/poultry_shop/reports/stock_report.php">Stock Report</a>
                </div>
            </div>
        </li>

        <hr class="sidebar-divider d-none d-md-block">

        <div class="text-center d-none d-md-block">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>
        </div><!-- .sidebar-inner -->
    </ul>
    <!-- End Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <!-- Topbar - NOW STICKY -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow" id="stickyTopbar">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle me-3">
                    <i class="fa fa-bars"></i>
                </button>

                <div class="d-none d-sm-inline-block fw-bold text-muted small">
                    <i class="fas fa-calendar-alt me-1"></i> <?= date('l, d M Y') ?>
                </div> 

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <span class="me-2 text-gray-600 small">
                                <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
                                <span class="badge bg-<?= isAdmin() ? 'danger' : 'primary' ?> ms-1"><?= ucfirst($_SESSION['user_role'] ?? '') ?></span>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in">
                            <a class="dropdown-item" href="/poultry_shop/auth/logout.php">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i> Logout
                            </a>
                        </div>
                    </li>
                </ul>
                
                <!-- Optional: Progress bar effect -->
                <div class="topbar-progress"></div>
            </nav>
            <!-- End Topbar -->

            <!-- Begin Page Content -->
            <div class="container-fluid">
<?php
$flash = flashMessage();
if ($flash):
?>
<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
    <i class="fas fa-check-circle me-2"></i> <?= $flash ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>