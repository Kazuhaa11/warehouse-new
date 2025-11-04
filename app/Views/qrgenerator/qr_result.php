<?= $this->extend('layouts/sbadmin_local') ?>

<?= $this->section('content') ?>
<div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-qrcode me-2"></i> Hasil Generate QR</span>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/generate-qr') ?>" class="btn btn-secondary btn-sm">
                ← Kembali
            </a>
            <button id="btnPrint" class="btn btn-success btn-sm">
                <i class="fas fa-print me-1"></i> Print
            </button>
        </div>
    </div>

    <div class="card-body">
        <div id="printArea" class="qr-grid">
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
        break-inside: avoid;
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

    @page {
        size: A4;
        margin: 5mm;
    }

    @media print {
        body * {
            visibility: hidden !important;
        }

        #printArea,
        #printArea * {
            visibility: visible !important;
        }

        #printArea {
            position: absolute;
            left: 0;
            top: 0;
            width: calc(210mm - 20mm); 
        }

        .qr-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 8mm;
        }

        .qr-card {
            page-break-inside: avoid;
        }

        .card-header,
        .btn,
        .card-footer {
            display: none !important;
        }
    }
</style>

<script>
    document.getElementById('btnPrint')?.addEventListener('click', () => {
        window.print();
    });
</script>
<?= $this->endSection() ?>