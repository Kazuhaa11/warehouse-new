<?php

use App\Libraries\Auth as AuthCtx;

$menu = $menu ?? '';
$title = $title ?? 'Warehouse';
$request = service('request');

$user = AuthCtx::user();
$role = strtolower((string)($user['role'] ?? ''));
$isSuperAdmin = ($role === 'super_admin');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= esc($title) ?> · Warehouse</title>

    <link rel="stylesheet" href="<?= base_url('assets/bootstrap/css/bootstrap.min.css') ?>">

    <link href="<?= base_url('sbadmin/css/styles.css') ?>" rel="stylesheet" />
    <link rel="stylesheet" href="<?= base_url('fontawesome/css/all.min.css') ?>">
</head>

<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-3" id="sidebarToggle"><i
                class="fas fa-bars"></i></button>
        <a class="navbar-brand ps-3" href="<?= base_url('admin/dashboard') ?>">Warehouse</a>
        <ul class="navbar-nav ms-auto me-3">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><i
                        class="fas fa-user fa-fw"></i></a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="<?= base_url('admin/change-password') ?>">
                            <i class="fas fa-key me-2"></i> Ubah Password
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="#" id="btnLogout">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>

    <div id="layoutSidenav">
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
                            <div class="sb-nav-link-icon"><i class="fas fa-file-invoice"></i></div>Bon Pinjam
                        </a>
                        <a class="nav-link <?= $menu === 'reservasi' ? 'active' : '' ?>"
                            href="<?= base_url('admin/reservasi') ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-file-invoice"></i></div>Reservasi List
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
                        <a class="nav-link <?= $menu === 'movement' ? 'active' : '' ?>"
                            href="<?= base_url('admin/movement') ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>Pergerakan Barang
                        </a>
                        <a class="nav-link <?= $menu === 'generateqr' ? 'active' : '' ?>"
                            href="<?= base_url('admin/generate-qr') ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-qrcode"></i></div>Generate QR Barang
                        </a>
                        <a class="nav-link <?= $menu === 'stockpredict' ? 'active' : '' ?>"
                            href="<?= base_url('admin/stock-predict-view') ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>Stock Predict
                        </a>
                        <?php if ($isSuperAdmin): ?>
                            <a class="nav-link <?= $menu === 'users' ? 'active' : '' ?>"
                                href="<?= base_url('admin/users') ?>">
                                <div class="sb-nav-link-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                Manajemen User
                            </a>
                        <?php endif; ?>
                        <a class="nav-link" href="<?= base_url('admin/download/manual-book') ?>">
                            <div class="sb-nav-link-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            Manual Book
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Logged in as:</div>
                    <?= esc(ucwords(str_replace('_', ' ', $role))) ?>
                </div>
            </nav>
        </div>

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

    <script src="<?= base_url('assets/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('sbadmin/js/scripts.js') ?>"></script>


    <?= $this->renderSection('scripts') ?>
</body>

</html>