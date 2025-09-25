<?= $this->extend('layouts/sbadmin_local') ?>

<?= $this->section('content') ?>
<div class="card mb-4">
  <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
    <form id="filterForm" class="d-flex flex-wrap gap-2 align-items-center">
      <!-- Search -->
      <input type="text" class="form-control form-control-sm" name="q" placeholder="Cari material/desc/sloc…"
        value="<?= esc(service('request')->getGet('q') ?? '') ?>" style="min-width:220px">

      <!-- Plant -->
      <select class="form-select form-select-sm" name="plant" title="Plant" style="min-width:120px">
        <option value="">All Plant</option>
        <option value="1200">Plant 1200</option>
        <option value="1300">Plant 1300</option>
      </select>

      <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
      <button type="button" id="btnReset" class="btn btn-sm btn-outline-secondary">Reset</button>

      <!-- Button buka modal: Tambah Satuan (Barang) -->
      <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalSatuan">
        <i class="fas fa-plus"></i> Tambah Satuan
      </button>
    </form>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped table-hover table-sm align-middle table-bordered" id="tbl-barang">
        <thead class="table-light">
          <tr>
            <th>Material</th>
            <th>Deskripsi</th>
            <th>Plant</th>
            <th>Stor. Loc</th>
            <th>Stor. Loc Desc</th>
            <th class="text-end">Unrestricted</th>
            <th class="text-end">Transit</th>
            <th class="text-end">Blocked</th>
            <th style="width:100px">Aksi</th>
          </tr>
        </thead>
        <tbody id="tbody-barang">
          <tr>
            <td colspan="9" class="text-center text-muted">Memuat data...</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2">
      <small class="text-muted" id="metaText">—</small>
      <nav>
        <ul class="pagination pagination-sm mb-0" id="pager"></ul>
      </nav>
    </div>
  </div>
</div>

<!-- MODAL: Tambah Satuan/Barang (komponen generik) -->
<?= view('components/modal/modal-form', [
  'modalId' => 'modalSatuan',
  'title' => 'Tambah Barang',
  'api' => base_url('api/v1/barang/create'),
  'method' => 'POST',
  'submitText' => 'Simpan',
  'size' => 'lg',
  'split' => 5, // 5 field kiri, 4 kanan
  'fields' => [
    // KIRI (5)
    ['name' => 'material', 'label' => 'Material', 'type' => 'text', 'placeholder' => 'Kode material unik', 'required' => true],
    ['name' => 'material_description', 'label' => 'Deskripsi', 'type' => 'text', 'placeholder' => 'Nama/Deskripsi'],
    [
      'name' => 'plant',
      'label' => 'Plant',
      'type' => 'select',
      'options' => [['value' => '1200', 'label' => 'Plant 1200'], ['value' => '1300', 'label' => 'Plant 1300']]
    ],
    [
      'name' => 'storage_location',
      'label' => 'Stor. Loc',
      'type' => 'select',
      'options' => [
        ['value' => '3618', 'label' => '3618'],
        ['value' => '2691', 'label' => '2691'],
        ['value' => '2642', 'label' => '2642'],
        ['value' => '3691', 'label' => '3691'],
      ]
    ],
    [
      'name' => 'storage_location_desc',
      'label' => 'Stor. Loc Desc',
      'type' => 'select',
      'options' => [
        ['value' => 'Engineering SDR', 'label' => 'Engineering SDR'],
        ['value' => 'PROD ENG INDUK', 'label' => 'PROD ENG INDUK'],
      ]
    ],

    // KANAN (4)
    ['name' => 'base_unit_of_measure', 'label' => 'UoM', 'type' => 'text', 'placeholder' => 'PCS / KG / L'],
    ['name' => 'qty_unrestricted', 'label' => 'Unrestricted', 'type' => 'number', 'step' => '0.001', 'value' => '0'],
    ['name' => 'qty_transit_and_transfer', 'label' => 'Transit', 'type' => 'number', 'step' => '0.001', 'value' => '0'],
    ['name' => 'qty_blocked', 'label' => 'Blocked', 'type' => 'number', 'step' => '0.001', 'value' => '0'],
    ['name' => 'material_type', 'label' => 'Tipe', 'type' => 'text', 'placeholder' => 'mis. FERT / HAWA'],
  ],
]) ?>

