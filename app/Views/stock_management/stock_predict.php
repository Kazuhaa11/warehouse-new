<?= $this->extend('layouts/sbadmin_local') ?>

<?= $this->section('content') ?>

<div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-box me-2"></i> Prediksi Reorder Barang</span>
        <div class="d-flex gap-2">
            <form id="searchForm" class="d-flex">
                <input type="text" class="form-control form-control-sm" name="q" placeholder="Cari material/deskripsi..." style="width:250px;">
                <button class="btn btn-sm btn-primary ms-1"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <div class="card-body">
        <div id="loading" class="text-center my-4" style="display:none;">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2 mb-0 small">Memuat data...</p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm align-middle" id="tablePredict">
                <thead class="table-light text-center">
                    <tr>
                        <th style="width:50px;">No</th>
                        <th>Material</th>
                        <th>Deskripsi</th>
                        <th>Kelas</th>
                        <th>Safety Stock</th>
                        <th>ROP</th>
                        <th>Jumlah Order(PCS)</th>
                        <th>Disarankan Order</th>
                        <th>Stok Saat Ini</th>
                        <th>Tanggal Pesan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    <tr>
                        <td colspan="11" class="text-muted">Belum ada data.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="pagination" class="d-flex justify-content-between align-items-center flex-wrap mt-2 small"></div>
    </div>
</div>

<script>
    async function loadPredictData(page = 1, q = '') {
        const tbody = document.querySelector('#tablePredict tbody');
        const loading = document.getElementById('loading');
        const pagination = document.getElementById('pagination');
        tbody.innerHTML = '';
        pagination.innerHTML = '';
        loading.style.display = 'block';

        try {
            const res = await fetch(`<?= base_url('admin/stock-predict') ?>?page=${page}&per_page=25&q=${encodeURIComponent(q)}`);
            const json = await res.json();
            const {
                data,
                meta
            } = json;
            loading.style.display = 'none';

            if (!data || data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="11" class="text-muted">Tidak ada data ditemukan.</td></tr>`;
                return;
            }

            let i = (meta.page - 1) * meta.per_page + 1;
            for (const d of data) {
                let badgeClass = 'bg-secondary';
                if (d.order_status.includes('Sekarang')) badgeClass = 'bg-danger';
                else if (d.order_status.includes('hari')) badgeClass = 'bg-warning text-dark';
                else badgeClass = 'bg-success';

                const row = `
        <tr>
          <td>${i++}</td>
          <td>${d.material}</td>
          <td class="text-start">${d.description}</td>
          <td><span class="badge bg-info text-dark">${d.class}</span></td>
          <td>${Number(d.safety_stock).toFixed(2)}</td>
          <td>${Number(d.rop_rounded).toLocaleString()}</td>
          <td>${Number(d.eoq).toLocaleString()}</td>
          <td>${Number(d.order_qty_suggested).toLocaleString()}</td>
          <td>${Number(d.current_stock).toLocaleString()}</td>
          <td>${d.next_order_date}</td>
          <td><span class="badge ${badgeClass}">${d.order_status}</span></td>
        </tr>
      `;
                tbody.insertAdjacentHTML('beforeend', row);
            }

            let pagHTML = `
      <div>Halaman ${meta.page} / ${meta.total_pages} &nbsp;·&nbsp; ${meta.per_page} data/hal &nbsp;·&nbsp; Total ${meta.total} data</div>
      <nav>
        <ul class="pagination pagination-sm mb-0">
    `;
            if (meta.page > 1) {
                pagHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${meta.page - 1}">&laquo;</a></li>`;
            }
            const start = Math.max(1, meta.page - 2);
            const end = Math.min(meta.total_pages, meta.page + 2);
            for (let i = start; i <= end; i++) {
                pagHTML += `<li class="page-item ${i === meta.page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }
            if (meta.page < meta.total_pages) {
                pagHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${meta.page + 1}">&raquo;</a></li>`;
            }
            pagHTML += `</ul></nav>`;
            pagination.innerHTML = pagHTML;

            pagination.querySelectorAll('a.page-link').forEach(a => {
                a.addEventListener('click', e => {
                    e.preventDefault();
                    loadPredictData(parseInt(a.dataset.page), q);
                });
            });
        } catch (err) {
            loading.style.display = 'none';
            tbody.innerHTML = `<tr><td colspan="11" class="text-danger">Gagal memuat data: ${err.message}</td></tr>`;
        }
    }

    document.getElementById('searchForm')?.addEventListener('submit', e => {
        e.preventDefault();
        const q = e.target.q.value;
        loadPredictData(1, q);
    });

    document.addEventListener('DOMContentLoaded', () => loadPredictData());
</script>

<?= $this->endSection() ?>