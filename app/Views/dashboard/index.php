<?= $this->extend('layouts/sbadmin_local') ?>

<?= $this->section('content') ?>
<div class="row g-3">
    <!-- Total Material -->
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">Total Material</div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <span id="stat-material">0</span>
                <a class="small text-white stretched-link" href="<?= base_url('admin/barang') ?>">View</a>
            </div>
        </div>
    </div>

    <!-- Peminjaman Storage -->
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">Peminjaman Storage</div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <span id="stat-peminjaman">0</span>
                <a class="small text-white stretched-link" href="<?= base_url('admin/peminjaman') ?>">View</a>
            </div>
        </div>
    </div>

    <!-- Stock Opname -->
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">Stock Opname</div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <span id="stat-stockopname">0</span>
                <a class="small text-white stretched-link" href <?= '="' . base_url('admin/opname') . '"' ?>>View</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <?= view('components/charts/material_chart') ?>
        </div>
        <div class="col-12">
            <?= view('components/charts/peminjaman_chart') ?>
        </div>
        <div class="col-12">
            <?= view('components/charts/stockopname_chart') ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (function () {
        const API = '<?= base_url('api/v1/stats/dashboard') ?>';
        const elMaterial = document.getElementById('stat-material');
        const elPinjam = document.getElementById('stat-peminjaman');
        const elSO = document.getElementById('stat-stockopname');

        fetch(API)
            .then(r => r.json())
            .then(j => {
                if (!j.success) throw new Error(j?.error?.message || 'Gagal load statistik');
                const d = j.data || {};
                elMaterial && (elMaterial.textContent = (d.material_total ?? 0).toLocaleString('id-ID'));
                elPinjam && (elPinjam.textContent = (d.peminjaman_total ?? 0).toLocaleString('id-ID'));
                elSO && (elSO.textContent = (d.stock_opname_total ?? 0).toLocaleString('id-ID'));
            })
            .catch(err => console.warn('stats error:', err.message));
    })();
</script>
<?= $this->endSection() ?>