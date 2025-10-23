<?= $this->extend('layouts/sbadmin_local') ?>

<?= $this->section('content') ?>

<div class="card mb-4 shadow-sm position-relative">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <span><i class="fas fa-qrcode me-2"></i> Generate QR Code Barang</span>
        <?php if (session('error')): ?>
            <span class="text-danger small"><?= esc(session('error')) ?></span>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <form method="get" class="mb-3 d-flex flex-wrap gap-2 align-items-center">
            <div class="input-group input-group-sm" style="max-width:300px;">
                <input type="text" name="q" class="form-control form-control-sm"
                    placeholder="Cari material / deskripsi..." value="<?= esc($q) ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <?php if ($q): ?>
                <a href="<?= base_url('admin/generate-qr') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> Reset
                </a>
            <?php endif; ?>
        </form>

        <form method="post" action="<?= base_url('admin/generate-qr') ?>" id="formGenerateQR">
            <?= csrf_field() ?>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:42px;" class="text-center">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Material</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($barang)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">Tidak ada data ditemukan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($barang as $b): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="barang_ids[]" value="<?= esc($b['id']) ?>">
                                    </td>
                                    <td><?= esc($b['material']) ?></td>
                                    <td><?= esc($b['material_description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center flex-wrap mt-2 small">
                    <div>
                        Halaman <?= $page ?> / <?= $totalPages ?> &nbsp;·&nbsp;
                        <?= $perPage ?> data ditampilkan &nbsp;·&nbsp;
                        Total <?= $total ?> data
                    </div>

                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&q=<?= esc($q) ?>">&laquo;</a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&q=<?= esc($q) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&q=<?= esc($q) ?>">&raquo;</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>
<button id="btnGenerate" class="btn btn-primary shadow position-fixed" style="top: 85px; right: 30px; z-index: 1000;">
    <i class="fas fa-qrcode me-1"></i> Generate
</button>

<script>
    document.getElementById('selectAll')?.addEventListener('change', function () {
        document.querySelectorAll('input[name="barang_ids[]"]').forEach(cb => cb.checked = this.checked);
    });
    document.getElementById('btnGenerate')?.addEventListener('click', function () {
        const checked = document.querySelectorAll('input[name="barang_ids[]"]:checked').length;
        if (checked === 0) {
            alert('Pilih minimal satu barang untuk generate QR.');
            return;
        }
        document.getElementById('formGenerateQR').submit();
    });
</script>

<?= $this->endSection() ?>