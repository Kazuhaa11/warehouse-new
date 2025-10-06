<?= $this->extend('layouts/sbadmin_local') ?>

<?= $this->section('content') ?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <form id="filterForm" class="d-flex flex-wrap gap-2">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari zone/rack/bin/nama/note">
    <select name="plant" class="form-select form-select-sm">
      <option value="">All Plant</option>
      <option value="1200">1200</option>
      <option value="1300">1300</option>
    </select>
    <select name="storage_location" class="form-select form-select-sm">
      <option value="">All Stor. Loc</option>
      <option value="3618">3618</option>
      <option value="2691">2691</option>
      <option value="2642">2642</option>
      <option value="3691">3691</option>
    </select>
    <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>

    <button type="button" id="btnAdd" class="btn btn-success btn-sm">
      <i class="fas fa-plus me-1"></i> Tambah Lokasi
    </button>
  </form>
</div>

<div class="card w-100">
  <div class="card-header">Storages</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-bordered table-sm mb-0 w-100">
        <thead class="table-light">
          <tr>
            <th style="width:70px">ID</th>
            <th>Plant</th>
            <th>Stor. Loc</th>
            <th>Stor. Loc Desc</th>
            <th>Zone</th>
            <th>Rack</th>
            <th>Bin</th>
            <th>Name</th>
            <th>Path</th>
            <th style="width:120px">Aksi</th>
          </tr>
        </thead>
        <tbody id="rows">
          <tr>
            <td colspan="10" class="text-center text-muted">Memuat data...</td>
          </tr>
        </tbody>
      </table>
    </div>
    <nav>
      <ul class="pagination pagination-sm" id="pager"></ul>
    </nav>
  </div>
</div>

