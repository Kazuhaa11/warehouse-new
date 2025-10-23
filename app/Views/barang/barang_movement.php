<?= $this->extend('layouts/sbadmin_local') ?>

<?= $this->section('content') ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <i class="fas fa-chart-bar me-2"></i> Fast / Slow / Dead Movement
        </div>
        <div class="d-flex gap-2 align-items-center">
            <select id="filterType" class="form-select form-select-sm" style="min-width:150px;">
                <option value="">All Type</option>
                <option value="fast">Fast Moving</option>
                <option value="slow">Slow Moving</option>
                <option value="dead">Dead Item</option>
            </select>

            <select id="filterMonth" class="form-select form-select-sm" style="min-width:160px;">
                <option value="">All Month</option>
                <?php
                $months = [
                    'Jan',
                    'Feb',
                    'Mar',
                    'Apr',
                    'May',
                    'Jun',
                    'Jul',
                    'Aug',
                    'Sep',
                    'Oct',
                    'Nov',
                    'Dec'
                ];
                $year = date('Y');
                foreach ($months as $m) {
                    echo "<option value='{$m} {$year}'>{$m} {$year}</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle" id="tblMovement">
                <thead class="table-light">
                    <tr class="text-center">
                        <th>Material</th>
                        <th>Deskripsi</th>
                        <th>Plant</th>
                        <th>Stor. Loc</th>
                        <th>Stor. Loc Desc</th>
                        <th class="text-end">Unrestricted</th>
                        <th class="text-end">Keluar (Bulan)</th>
                        <th class="text-end">Turnover Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Pilih filter untuk menampilkan data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (function () {
        const tbody = document.querySelector('#tblMovement tbody');
        const typeSel = document.getElementById('filterType');
        const monthSel = document.getElementById('filterMonth');

        async function loadMovement() {
            const type = typeSel.value;
            const month = monthSel.value;

            if (!type && !month) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">Silakan lakukan filter terlebih dahulu</td></tr>`;
                return;
            }

            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">Loading...</td></tr>`;

            try {
                const url = new URL("<?= base_url('api/v1/barang/movement-list') ?>");
                if (type) url.searchParams.append('type', type);
                if (month) url.searchParams.append('month', month);

                const res = await fetch(url);
                const json = await res.json();

                if (!json.success) throw new Error('Response error');

                const rows = json.data || [];

                if (rows.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">Tidak ada data untuk filter ini</td></tr>`;
                    return;
                }

                tbody.innerHTML = rows.map(r => {
                    const turnover = parseFloat(r.turnover || 0); // pastikan angka
                    return `
          <tr>
            <td>${r.material ?? '-'}</td>
            <td>${r.material_description ?? '-'}</td>
            <td>${r.plant ?? '-'}</td>
            <td>${r.storage_location ?? '-'}</td>
            <td>${r.storage_location_desc ?? '-'}</td>
            <td class="text-end">${r.qty_unrestricted ?? 0}</td>
            <td class="text-end">${r.total_keluar ?? 0}</td>
            <td class="text-end">${turnover.toFixed(2)}</td>
          </tr>
        `;
                }).join('');
            } catch (err) {
                console.error(err);
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Gagal memuat data</td></tr>`;
            }
        }

        typeSel.addEventListener('change', loadMovement);
        monthSel.addEventListener('change', loadMovement);

        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">Silakan lakukan filter terlebih dahulu</td></tr>`;
    })();
</script>
<?= $this->endSection() ?>