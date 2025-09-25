<?php
$menu = $menu ?? '';
$title = $title ?? 'Warehouse';
$request = service('request');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= esc($title) ?> · Warehouse</title>

    <!-- SB Admin CSS (dari ZIP lokal) -->
    <link href="<?= base_url('sbadmin/css/styles.css') ?>" rel="stylesheet" />

    <!-- Font Awesome & Bootstrap via CDN (boleh diganti lokal kalau mau) -->
    <script src="https://use.fontawesome.com/releases/v6.5.2/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@9.0.0/dist/style.min.css" rel="stylesheet">
</head>

<body class="sb-nav-fixed">
    <!-- Top Navbar -->
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-3" id="sidebarToggle"><i
                class="fas fa-bars"></i></button>
        <a class="navbar-brand ps-3" href="<?= base_url('admin/dashboard') ?>">Warehouse</a>

        <!-- Search -->
        <form class="d-none d-md-inline-block ms-auto me-3" action="<?= base_url('admin/barang') ?>" method="get">
            <div class="input-group input-group-sm">
                <input class="form-control" type="text" name="q" placeholder="Cari Material / Deskripsi"
                    value="<?= esc($request->getGet('q') ?? '') ?>" />
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <!-- User -->
        <ul class="navbar-nav me-3">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><i
                        class="fas fa-user fa-fw"></i></a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#">Profile</a></li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item" href="#" id="btnLogout">Logout</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <div id="layoutSidenav">
        <!-- Sidebar -->
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Main</div>
                        <a class="nav-link <?= $menu === 'dashboard' ? 'active' : '' ?>"
                            href="<?= base_url('admin/dashboard') ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Dashboard
                        </a>
                        <a class="nav-link <?= $menu === 'barang' ? 'active' : '' ?>"
                            href="<?= base_url('admin/barang') ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>Barang
                        </a>
                        <a class="nav-link <?= $menu === 'peminjaman' ? 'active' : '' ?>"
                            href="<?= base_url('admin/peminjaman') ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-file-invoice"></i></div>Peminjaman
                        </a>
                        <a class="nav-link <?= $menu === 'opname' ? 'active' : '' ?>"
                            href="<?= base_url('admin/opname') ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-clipboard-check"></i></div>Stock Opname
                        </a>
                        <a class="nav-link <?= $menu === 'storages' ? 'active' : '' ?>"
                            href="<?= base_url('admin/storages') ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-th-large"></i></div>Storages
                        </a>
                        <a class="nav-link <?= $menu === 'import' ? 'active' : '' ?>"
                            href="<?= base_url('admin/import-export') ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-file-excel"></i></div>Import / Export
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Logged in as:</div> Admin
                </div>
            </nav>
        </div>

        <!-- Content -->
        <div id="layoutSidenav_content">
            <main class="container-fluid px-4 py-3">
                <h1 class="mt-2 mb-3"><?= esc($title) ?></h1>
                <?= $this->renderSection('content') ?>
            </main>
            <footer class="py-3 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">© <?= date('Y') ?> Warehouse System</div>
                        <div><a href="#">Privacy</a> · <a href="#">Terms</a></div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="<?= base_url('js/api.js') ?>"></script>
    <script>
        document.getElementById('btnLogout')?.addEventListener('click', (e) => {
            e.preventDefault();
            apiLogout({
                endpoint: "<?= base_url('api/v1/auth/logout') ?>",
                redirectUrl: "<?= base_url('/') ?>"
            });
        });
    </script>

    <!-- JS vendor (CDN seperti di template asli) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4"></script>

    <!-- SB Admin JS (dari ZIP lokal) -->
    <script src="<?= base_url('sbadmin/js/scripts.js') ?>"></script>

    <?= $this->renderSection('scripts') ?>
</body>

</html>