<?php
echo view('components/modal/modal-form', [
  'modalId' => 'modalStorage',
  'formId' => 'modalStorageForm',
  'title' => 'Form Storage',
  'api' => base_url('api/v1/storages'),
  'method' => 'POST',
  'submitText' => 'Simpan',
  'size' => 'lg',
  'split' => 6,
  'fields' => [
    [
      'name' => 'plant',
      'label' => 'Plant',
      'type' => 'select',
      'required' => true,
      'options' => [['value' => '1200', 'label' => '1200'], ['value' => '1300', 'label' => '1300']]
    ],
    [
      'name' => 'storage_location',
      'label' => 'Stor. Loc',
      'type' => 'select',
      'required' => true,
      'options' => [['value' => '3618', 'label' => '3618'], ['value' => '2691', 'label' => '2691'], ['value' => '2642', 'label' => '2642'], ['value' => '3691', 'label' => '3691']]
    ],
    [
      'name' => 'storage_location_desc',
      'label' => 'Stor. Loc Desc',
      'type' => 'select',
      'required' => true,
      'options' => [
        ['value' => 'PROD ENG INDUK', 'label' => 'PROD ENG INDUK'],
        ['value' => 'Engineering SDR', 'label' => 'Engineering SDR'],
        ['value' => 'NON MRP PRD ENG', 'label' => 'NON MRP PRD ENG'],
        ['value' => 'Prod Eng Maint', 'label' => 'Prod Eng Maint'],
      ]
    ],
    ['name' => 'zone', 'label' => 'Zone', 'type' => 'text', 'placeholder' => 'cth: Z-1'],
    ['name' => 'rack', 'label' => 'Rack', 'type' => 'text', 'placeholder' => 'cth: R-01'],
    ['name' => 'bin', 'label' => 'Bin', 'type' => 'text', 'placeholder' => 'cth: B-05'],
    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'placeholder' => 'Nama lokasi (opsional)'],
    ['name' => 'note', 'label' => 'Note', 'type' => 'textarea', 'placeholder' => 'Catatan'],
  ]
]);
?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  (function () {
    const API_Storages = '<?= base_url('api/v1/storages') ?>';
    let page = 1, per_page = 50;

    const $rows = document.getElementById('rows');
    const $pager = document.getElementById('pager');
    const $filter = document.getElementById('filterForm');

    const modalEl = document.getElementById('modalStorage');
    const formId = 'modalStorageForm';
    const form = document.getElementById(formId);
    const F = (name) => document.getElementById(formId + '_' + name);

    let mode = 'create'; let editId = null;

    const qs = (form) => {
      const p = new URLSearchParams(new FormData(form));
      p.set('page', page); p.set('per_page', per_page);
      return p.toString();
    };

    async function load() {
      $rows.innerHTML = `<tr><td colspan="10" class="text-center text-muted">Memuat data...</td></tr>`;
      try {
        const json = await apiFetch(`${API_Storages}?${qs($filter)}`);
        if (!json.success) throw new Error(json?.error?.message || 'Gagal load');
        renderRows(json.data || []);
        renderPager(json.meta || { page: 1, total_pages: 1 });
      } catch (err) {
        $rows.innerHTML = `<tr><td colspan="10" class="text-danger text-center">${esc(err.message)}</td></tr>`;
      }
    }

    function renderRows(items) {
      if (!items.length) {
        $rows.innerHTML = `<tr><td colspan="10" class="text-center text-muted">Tidak ada data</td></tr>`;
        return;
      }
      $rows.innerHTML = items.map(r => `
      <tr>
        <td>${esc(r.id)}</td>
        <td>${esc(r.plant)}</td>
        <td>${esc(r.storage_location)}</td>
        <td>${esc(r.storage_location_desc ?? '')}</td>
        <td>${esc(r.zone ?? '')}</td>
        <td>${esc(r.rack ?? '')}</td>
        <td>${esc(r.bin ?? '')}</td>
        <td>${esc(r.name ?? '')}</td>
        <td>${esc(r.path ?? '')}</td>
        <td class="text-nowrap">
          <button type="button" class="btn btn-sm btn-outline-primary me-1" data-edit="${r.id}">
            <i class="fas fa-edit"></i>
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger" data-del="${r.id}">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>
    `).join('');
    }

    function renderPager(meta) {
      $pager.innerHTML = '';
      const total = meta.total_pages || 1, current = meta.page || 1;
      for (let i = 1; i <= total; i++) {
        const li = document.createElement('li');
        li.className = 'page-item' + (i === current ? ' active' : '');
        li.innerHTML = `<a href="#" class="page-link">${i}</a>`;
        li.addEventListener('click', e => { e.preventDefault(); page = i; load(); });
        $pager.appendChild(li);
      }
    }

    $filter.addEventListener('submit', e => { e.preventDefault(); page = 1; load(); });

    document.getElementById('btnAdd').addEventListener('click', () => {
      mode = 'create'; editId = null;
      form.reset();
      form.dataset.api = API_Storages;           
      form.dataset.method = 'POST';
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    $rows.addEventListener('click', async (e) => {
      const btnDel = e.target.closest('button[data-del]');
      const btnEdit = e.target.closest('button[data-edit]');

      if (btnDel) {
        if (!confirm('Nonaktifkan data ini?')) return;
        try {
          const j = await apiFetch(`${API_Storages}/${btnDel.dataset.del}`, { method: 'DELETE' });
          if (j.success) load(); else alert(j?.error?.message || 'Gagal menghapus');
        } catch (err) {
          alert(err.message);
        }
        return;
      }

      if (btnEdit) {
        mode = 'edit';
        editId = btnEdit.dataset.edit;
        try {
          const j = await apiFetch(`${API_Storages}/${editId}`);
          if (!j.success) throw new Error(j?.error?.message || 'Gagal load detail');

          const d = j.data || {};
          form.dataset.api = `${API_Storages}/${editId}`;
          form.dataset.method = 'PUT';

          F('plant').value = d.plant ?? '';
          F('storage_location').value = d.storage_location ?? '';
          await loadStorLocDescOptions();
          const sel = F('storage_location_desc');
          const cur = d.storage_location_desc ?? '';
          if (cur && !Array.from(sel.options).some(o => o.value === cur)) {
            const opt = document.createElement('option'); opt.value = cur; opt.textContent = cur; sel.appendChild(opt);
          }
          sel.value = cur;

          F('zone').value = d.zone ?? '';
          F('rack').value = d.rack ?? '';
          F('bin').value = d.bin ?? '';
          F('name').value = d.name ?? '';
          F('note').value = d.note ?? '';

          bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } catch (err) {
          alert(err.message);
        }
      }
    });

    let DEFAULT_SLDESC_HTML = null;

    async function loadStorLocDescOptions() {
      const plant = F('plant')?.value;
      const sl = F('storage_location')?.value;
      const sel = F('storage_location_desc');

      if (!sel) return;

      if (DEFAULT_SLDESC_HTML === null) DEFAULT_SLDESC_HTML = sel.innerHTML;

      if (!plant || !sl) { sel.innerHTML = DEFAULT_SLDESC_HTML; return; }

      try {
        sel.innerHTML = '<option value="">Memuat…</option>';
        const url = `<?= base_url('api/v1/storages/presets/storage-location-desc') ?>?plant=${encodeURIComponent(plant)}&storage_location=${encodeURIComponent(sl)}`;
        const j = await apiFetch(url);
        const list = (j && j.success && Array.isArray(j.data)) ? j.data : [];
        if (list.length) {
          sel.innerHTML = '<option value="">— Pilih —</option>';
          list.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v; opt.textContent = v;
            sel.appendChild(opt);
          });
        } else {
          sel.innerHTML = DEFAULT_SLDESC_HTML;
        }
      } catch (e) {
        sel.innerHTML = DEFAULT_SLDESC_HTML;
      }
    }

    ['plant', 'storage_location'].forEach(n => {
      const el = F(n); if (el) el.addEventListener('change', loadStorLocDescOptions);
    });

    window.addEventListener('modal:success', (ev) => {
      if (ev.detail?.modalId === 'modalStorage') load();
    });

    function esc(s) { return String(s ?? '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])); }

    load();
  })();
</script>
<script src="<?= base_url('sbadmin/js/modal-form.js') ?>"></script>
<?= $this->endSection() ?>