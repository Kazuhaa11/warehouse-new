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
                        <th class="text-end">Stok</th>
                        <th class="text-end">Keluar (Bulan)</th>
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
    (function() {

        const tbody = document.querySelector('#tblMovement tbody');
        const typeSel = document.getElementById('filterType');
        const monthSel = document.getElementById('filterMonth');

        const pagination = document.createElement('div');
        pagination.className = "d-flex justify-content-between align-items-center flex-wrap mt-2 small";
        document.querySelector('.card-body').appendChild(pagination);

        const API_URL = "<?= base_url('api/v1/barang/movement-list') ?>";

        function getRowClass(type) {
            if (!type) return "";
            type = type.toLowerCase();
            if (type === "fast") return "table-success";
            if (type === "slow") return "table-warning";
            if (type === "dead") return "table-danger";
            return "";
        }

        async function loadMovement(page = 1) {
            const type = typeSel.value;
            const month = monthSel.value;

            if (!type && !month) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        Silakan pilih filter terlebih dahulu
                    </td>
                </tr>`;
                pagination.innerHTML = '';
                return;
            }

            tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted">
                    Memuat data...
                </td>
            </tr>`;
            pagination.innerHTML = '';

            try {
                const url = new URL(API_URL);

                if (type) url.searchParams.append('type', type);
                if (month) url.searchParams.append('month', month);

                url.searchParams.append('page', page);
                url.searchParams.append('per_page', 25);

                const res = await fetch(url);
                const json = await res.json();

                if (!json.success) throw new Error("Response error");

                const rows = json.data || [];
                const meta = json.meta || {
                    page: 1,
                    total_pages: 1,
                    total: rows.length
                };

                if (rows.length === 0) {
                    tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center text-muted">Tidak ada data</td>
                    </tr>`;
                    pagination.innerHTML = '';
                    return;
                }

                tbody.innerHTML = rows.map((r) => {
                    const className = getRowClass(typeSel.value);

                    return `
                    <tr class="${className}">
                        <td>${r.material ?? '-'}</td>
                        <td>${r.material_description ?? '-'}</td>
                        <td>${r.plant ?? '-'}</td>
                        <td>${r.storage_location ?? '-'}</td>
                        <td>${r.storage_location_desc ?? '-'}</td>
                        <td class="text-end">${Number(r.qty_unrestricted || 0).toLocaleString()}</td>
                        <td class="text-end fw-bold">${Number(r.total_keluar || 0).toLocaleString()}</td>
                    </tr>
                `;
                }).join('');

                let pagHTML = `
                <div>
                    Halaman ${meta.page} / ${meta.total_pages}  
                    • Total ${meta.total} data
                </div>
                <nav><ul class="pagination pagination-sm mb-0">
            `;

                if (meta.page > 1) {
                    pagHTML += `
                    <li class="page-item"><a class="page-link" href="#" data-page="${meta.page - 1}">
                        &laquo;
                    </a></li>`;
                }

                const start = Math.max(1, meta.page - 2);
                const end = Math.min(meta.total_pages, meta.page + 2);

                for (let i = start; i <= end; i++) {
                    pagHTML += `
                    <li class="page-item ${i === meta.page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>`;
                }

                if (meta.page < meta.total_pages) {
                    pagHTML += `
                    <li class="page-item"><a class="page-link" href="#" data-page="${meta.page + 1}">
                        &raquo;
                    </a></li>`;
                }

                pagHTML += `</ul></nav>`;

                pagination.innerHTML = pagHTML;

                pagination.querySelectorAll("a.page-link").forEach(a => {
                    a.addEventListener("click", e => {
                        e.preventDefault();
                        loadMovement(a.dataset.page);
                    });
                });

            } catch (err) {
                console.error(err);
                tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-danger">Gagal memuat data</td>
                </tr>`;
            }
        }

        typeSel.addEventListener('change', () => loadMovement(1));
        monthSel.addEventListener('change', () => loadMovement(1));

    })();
</script>

<?= $this->endSection() ?>