<?= $this->extend('layouts/sbadmin_local') ?>

<?= $this->section('content') ?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <form id="filterForm" class="d-flex flex-wrap gap-2 align-items-center">
    <input type="text" class="form-control form-control-sm" name="q" placeholder="Cari no nota / catatan"
      value="<?= esc(service('request')->getGet('q') ?? '') ?>" style="min-width:220px">
    <select class="form-select form-select-sm" name="plant" style="min-width:120px">
      <option value="">All Plant</option>
      <option value="1200">Plant 1200</option>
      <option value="1300">Plant 1300</option>
    </select>
    <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
    <button type="button" id="btnReset" class="btn btn-sm btn-outline-secondary">Reset</button>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalNota">
      <i class="fas fa-file-invoice me-1"></i> Cetak Laporan
    </button>
  </form>
</div>

<div class="card">
  <div class="card-header">Peminjaman</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped table-hover table-sm align-middle table-bordered">
        <thead class="table-light">
          <tr>
            <th>No Nota</th>
            <th>Tanggal</th>
            <th>Plant</th>
            <th>Status</th>
            <th>Catatan</th>
            <th style="width:90px">Details</th>
          </tr>
        </thead>
        <tbody id="tbody-pinjam">
          <tr>
            <td colspan="6" class="text-center text-muted">Memuat data...</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2">
      <small class="text-muted" id="metaText">—</small>
      <ul class="pagination pagination-sm mb-0" id="pager"></ul>
    </div>
  </div>
</div>