<!-- MODAL: Detail Barang (komponen generik) -->
<?php
echo view('components/modal/modal-form', [
  'modalId' => 'modalBarangDetail',
  'title' => 'Detail Barang',
  'api' => '#',          // di-set via JS → /api/v1/barang/{id}
  'method' => 'PUT',
  'submitText' => 'Update',
  'size' => 'lg',
  'split' => 3,
  'fields' => [
    // kiri
    ['name' => 'material', 'label' => 'Material', 'type' => 'text', 'required' => true],
    ['name' => 'material_description', 'label' => 'Deskripsi', 'type' => 'text'],
    ['name' => 'plant', 'label' => 'Plant', 'type' => 'text'],
    // kanan
    ['name' => 'base_unit_of_measure', 'label' => 'UoM', 'type' => 'text'],
    ['name' => 'material_type', 'label' => 'Material Type', 'type' => 'text'],
    ['name' => 'material_group', 'label' => 'Material Group', 'type' => 'text'],
    // bawah
    ['name' => 'storage_id', 'label' => 'Storage ID', 'type' => 'text', 'placeholder' => 'ID storage'],
    ['name' => 'storage_info', 'label' => 'Info Storage (auto)', 'type' => 'textarea', 'placeholder' => 'Akan diisi otomatis dari /storages/{id}'],
  ],
]);
?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  (() => {
    const API_BARANG = '<?= base_url('api/v1/barang') ?>';
    const API_STORAGES = '<?= base_url('api/v1/storages') ?>';
    const PER_PAGE = 50;

    const form = document.getElementById('filterForm');
    const qInput = form.querySelector('input[name="q"]');
    const plantSel = form.querySelector('select[name="plant"]');
    const btnReset = document.getElementById('btnReset');

    const tbody = document.getElementById('tbody-barang');
    const pager = document.getElementById('pager');
    const metaText = document.getElementById('metaText');

    // keep params from URL
    const initParams = new URLSearchParams(location.search);
    if (initParams.get('plant')) plantSel.value = initParams.get('plant');
    if (initParams.get('q')) qInput.value = initParams.get('q');

    form.addEventListener('submit', (e) => { e.preventDefault(); load(1); });
    btnReset.addEventListener('click', () => {
      qInput.value = '';
      plantSel.value = '';
      load(1);
    });

    async function load(page = 1) {
      const params = new URLSearchParams();
      const q = qInput.value.trim();
      if (q) params.set('q', q);
      const plant = plantSel.value;
      if (plant) params.set('plant', plant);
      params.set('per_page', PER_PAGE);
      params.set('page', page);

      // update URL
      history.replaceState(null, '', '?' + params.toString());

      tbody.innerHTML = `
      <tr><td colspan="9" class="text-center text-muted">Memuat data...</td></tr>
    `;
      pager.innerHTML = '';
      metaText.textContent = '—';

      try {
        const res = await fetch(`${API_BARANG}?${params.toString()}`);
        const json = await res.json();
        if (!json.success) throw new Error(json?.error?.message || 'Gagal memuat');

        const rows = json.data || [];
        const meta = json.meta || { page, per_page: PER_PAGE, total: 0, total_pages: 1 };

        renderRows(rows);
        renderPager(meta);
        metaText.textContent = `Halaman ${meta.page} / ${meta.total_pages} • ${rows.length} data ditampilkan • Total ${meta.total} data`;
      } catch (err) {
        tbody.innerHTML = `
        <tr><td colspan="9" class="text-center text-danger">${esc(err.message)}</td></tr>
      `;
      }
    }

    function renderRows(rows) {
      if (!rows.length) {
        tbody.innerHTML = `
        <tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>
      `;
        return;
      }
      tbody.innerHTML = rows.map(r => `
      <tr>
        <td>${esc(r.material ?? '')}</td>
        <td>${esc(r.material_description ?? '')}</td>
        <td>${esc(r.plant ?? '')}</td>
        <td>${esc(r.storage_location ?? '')}</td>
        <td>${esc(r.storage_location_desc ?? '')}</td>
        <td class="text-end">${num(r.qty_unrestricted)}</td>
        <td class="text-end">${num(r.qty_transit_and_transfer)}</td>
        <td class="text-end">${num(r.qty_blocked)}</td>
        <td class="text-center">
          <button class="btn btn-outline-primary btn-sm btn-detail"
                  data-id="${r.id}" title="Detail">
            <i class="fas fa-eye"></i>
          </button>
        </td>
      </tr>
    `).join('');
    }

    function renderPager(meta) {
      const total = meta.total_pages || 1;
      const current = meta.page || 1;

      function item(p, label = p, disabled = false, active = false) {
        return `
        <li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
          <a class="page-link" href="#" data-p="${p}">${label}</a>
        </li>
      `;
      }

      pager.innerHTML = '';
      pager.insertAdjacentHTML('beforeend', item(current - 1, '&laquo;', current <= 1, false));

      const max = 7;
      let start = Math.max(1, current - Math.floor(max / 2));
      let end = Math.min(total, start + max - 1);
      if (end - start + 1 < max) start = Math.max(1, end - max + 1);

      if (start > 1) {
        pager.insertAdjacentHTML('beforeend', item(1, '1', false, current === 1));
        if (start > 2) pager.insertAdjacentHTML('beforeend', `<li class="page-item disabled"><span class="page-link">…</span></li>`);
      }

      for (let p = start; p <= end; p++) {
        if (p === 1 || p === total) continue;
        pager.insertAdjacentHTML('beforeend', item(p, String(p), false, p === current));
      }

      if (end < total) {
        if (end < total - 1) pager.insertAdjacentHTML('beforeend', `<li class="page-item disabled"><span class="page-link">…</span></li>`);
        pager.insertAdjacentHTML('beforeend', item(total, String(total), false, current === total));
      }

      pager.insertAdjacentHTML('beforeend', item(current + 1, '&raquo;', current >= total, false));

      pager.querySelectorAll('a.page-link').forEach(a => {
        a.addEventListener('click', e => {
          e.preventDefault();
          const p = parseInt(a.dataset.p, 10);
          if (!isNaN(p)) load(p);
        });
      });
    }

    // ====== DETAIL MODAL ======
    const detailModalEl = document.getElementById('modalBarangDetail');
    const detailForm = detailModalEl.querySelector('form[data-modal-form]');
    const errBox = detailModalEl.querySelector('[data-role="error"]');

    // Tambahkan tombol Hapus di footer modal
    (() => {
      const footer = detailModalEl.querySelector('.modal-footer');
      const delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'btn btn-sm btn-outline-danger ms-auto';
      delBtn.id = 'btnDeleteBarang';
      delBtn.innerHTML = '<i class="fas fa-trash me-1"></i> Hapus';
      footer.insertBefore(delBtn, footer.lastElementChild);
    })();

    // Klik detail → buka modal
    tbody.addEventListener('click', async (e) => {
      const btn = e.target.closest('.btn-detail'); if (!btn) return;
      const id = btn.dataset.id;
      await openDetail(id);
    });

    async function openDetail(id) {
      clearError();
      try {
        // GET barang
        const res = await fetch(`${API_BARANG}/${id}`);
        const json = await res.json();
        if (!res.ok || !json.success) throw new Error(json?.error?.message || 'Gagal ambil detail');

        const b = json.data || json;
        // set target PUT /barang/{id}
        detailForm.dataset.api = `${API_BARANG}/${id}`;
        detailForm.dataset.method = 'PUT';

        // isi fields
        setValue('material', b.material);
        setValue('material_description', b.material_description);
        setValue('plant', b.plant);
        setValue('base_unit_of_measure', b.base_unit_of_measure);
        setValue('material_type', b.material_type);
        setValue('material_group', b.material_group);
        setValue('storage_id', b.storage_id || '');
        setValue('storage_info', 'Memuat info storage…');

        // GET info storage (jika ada storage_id)
        if (b.storage_id) {
          try {
            const rs = await fetch(`${API_STORAGES}/${b.storage_id}`);
            const js = await rs.json();
            if (rs.ok) {
              const s = js.data || js;
              const label = [
                s.zone ? ` • Zone: ${s.bin}`: '', 
                s.rack ? ` • Rack: ${s.rack}`: '',
                s.bin ? ` • Bin: ${s.bin}` : '',
                s.plant ? ` • Plant: ${s.plant}` : '',
                s.storage_location ? ` • SLoc: ${s.storage_location}` : ''
              ].filter(Boolean).join('');
              setValue('storage_info', label || 'Storage ditemukan tetapi tanpa detail.');
            } else {
              setValue('storage_info', js?.error?.message || 'Gagal memuat info storage.');
            }
          } catch {
            setValue('storage_info', 'Gagal memuat info storage.');
          }
        } else {
          setValue('storage_info', 'Tidak ada storage_id.');
        }

        // tampilkan modal
        new bootstrap.Modal(detailModalEl).show();

        // tombol delete
        const del = document.getElementById('btnDeleteBarang');
        del.onclick = async () => {
          if (!confirm('Hapus barang ini?')) return;
          try {
            const r = await fetch(`${API_BARANG}/${id}`, { method: 'DELETE' });
            const j = await r.json().catch(() => ({}));
            if (!r.ok || j.success === false) throw new Error(j?.error?.message || 'Gagal menghapus');
            bootstrap.Modal.getInstance(detailModalEl)?.hide();
            load(1);
          } catch (err) { showError(err.message || 'Gagal menghapus'); }
        };

        // change storage_id → refresh storage_info
        detailForm.querySelector('#<?= esc('modalBarangDetailForm_storage_id') ?>')
          ?.addEventListener('change', async (e) => {
            const val = String(e.target.value || '').trim();
            if (!val) { setValue('storage_info', 'Tidak ada storage_id.'); return; }
            setValue('storage_info', 'Memuat info storage…');
            try {
              const rs = await fetch(`${API_STORAGES}/${encodeURIComponent(val)}`);
              const js = await rs.json();
              if (!rs.ok) throw new Error(js?.error?.message || 'Gagal memuat info storage.');
              const s = js.data || js;
              const label = [
                s.name || s.title || '',
                s.path ? ` (${s.path})` : '',
                s.plant ? ` • Plant: ${s.plant}` : '',
                s.storage_location ? ` • SLoc: ${s.storage_location}` : ''
              ].filter(Boolean).join('');
              setValue('storage_info', label || 'Storage ditemukan tetapi tanpa detail.');
            } catch (err) {
              setValue('storage_info', err.message || 'Gagal memuat info storage.');
            }
          });

        // reload list ketika update sukses
        window.addEventListener('modal:success', onModalSuccess, { once: true });
        function onModalSuccess(ev) {
          if (ev.detail?.modalId === 'modalBarangDetail') load(1);
        }

      } catch (err) {
        alert(err.message || 'Gagal memuat detail.');
      }
    }

    // notifikasi sederhana untuk Tambah Satuan sukses
    window.addEventListener('modal:success', (ev) => {
      if (ev.detail?.modalId === 'modalSatuan') {
        alert('Satuan/Barang berhasil ditambahkan.');
        load(1);
      }
    });

    function setValue(name, value) {
      const el = detailForm.querySelector(`[name="${css(name)}"]`);
      if (el) el.value = value ?? '';
    }
    function clearError() { if (errBox) { errBox.classList.add('d-none'); errBox.textContent = ''; } }
    function showError(msg) { if (errBox) { errBox.classList.remove('d-none'); errBox.textContent = msg; } }
    function num(v) { const n = Number(v ?? 0); return isNaN(n) ? '0' : n.toLocaleString(); }
    function esc(s) { return String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])); }
    function css(s) { return String(s).replace(/"/g, '\\"'); }

    // initial
    load(1);
  })();
</script>

<!-- handler modal generik -->
<script src="<?= base_url('sbadmin/js/modal-form.js') ?>"></script>
<?= $this->endSection() ?>