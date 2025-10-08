<?= $this->extend('layouts/sbadmin_local') ?>

<?php
?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Items Stock Opname</h4>
    <div class="text-muted small" id="sessInfo">Memuat…</div>
  </div>

  <div class="d-flex gap-2 align-items-center flex-wrap justify-content-end">
    <div class="d-flex align-items-center gap-2">
      <input id="inpSearch" class="form-control form-control-sm" style="width:220px" type="search"
        placeholder="Cari material/desc/sloc…">
      <select id="selPlant" class="form-select form-select-sm" style="width:auto">
        <option value="">All Plants</option>
      </select>

      <label class="form-label mb-0 small text-muted">Sort</label>
      <select id="selSort" class="form-select form-select-sm" style="width:auto">
        <option value="diff_desc">Selisih Terbesar</option>
        <option value="diff_asc">Selisih Terkecil</option>
        <option value="counted_desc">Terhitung Terbesar</option>
        <option value="counted_asc">Terhitung Terkecil</option>
      </select>

      <label class="form-label mb-0 small text-muted ms-2">Per Page</label>
      <select id="selPerPage" class="form-select form-select-sm" style="width:auto">
        <option>25</option>
        <option>50</option>
        <option>100</option>
      </select>
    </div>

    <button id="btnImport" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-file-excel me-1"></i> Import Excel
    </button>
    <button id="btnAdd" class="btn btn-success btn-sm">
      <i class="fas fa-plus me-1"></i> Tambah Item
    </button>
    <button id="btnFinalize" class="btn btn-primary btn-sm">
      <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
      <span class="btn-text"><i class="fas fa-check me-1"></i> Finalize</span>
    </button>

  </div>
</div>