<!-- Modal Detail Peminjaman (read-only) -->
<?= view('components/modal/modal-form', [
  'modalId' => 'modalDetailPinjam',
  'title' => 'Detail Peminjaman',
  'api' => '#',
  'method' => 'GET',
  'submitText' => 'Tutup',
  'size' => 'xl',
  'split' => 4,
  'fields' => [
    // KIRI
    ['name' => 'no_nota', 'label' => 'No Nota', 'type' => 'text'],
    ['name' => 'borrow_date', 'label' => 'Tanggal', 'type' => 'text'],
    ['name' => 'plant', 'label' => 'Plant', 'type' => 'text'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
    // KANAN
    ['name' => 'peminjam', 'label' => 'Peminjam', 'type' => 'text'],
    ['name' => 'created_at', 'label' => 'Dibuat', 'type' => 'text'],
    ['name' => 'note', 'label' => 'Catatan', 'type' => 'textarea'],
  ],
]) ?>

<!-- Modal Cetak Laporan (pakai komponen modal-form) -->
<?= view('components/modal/modal-form', [
  'modalId' => 'modalNota',
  'title' => 'Cetak Laporan Peminjaman',
  'api' => '#',          // submit manual via JS (open PDF)
  'method' => 'GET',
  'submitText' => 'Cetak PDF',
  'size' => 'lg',
  'split' => 4,
  'fields' => [
    // KIRI
    [
      'name' => 'mode',
      'label' => 'Mode Filter',
      'type' => 'select',
      'value' => 'range',
      'options' => [
        ['value' => 'range', 'label' => 'Range Tanggal'],
        ['value' => 'monthyear', 'label' => 'Bulan & Tahun'],
      ],
    ],
    ['name' => 'from_date', 'label' => 'Dari Tanggal', 'type' => 'date', 'required' => true],
    ['name' => 'to_date', 'label' => 'Sampai Tanggal', 'type' => 'date', 'required' => true],
    [
      'name' => 'month',
      'label' => 'Bulan',
      'type' => 'select',
      'options' => array_map(
        fn($m) => ['value' => $m, 'label' => DateTime::createFromFormat('!m', (string) $m)->format('F')],
        range(1, 12)
      ),
    ],
    ['name' => 'year', 'label' => 'Tahun', 'type' => 'number', 'value' => date('Y')],

    // KANAN
    [
      'name' => 'plant',
      'label' => 'Plant',
      'type' => 'select',
      'options' => [
        ['value' => '1200', 'label' => '1200'],
        ['value' => '1300', 'label' => '1300'],
      ],
    ],
    [
      'name' => 'sort_by',
      'label' => 'Sortir',
      'type' => 'select',
      'value' => 'tanggal',
      'options' => [
        ['value' => 'tanggal', 'label' => 'Tanggal'],
        ['value' => 'no_nota', 'label' => 'No Nota'],
      ],
    ],
    [
      'name' => 'sort_dir',
      'label' => 'Urutan',
      'type' => 'select',
      'value' => 'desc',
      'options' => [
        ['value' => 'asc', 'label' => 'ASC'],
        ['value' => 'desc', 'label' => 'DESC'],
      ],
    ],
  ],
]) ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  (function () {
    const API = '<?= base_url('api/v1/peminjaman') ?>';
    const PER_PAGE = 50;

    const form = document.getElementById('filterForm');
    const qInput = form.querySelector('input[name="q"]');
    const plantSel = form.querySelector('select[name="plant"]');
    const btnReset = document.getElementById('btnReset');

    const tbody = document.getElementById('tbody-pinjam');
    const pager = document.getElementById('pager');
    const metaText = document.getElementById('metaText');

    // Modal Detail
    const modalDetailEl = document.getElementById('modalDetailPinjam');
    const modalDetail = new bootstrap.Modal(modalDetailEl);
    const formEl = modalDetailEl.querySelector('form[data-modal-form]');
    const formId = formEl ? formEl.id : 'modalDetailPinjamForm';

    // Init modal (readonly + tabel items)
    (function initDetailModalShell() {
      if (!formEl) return;
      formEl.querySelectorAll('input, textarea').forEach(el => el.readOnly = true);
      formEl.querySelectorAll('select').forEach(el => el.disabled = true);
      const submitBtn = formEl.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.type = 'button';
        submitBtn.setAttribute('data-bs-dismiss', 'modal');
        submitBtn.innerHTML = '<i class="fas fa-times me-1"></i> Tutup';
      }
      const body = modalDetailEl.querySelector('.modal-body');
      if (!document.getElementById('pinjamItemsBody')) {
        const wrap = document.createElement('div');
        wrap.innerHTML = `
        <hr class="my-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">Items</h6>
          <small class="text-muted" id="pinjamItemsMeta">—</small>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:60px">#</th>
                <th>Material</th>
                <th>Deskripsi</th>
                <th style="width:120px">Plant</th>
                <th style="width:140px">Storage Loc</th>
                <th style="width:100px" class="text-end">Qty</th>
                <th style="width:120px">UoM</th>
              </tr>
            </thead>
            <tbody id="pinjamItemsBody">
              <tr><td colspan="7" class="text-center text-muted">—</td></tr>
            </tbody>
          </table>
        </div>`;
        body.appendChild(wrap);
      }
      modalDetailEl.addEventListener('hidden.bs.modal', () => {
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
      });
    })();

    function setField(name, val) {
      const el = document.getElementById(`${formId}_${name}`);
      if (!el) return;
      const v = (val ?? '').toString();
      if (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT' || el.tagName === 'SELECT') el.value = v;
      else el.textContent = v;
    }

    // Filter init
    const init = new URLSearchParams(location.search);
    if (init.get('plant')) plantSel.value = init.get('plant');
    if (init.get('q')) qInput.value = init.get('q');

    form.addEventListener('submit', (e) => { e.preventDefault(); load(1); });
    btnReset.addEventListener('click', () => { qInput.value = ''; plantSel.value = ''; load(1); });

    async function load(page = 1) {
      const params = new URLSearchParams();
      if (qInput.value.trim()) params.set('q', qInput.value.trim());
      if (plantSel.value) params.set('plant', plantSel.value);
      params.set('per_page', PER_PAGE);
      params.set('page', page);
      history.replaceState(null, '', '?' + params.toString());

      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Memuat data...</td></tr>`;
      pager.innerHTML = ''; metaText.textContent = '—';

      try {
        const res = await fetch(`${API}?${params.toString()}`);
        const json = await res.json();
        if (!json.success) throw new Error(json?.error?.message || 'Gagal memuat');
        renderRows(json.data || []);
        renderPager(json.meta || { page, total_pages: 1, total: 0 });
        metaText.textContent = `Halaman ${json.meta.page} / ${json.meta.total_pages} • ${json.data.length} data • Total ${json.meta.total}`;
      } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${esc(err.message)}</td></tr>`;
      }
    }

    function renderRows(rows) {
      if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Tidak ada data</td></tr>`;
        return;
      }
      tbody.innerHTML = rows.map(r => `
      <tr>
        <td>${esc(r.nomor ?? r.no_nota ?? '')}</td>
        <td>${esc(r.tanggal ?? r.borrow_date ?? '')}</td>
        <td>${esc(r.plant ?? r.plants ?? '')}</td>
        <td><span class="badge text-bg-${badge(r.status)}">${esc(r.status)}</span></td>
        <td>${esc(r.note ?? '')}</td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-primary" data-id="${esc(r.id)}" data-action="detail">
            <i class="fas fa-eye"></i>
          </button>
        </td>
      </tr>`).join('');
    }

    function renderPager(meta) {
      const total = meta.total_pages || 1; const current = meta.page || 1; pager.innerHTML = '';
      function item(p, label = p, disabled = false, active = false) {
        return `<li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
          <a class="page-link" href="#" data-p="${p}">${label}</a></li>`;
      }
      pager.insertAdjacentHTML('beforeend', item(current - 1, '&laquo;', current <= 1));
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
      pager.insertAdjacentHTML('beforeend', item(current + 1, '&raquo;', current >= total));
      pager.querySelectorAll('a.page-link').forEach(a => a.addEventListener('click', e => {
        e.preventDefault();
        const p = parseInt(a.dataset.p, 10);
        if (!isNaN(p)) load(p);
      }));
    }

    // Detail
    tbody.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-action="detail"]');
      if (!btn) return;
      openDetail(btn.dataset.id);
    });

    async function openDetail(id) {
      // reset awal
      setField('no_nota', '—');
      setField('borrow_date', '—');
      setField('plant', '—');
      setField('status', '—');
      setField('peminjam', '—');
      setField('created_at', '—');
      setField('note', '');

      const itemsBody = document.getElementById('pinjamItemsBody');
      const itemsMeta = document.getElementById('pinjamItemsMeta');
      itemsBody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Memuat items...</td></tr>`;
      itemsMeta.textContent = 'Memuat...';

      modalDetail.show();

      try {
        const res = await fetch(`${API}/${id}`);
        const json = await res.json();
        if (!json?.success) throw new Error(json?.error?.message || 'Gagal memuat detail');
        const h = json.data?.header || {};
        const items = Array.isArray(json.data?.items) ? json.data.items : [];

        setField('no_nota', h.nomor || h.no_nota || '—');
        setField('borrow_date', h.tanggal || h.borrow_date || '—');
        setField('plant', h.plants || h.plant || '—');
        setField('status', (h.status || '—').toUpperCase());
        setField('peminjam', h.peminjam_username || h.peminjam || '—');
        setField('created_at', h.created_at || '—');
        setField('note', h.note || '');

        renderDetailItems(items);
      } catch (err) {
        itemsBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${esc(err.message)}</td></tr>`;
        itemsMeta.textContent = '—';
      }
    }

    function renderDetailItems(items) {
      const itemsBody = document.getElementById('pinjamItemsBody');
      const itemsMeta = document.getElementById('pinjamItemsMeta');
      if (!items.length) {
        itemsBody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Tidak ada item</td></tr>`;
        itemsMeta.textContent = '0 item';
        return;
      }
      itemsBody.innerHTML = items.map((it, i) => `
      <tr>
        <td class="text-center">${i + 1}</td>
        <td>${esc(it.material ?? '')}</td>
        <td>${esc(it.material_description ?? '')}</td>
        <td>${esc(it.plant ?? '')}</td>
        <td>${esc(it.storage_location ?? '')}</td>
        <td class="text-end">${esc(it.qty ?? it.requested_qty ?? 1)}</td>
        <td>${esc(it.uom ?? it.base_unit_of_measure ?? '')}</td>
      </tr>`).join('');
      itemsMeta.textContent = `${items.length} item`;
    }

    function badge(status) {
      switch ((status || '').toLowerCase()) {
        case 'draft': return 'secondary';
        case 'submitted': return 'info';
        case 'approved': return 'success';
        case 'returned': return 'primary';
        case 'rejected': return 'danger';
        case 'loaned': return 'warning';
        case 'lost': return 'dark';
        default: return 'light';
      }
    }

    function esc(s) {
      return String(s ?? '').replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
      }[m]));
    }

    // ===== Cetak Laporan (Modal 'modalNota') =====
    (function () {
      const formId = 'modalNotaForm';
      const f = document.getElementById(formId);
      if (!f) return;

      const modeEl = document.getElementById(formId + '_mode');
      const fromEl = document.getElementById(formId + '_from_date');
      const toEl = document.getElementById(formId + '_to_date');
      const monthEl = document.getElementById(formId + '_month');
      const yearEl = document.getElementById(formId + '_year');
      const plantEl = document.getElementById(formId + '_plant');
      const sortByEl = document.getElementById(formId + '_sort_by');
      const sortDirEl = document.getElementById(formId + '_sort_dir');

      const wrap = el => el ? el.closest('.mb-2') : null;
      const show = (el, on) => { const w = wrap(el); if (w) w.style.display = on ? '' : 'none'; }
      const setReq = (el, on) => { if (el) el.required = !!on; }

      function applyModeUI() {
        const isRange = (modeEl?.value || 'range') === 'range';
        show(fromEl, isRange);
        show(toEl, isRange);
        setReq(fromEl, isRange);
        setReq(toEl, isRange);

        show(monthEl, !isRange);
        show(yearEl, !isRange);
        setReq(monthEl, !isRange);
        setReq(yearEl, !isRange);
      }
      modeEl?.addEventListener('change', applyModeUI);
      applyModeUI();

      f.addEventListener('submit', function (e) {
        e.preventDefault();
        const params = new URLSearchParams();
        const mode = modeEl?.value || 'range';
        params.set('mode', mode);

        if (mode === 'range') {
          const from = fromEl?.value || '';
          const to = toEl?.value || '';
          if (!from || !to) { alert('Isi rentang tanggal.'); return; }
          params.set('from_date', from);
          params.set('to_date', to);
        } else {
          const month = monthEl?.value || '';
          const year = yearEl?.value || '';
          if (!month || !year) { alert('Pilih bulan & tahun.'); return; }
          params.set('month', month);
          params.set('year', year);
        }

        const plant = plantEl?.value || '';
        const sort_by = sortByEl?.value || 'tanggal';
        const sort_dir = sortDirEl?.value || 'desc';
        if (plant) params.set('plant', plant);
        params.set('sort_by', sort_by);
        params.set('sort_dir', sort_dir);

        params.set('dl', '1');

        const url = '<?= base_url('api/v1/peminjaman/report/pdf') ?>?' + params.toString();
        window.location.href = url; // langsung trigger download
      });
    })();

    // initial
    load(1);
  })();
</script>
<?= $this->endSection() ?>