<?= $this->extend('layouts/sbadmin_local') ?>

<?= $this->section('content') ?>
<div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-qrcode me-2"></i> Hasil Generate QR</span>
        <div class="d-flex gap-2">

            <a href="<?= base_url('admin/generate-qr') ?>" class="btn btn-secondary btn-sm">
                ← Kembali
            </a>

            <form method="post" action="<?= base_url('admin/generate-qr/download-zpl') ?>">
                <input type="hidden" name="zplData" value="<?= htmlspecialchars($zplData) ?>">
                <button class="btn btn-success btn-sm">
                    <i class="fas fa-download me-1"></i> Download ZPL
                </button>
            </form>

        </div>
    </div>

    <div class="card-body">
        <div class="qr-grid">
            <?php foreach ($labels as $l): ?>
                <div class="qr-card">
                    <div class="qr-title"><?= esc($l['material']) ?></div>
                    <img src="<?= $l['dataUri'] ?>" alt="QR <?= esc($l['material']) ?>">
                    <div class="qr-desc"><?= esc($l['desc']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
    .qr-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8mm;
    }

    .qr-card {
        border: 1px dashed #ccc;
        border-radius: 6px;
        padding: 6mm 4mm;
        text-align: center;
    }

    .qr-title {
        font-weight: 400;
        font-size: 6pt;
        margin-bottom: 3mm;
    }

    .qr-desc {
        font-size: 6pt;
        color: #666;
        margin-top: 2mm;
    }

    .qr-card img {
        width: 15mm;
        height: 15mm;
        object-fit: contain;
    }
</style>

<?= $this->endSection() ?>