<div class="card">
  <div class="card-header">Daftar Item</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm mb-0 align-middle" id="tblItems">
        <thead class="table-light">
          <tr>
            <th style="width:1%">#</th>
            <th>Material</th>
            <th>Stor. Loc</th>
            <th class="text-end">Qty Unrest.</th>
            <th class="text-end">Qty Transit+Transfer</th>
            <th class="text-end">Qty Blocked</th>
            <th class="text-end">Qty Total</th>
            <th class="text-end">Terhitung</th>
            <th class="text-end">Selisih</th>
            <th>Catatan</th>
            <th style="width:1%">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="11" class="text-center text-muted">Memuat…</td>
          </tr>
        </tbody>
        <tfoot class="table-light">
          <tr>
            <th colspan="3" class="text-end">TOTAL</th>
            <th class="text-end" id="tSysU">0</th>
            <th class="text-end" id="tSysTT">0</th>
            <th class="text-end" id="tSysB">0</th>
            <th class="text-end" id="tSysTot">0</th>
            <th class="text-end" id="tCnt">0</th>
            <th class="text-end" id="tDiff">0</th>
            <th colspan="2"></th>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="small text-muted" id="pagingInfo">—</div>
      <nav>
        <ul class="pagination pagination-sm mb-0" id="paging"></ul>
      </nav>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAdd" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="formAdd">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Item</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" data-role="error"></div>

        <div class="mb-2">
          <label class="form-label">Material (ketik untuk cari)</label>
          <input id="fMaterial" name="material" class="form-control" list="dlMaterials" required>
          <datalist id="dlMaterials"></datalist>
          <div class="form-text">Ambil dari tabel barang.</div>
        </div>

        <div class="mb-2">
          <label class="form-label">Storage Location</label>
          <input id="fSloc" name="storage_location" class="form-control" required>
        </div>

        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label">Qty Unrestricted</label>
            <input id="fSysU" name="qty_unrestricted" type="number" class="form-control" step="0.001" value="0">
          </div>
          <div class="col-6">
            <label class="form-label">Qty Transit+Transfer</label>
            <input id="fSysTT" name="qty_transit_and_transfer" type="number" class="form-control" step="0.001"
              value="0">
          </div>
        </div>

        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label">Qty Blocked</label>
            <input id="fSysB" name="qty_blocked" type="number" class="form-control" step="0.001" value="0">
          </div>
          <div class="col-6">
            <label class="form-label">Counted Qty</label>
            <input id="fCounted" name="counted_qty" type="number" class="form-control" step="0.001" value="0" required>
            <div class="form-text">Diff = Counted - (Unrest. + TT + Blocked)</div>
          </div>
        </div>

        <div class="mt-2">
          <label class="form-label">Catatan</label>
          <textarea name="note" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalImport" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="formImport">
      <div class="modal-header">
        <h5 class="modal-title">Import Excel</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small mb-2">
          Header yang didukung (gaya barang):
          <code>Material</code>, <code>Material Description</code>, <code>Plant</code>, <code>Material Group</code>,
          <code>Storage Location</code>, <code>Descr. of Storage Loc.</code>, <code>DF stor. loc. level</code>,
          <code>Base Unit of Measure</code>, <code>Unrestricted</code>, <code>Transit and Transfer</code>,
          <code>Blocked</code>, <code>Material Type</code>, <code>Counted</code> (opsional), <code>Note</code>
          (opsional).
          Minimal wajib: <strong>Material</strong>.
        </p>
        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-control" required>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" id="btnUploadImport" type="submit">
          <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
          <span class="btn-text">Upload</span>
        </button>
      </div>
    </form>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  const sessionId = <?= (int) $sessionId ?>;
  const API_Opname = <?= json_encode($api) ?>;

  const state = { page: 1, perPage: 25, sort: 'diff_desc', q: '', plant: '', lastMeta: null };

  const $tblBody = document.querySelector('#tblItems tbody');
  const $tSysU = document.getElementById('tSysU');
  const $tSysTT = document.getElementById('tSysTT');
  const $tSysB = document.getElementById('tSysB');
  const $tSysTot = document.getElementById('tSysTot');
  const $tCnt = document.getElementById('tCnt');
  const $tDiff = document.getElementById('tDiff');

  const $fMat = document.getElementById('fMaterial');
  const $fSloc = document.getElementById('fSloc');
  const $fSysU = document.getElementById('fSysU');
  const $fSysTT = document.getElementById('fSysTT');
  const $fSysB = document.getElementById('fSysB');

  const $selSort = document.getElementById('selSort');
  const $selPer = document.getElementById('selPerPage');
  const $paging = document.getElementById('paging');
  const $pagingInfo = document.getElementById('pagingInfo');
  const $btnFinalize = document.getElementById('btnFinalize');

  const $inpSearch = document.getElementById('inpSearch');
  const $selPlant = document.getElementById('selPlant');

  function num(n) { return Number(n || 0) }
  function notify(type, msg) {
    try {
      const host = document.getElementById('toastArea') || (() => {
        const d = document.createElement('div'); d.id = 'toastArea';
        d.className = 'toast-container position-fixed top-0 end-0 p-3'; d.style.zIndex = 1080;
        document.body.appendChild(d); return d;
      })();
      const el = document.createElement('div'); el.className = 'toast align-items-center border-0 show';
      el.innerHTML = `<div class="d-flex"><div class="toast-body ${type === 'success' ? 'text-bg-success' : 'text-bg-danger'} p-2 rounded-start">${msg}</div><button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
      host.appendChild(el); setTimeout(() => el.remove(), 3500);
    } catch { alert(msg) }
  }
  function withQuery(url, params) {
    const q = new URLSearchParams(params);
    return url + (url.includes('?') ? '&' : '?') + q.toString();
  }
  async function apiFetch(url, opt = {}) {
    const token = localStorage.getItem('access_token');
    const headers = { 'Content-Type': 'application/json', ...(opt.headers || {}) };
    if (token && !headers.Authorization) headers.Authorization = `Bearer ${token}`;
    const res = await fetch(url, { ...opt, headers, credentials: 'same-origin' });
    const j = await res.json().catch(() => ({}));
    if (!res.ok) throw j || new Error('Request error');
    return j;
  }

  async function loadSession() {
    const j = await apiFetch(API_Opname.session);
    const s = j.data?.session || j.data || {};
    document.getElementById('sessInfo').textContent =
      `${s.code ?? ''} • Jadwal: ${s.scheduled_at ?? '-'} • Finalized: ${s.finalized_at ?? '-'}`;
    $btnFinalize.classList.toggle('d-none', !!s.finalized_at);
  }

  function renderPagination(meta) {
    state.lastMeta = meta;
    const { page, per_page, total, total_pages } = meta;
    $pagingInfo.textContent = `Halaman ${page} / ${total_pages} • ${per_page} per halaman • Total ${total} item`;
    const maxBtns = 7; let start = Math.max(1, page - Math.floor(maxBtns / 2)); let end = Math.min(total_pages, start + maxBtns - 1);
    if ((end - start + 1) < maxBtns) start = Math.max(1, end - maxBtns + 1);
    const btn = (label, p, disabled = false, active = false) =>
      `<li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`;
    let html = '';
    html += btn('«', 1, page === 1); html += btn('‹', page - 1, page === 1);
    for (let i = start; i <= end; i++) html += btn(i, i, false, i === page);
    html += btn('›', page + 1, page === total_pages); html += btn('»', total_pages, page === total_pages);
    $paging.innerHTML = html;
  }

  async function loadItems() {
    const url = withQuery(API_Opname.items, {
      page: state.page, per_page: state.perPage, sort: state.sort, q: state.q, plant: state.plant
    });
    const j = await apiFetch(url);
    const items = j.data || [];
    const meta = j.meta || { page: 1, per_page: items.length, total: items.length, total_pages: 1, sort: state.sort };
    renderPagination(meta);

    const plants = (meta.plants && meta.plants.length)
      ? meta.plants
      : Array.from(new Set(items.map(r => r.plant).filter(Boolean))).sort();
    if ($selPlant.dataset.filled !== '1') {
      $selPlant.innerHTML = `<option value="">All Plants</option>` +
        plants.map(p => `<option value="${p}">${p}</option>`).join('');
      $selPlant.dataset.filled = '1';
    }

    if (!items.length) {
      $tblBody.innerHTML = `<tr><td colspan="11" class="text-center text-muted">Belum ada item.</td></tr>`;
      [$tSysU, $tSysTT, $tSysB, $tSysTot, $tCnt, $tDiff].forEach(el => el.textContent = '0');
      return;
    }

    let i = (meta.page - 1) * meta.per_page,
      sumU = 0, sumTT = 0, sumB = 0, sumTot = 0, sumCnt = 0, sumDiff = 0;

    $tblBody.innerHTML = items.map(r => {
      const u = num(r.qty_unrestricted);
      const tt = num(r.qty_transit_and_transfer);
      const b = num(r.qty_blocked);
      const sysTot = u + tt + b;
      const cnt = num(r.counted_qty);
      const diff = cnt - sysTot;

      sumU += u; sumTT += tt; sumB += b; sumTot += sysTot; sumCnt += cnt; sumDiff += diff;

      return `<tr>
        <td>${++i}</td>
        <td>${r.material || '-'}</td>
        <td>${r.storage_location || '-'}</td>
        <td class="text-end">${u}</td>
        <td class="text-end">${tt}</td>
        <td class="text-end">${b}</td>
        <td class="text-end fw-semibold">${sysTot}</td>
        <td class="text-end">
          <input data-id="${r.id}" class="form-control form-control-sm text-end inp-cnt" type="number" step="0.001" value="${cnt}">
        </td>
        <td class="text-end ${diff === 0 ? '' : (diff > 0 ? 'text-success fw-semibold' : 'text-danger fw-semibold')}">${diff}</td>
        <td>${r.note || ''}</td>
        <td class="text-nowrap">
          <button class="btn btn-sm btn-outline-danger" data-del="${r.id}" title="Hapus"><i class="fas fa-trash"></i></button>
        </td>
      </tr>`;
    }).join('');

    $tSysU.textContent = sumU;
    $tSysTT.textContent = sumTT;
    $tSysB.textContent = sumB;
    $tSysTot.textContent = sumTot;
    $tCnt.textContent = sumCnt;
    $tDiff.textContent = sumDiff;
  }

  document.getElementById('btnAdd').onclick = () => new bootstrap.Modal('#modalAdd').show();
  document.getElementById('btnImport').onclick = () => new bootstrap.Modal('#modalImport').show();

  document.getElementById('fMaterial').addEventListener('input', async (e) => {
    const q = e.target.value.trim(); const dl = document.getElementById('dlMaterials'); if (q.length < 2) return;
    try {
      const list = await apiFetch('<?= base_url('api/v1/barang') ?>?q=' + encodeURIComponent(q));
      const items = list.data?.items || list.data || [];
      dl.innerHTML = items.slice(0, 10).map(b => `<option value="${b.material}">${b.storage_location || ''}</option>`).join('');
    } catch { }
  });

  async function fetchBarangDetail(material, sloc) {
    const url = '<?= base_url('api/v1/barang') ?>'
      + '?material=' + encodeURIComponent(material)
      + (sloc ? '&storage_location=' + encodeURIComponent(sloc) : '');
    try {
      const res = await apiFetch(url);
      const b = (res.data?.items?.[0]) || res.data || null;
      if (!b) return null;
      return { u: num(b.qty_unrestricted), tt: num(b.qty_transit_and_transfer), b: num(b.qty_blocked), sloc: b.storage_location || sloc };
    } catch { return null; }
  }
  async function tryFillSystem() {
    const mat = $fMat.value.trim(); const sl = $fSloc.value.trim(); if (!mat) return;
    const d = await fetchBarangDetail(mat, sl); if (!d) return;
    $fSloc.value = d.sloc || sl;
    if (!$fSysU.value || $fSysU.value === '0') $fSysU.value = d.u;
    if (!$fSysTT.value || $fSysTT.value === '0') $fSysTT.value = d.tt;
    if (!$fSysB.value || $fSysB.value === '0') $fSysB.value = d.b;
  }
  $fMat.addEventListener('change', tryFillSystem);
  $fSloc.addEventListener('change', tryFillSystem);

  document.getElementById('formAdd').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());
    payload.counted_qty = +payload.counted_qty || 0;
    payload.qty_unrestricted = +payload.qty_unrestricted || 0;
    payload.qty_transit_and_transfer = +payload.qty_transit_and_transfer || 0;
    payload.qty_blocked = +payload.qty_blocked || 0;

    try {
      await apiFetch(API_Opname.items, { method: 'POST', body: JSON.stringify(payload) });
      bootstrap.Modal.getInstance(document.getElementById('modalAdd')).hide();
      e.target.reset();
      notify('success', 'Item ditambahkan.');
      state.page = 1; loadItems();
    } catch (err) { notify('danger', err.message || 'Gagal menambah item.'); }
  });

  document.querySelector('#tblItems tbody').addEventListener('change', async (e) => {
    const inp = e.target.closest('.inp-cnt'); if (!inp) return;
    const id = inp.dataset.id; const val = +inp.value || 0;
    try {
      await apiFetch(`${API_Opname.items}/${id}`, { method: 'PUT', body: JSON.stringify({ counted_qty: val }) });
      loadItems();
    } catch (err) { notify('danger', err.message || 'Gagal update.'); }
  });

  document.querySelector('#tblItems tbody').addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-del]'); if (!btn) return;
    if (!confirm('Hapus item ini?')) return;
    try {
      await apiFetch(`${API_Opname.items}/${btn.dataset.del}`, { method: 'DELETE' });
      state.page = 1; loadItems();
    } catch (err) { notify('danger', err.message || 'Gagal menghapus.'); }
  });

  document.getElementById('formImport').addEventListener('submit', async (e) => {
    e.preventDefault();

    const fd = new FormData(e.target);
    const btnUpload = document.getElementById('btnUploadImport');
    const spinner = btnUpload.querySelector('.spinner-border');
    const btnText = btnUpload.querySelector('.btn-text');

    btnUpload.disabled = true;
    spinner.classList.remove('d-none');
    btnText.textContent = 'Uploading...';

    try {
      const token = localStorage.getItem('access_token');
      const res = await fetch(`${API_Opname.items}/import`, {
        method: 'POST',
        headers: token ? { Authorization: `Bearer ${token}` } : {},
        body: fd,
        credentials: 'same-origin'
      });

      if (!res.ok) throw new Error(`Server error ${res.status}`);

      bootstrap.Modal.getInstance(document.getElementById('modalImport')).hide();
      e.target.reset();
      notify('success', 'Import selesai.');
      state.page = 1;
      loadItems();
    } catch (err) {
      console.error(err);
      notify('danger', err.message || 'Gagal import.');
    } finally {
      btnUpload.disabled = false;
      spinner.classList.add('d-none');
      btnText.textContent = 'Upload';
    }
  });

  document.getElementById('btnFinalize').onclick = async () => {
    if (!confirm('Finalize sesi ini dan unduh hasilnya?')) return;

    const token = localStorage.getItem('access_token');
    const url = API_Opname.finalize;
    const btnFinalize = document.getElementById('btnFinalize');
    const spinner = btnFinalize.querySelector('.spinner-border');
    const btnText = btnFinalize.querySelector('.btn-text');

    try {
      btnFinalize.disabled = true;
      spinner.classList.remove('d-none');
      btnText.textContent = 'Processing...';

      const res = await fetch(url, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
        credentials: 'same-origin',
      });

      if (!res.ok) throw new Error(`Server error ${res.status}`);

      const disposition = res.headers.get('Content-Disposition');
      let filename = 'StockOpname_Final.xlsx';
      if (disposition && disposition.includes('filename=')) {
        filename = disposition.split('filename=')[1].replace(/"/g, '');
      }

      const blob = await res.blob();
      const urlBlob = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = urlBlob;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(urlBlob);

      notify('success', 'Sesi difinalkan dan file berhasil diunduh.');
      await loadSession();
    } catch (err) {
      console.error(err);
      notify('danger', err.message || 'Gagal finalize.');
    } finally {
      btnFinalize.disabled = false;
      spinner.classList.add('d-none');
      btnText.innerHTML = '<i class="fas fa-check me-1"></i> Finalize';
    }
  };


  document.getElementById('selSort').addEventListener('change', () => { state.sort = selSort.value; state.page = 1; loadItems(); });
  document.getElementById('selPerPage').addEventListener('change', () => { state.perPage = parseInt(selPerPage.value, 10) || 25; state.page = 1; loadItems(); });
  document.getElementById('paging').addEventListener('click', (e) => {
    const a = e.target.closest('a[data-page]'); if (!a) return; e.preventDefault();
    const p = parseInt(a.dataset.page, 10); if (Number.isNaN(p)) return;
    state.page = Math.max(1, p); loadItems();
  });

  let tSearch;
  $inpSearch.addEventListener('input', () => {
    clearTimeout(tSearch);
    tSearch = setTimeout(() => { state.q = $inpSearch.value.trim(); state.page = 1; loadItems(); }, 300);
  });
  $selPlant.addEventListener('change', () => { state.plant = $selPlant.value; state.page = 1; loadItems(); });

  (async function init() { await loadSession(); await loadItems(); })();
</script>
<?= $this->endSection() ?>