<?= $this->extend('layouts/sbadmin_local') ?>

<?= $this->section('content') ?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <form id="filterForm" class="d-flex flex-wrap gap-2 align-items-center">
    <div class="input-group input-group-sm">
      <input type="text" class="form-control form-control-sm" name="q" placeholder="Cari no nota / catatan"
        value="<?= esc(service('request')->getGet('q') ?? '') ?>" style="min-width:220px">
      <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
    </div>
    <select class="form-select form-select-sm" name="plant" style="min-width:120px">
      <option value="">All Plant</option>
      <option value="1200">Plant 1200</option>
      <option value="1300">Plant 1300</option>
    </select>
    <button type="button" id="btnReset" class="btn btn-sm btn-outline-secondary">Reset</button>
  </form>
  <button id="btnCetak" class="btn btn-success ms-auto btn-sm">
    <i class="fas fa-file-invoice me-1"></i> Cetak Laporan
  </button>
  <button id="btnAdd" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Tambah Peminjaman
  </button>
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

<?= view('components/modal/modal-form', [
  'modalId' => 'modalDetailPinjam',
  'title' => 'Detail Peminjaman',
  'api' => '#',
  'method' => 'GET',
  'submitText' => 'Tutup',
  'size' => 'xl',
  'split' => 4,
  'fields' => [
    ['name' => 'no_nota', 'label' => 'No Nota', 'type' => 'text'],
    ['name' => 'borrow_date', 'label' => 'Tanggal', 'type' => 'text'],
    ['name' => 'plant', 'label' => 'Plant', 'type' => 'text'],
    ['name' => 'status', 'label' => 'Status', 'type' => 'text'],
    ['name' => 'peminjam', 'label' => 'Peminjam', 'type' => 'text'],
    ['name' => 'created_at', 'label' => 'Dibuat', 'type' => 'text'],
    ['name' => 'note', 'label' => 'Catatan', 'type' => 'textarea'],
  ],
]) ?>
<?= view('components/modal/modal-form', [
  'modalId' => 'modalAddPinjam',
  'title' => 'Tambah Peminjaman',
  'method' => 'POST',
  'submitText' => 'Simpan',
  'size' => 'lg',
  'split' => 2,
  'fields' => [
    ['name' => 'tanggal', 'label' => 'Tanggal Peminjaman', 'type' => 'date', 'required' => true],
    [
      'name' => 'plant',
      'label' => 'Plant',
      'type' => 'select',
      'required' => true,
      'options' => [
        ['value' => '1200', 'label' => 'Plant 1200'],
        ['value' => '1300', 'label' => 'Plant 1300'],
      ]
    ],
    ['name' => 'due_date', 'label' => 'Tanggal Jatuh Tempo', 'type' => 'date'],
    ['name' => 'note', 'label' => 'Catatan', 'type' => 'textarea'],
    [
      'name' => 'search_barang',
      'label' => 'Cari Barang',
      'type' => 'text',
      'placeholder' => 'Ketik nama atau material barang...',
      'required' => true
    ],
    [
      'name' => 'qty',
      'label' => 'Jumlah',
      'type' => 'number',
      'step' => '1',
      'value' => '1',
      'required' => true
    ],
  ],
]) ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const PEMINJAMAN_API = '<?= base_url('api/v1/peminjaman') ?>';
    const BARANG_API = '<?= base_url('api/v1/barang') ?>';
    const PER_PAGE = 50;

    const form = document.getElementById('filterForm');
    const qInput = form.querySelector('input[name="q"]');
    const plantSel = form.querySelector('select[name="plant"]');
    const btnReset = document.getElementById('btnReset');
    const tbody = document.getElementById('tbody-pinjam');
    const pager = document.getElementById('pager');
    const metaText = document.getElementById('metaText');
    const modalDetailEl = document.getElementById('modalDetailPinjam');
    const modalDetail = new bootstrap.Modal(modalDetailEl);
    const formEl = modalDetailEl.querySelector('form[data-modal-form]');
    const formId = formEl ? formEl.id : 'modalDetailPinjamForm';
    const btnAdd = document.getElementById('btnAdd');
    const modalAddEl = document.getElementById('modalAddPinjam');
    const modalAdd = modalAddEl ? new bootstrap.Modal(modalAddEl) : null;
    const formAdd = modalAddEl ? modalAddEl.querySelector('form[data-modal-form]') : null;

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
    })();

    form.addEventListener('submit', e => {
      e.preventDefault();
      load(1);
    });
    btnReset.addEventListener('click', () => {
      qInput.value = '';
      plantSel.value = '';
      load(1);
    });

    async function load(page = 1) {
      const params = new URLSearchParams();
      if (qInput.value.trim()) params.set('q', qInput.value.trim());
      if (plantSel.value) params.set('plant', plantSel.value);
      params.set('per_page', PER_PAGE);
      params.set('page', page);
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Memuat data...</td></tr>`;
      pager.innerHTML = '';
      metaText.textContent = '—';
      try {
        const res = await fetch(`${PEMINJAMAN_API}?${params.toString()}`);
        const json = await res.json();
        if (!json.success) throw new Error(json?.error?.message || 'Gagal memuat');
        renderRows(json.data || []);
        renderPager(json.meta || {
          page,
          total_pages: 1,
          total: 0
        });
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
      const total = meta.total_pages || 1;
      const current = meta.page || 1;
      pager.innerHTML = '';

      function item(p, label = p, disabled = false, active = false) {
        return `<li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
      <a class="page-link" href="#" data-p="${p}">${label}</a></li>`;
      }
      pager.insertAdjacentHTML('beforeend', item(current - 1, '&laquo;', current <= 1));
      for (let p = 1; p <= total; p++) pager.insertAdjacentHTML('beforeend', item(p, p, false, p === current));
      pager.insertAdjacentHTML('beforeend', item(current + 1, '&raquo;', current >= total));
      pager.querySelectorAll('a.page-link').forEach(a => a.addEventListener('click', e => {
        e.preventDefault();
        const p = parseInt(a.dataset.p, 10);
        if (!isNaN(p)) load(p);
      }));
    }

    tbody.addEventListener('click', e => {
      const btn = e.target.closest('button[data-action="detail"]');
      if (!btn) return;
      openDetail(btn.dataset.id);
    });

    async function openDetail(id) {
      const itemsBody = document.getElementById('pinjamItemsBody');
      const itemsMeta = document.getElementById('pinjamItemsMeta');
      itemsBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Memuat items...</td></tr>';
      itemsMeta.textContent = 'Memuat...';
      modalDetail.show();

      try {
        const res = await fetch(`${PEMINJAMAN_API}/${id}`);
        const json = await res.json();
        if (!json?.success) throw new Error(json?.error?.message || 'Gagal memuat detail');

        const h = json.data?.header || {};
        const items = Array.isArray(json.data?.items) ? json.data.items : [];
        const mapped = {
          no_nota: h.nomor,
          peminjam: h.peminjam_username,
          tanggal: h.tanggal,
          plant: h.plants,
          status: h.status,
          note: h.note,
          created_at: h.created_at,
        };

        if (formEl) {
          formEl.querySelectorAll('input, textarea, select').forEach((el) => {
            const name = el.getAttribute('name');
            if (name && mapped[name] !== undefined) {
              el.value = mapped[name] ?? '';
            }
          });
        }
        renderDetailItems(items);
      } catch (err) {
        itemsBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${esc(err.message)}</td></tr>`;
        itemsMeta.textContent = '—';
      }
    }


    function renderDetailItems(items) {
      const body = document.getElementById('pinjamItemsBody');
      const meta = document.getElementById('pinjamItemsMeta');
      if (!items.length) {
        body.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Tidak ada item</td></tr>';
        meta.textContent = '0 item';
        return;
      }
      body.innerHTML = items.map((it, i) => `
  <tr>
    <td class="text-center">${i + 1}</td>
    <td>${esc(it.material ?? '')}</td>
    <td>${esc(it.material_description ?? '')}</td>
    <td>${esc(it.plant ?? '')}</td>
    <td>${esc(it.storage_location ?? '')}</td>
    <td class="text-end">${esc(it.qty ?? it.requested_qty ?? 1)}</td>
    <td>${esc(it.uom ?? it.base_unit_of_measure ?? '')}</td>
  </tr>`).join('');
      meta.textContent = `${items.length} item`;
    }


    function badge(s) {
      switch ((s || '').toLowerCase()) {
        case 'draft':
          return 'secondary';
        case 'submitted':
          return 'info';
        case 'approved':
          return 'success';
        case 'returned':
          return 'primary';
        case 'rejected':
          return 'danger';
        case 'loaned':
          return 'warning';
        case 'lost':
          return 'dark';
        default:
          return 'light';
      }
    }

    function esc(s) {
      return String(s ?? '').replace(/[&<>"']/g, m => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[m]));
    }

    async function initSearchBarang() {
      const input = document.getElementById(formAdd.id + '_search_barang');
      if (!input) return;
      input.parentElement.style.position = 'relative';
      const list = document.createElement('ul');
      list.className = 'list-group position-absolute w-100';
      list.style = 'z-index:1056; max-height:200px; overflow:auto; display:none;';
      input.parentElement.appendChild(list);
      const hiddenId = document.createElement('input');
      hiddenId.type = 'hidden';
      hiddenId.name = 'barang_id';
      input.parentElement.appendChild(hiddenId);
      let timer = null;
      input.addEventListener('input', () => {
        clearTimeout(timer);
        const val = input.value.trim();
        if (!val) {
          list.style.display = 'none';
          hiddenId.value = '';
          return;
        }
        timer = setTimeout(() => searchBarang(val, list, hiddenId, input), 400);
      });
      document.addEventListener('click', e => {
        if (!input.parentElement.contains(e.target)) list.style.display = 'none';
      });
    }

    async function searchBarang(keyword, list, hiddenId, input) {
      try {
        list.innerHTML = `<li class="list-group-item small text-muted">Mencari...</li>`;
        list.style.display = 'block';
        const res = await fetch(`${BARANG_API}?q=${encodeURIComponent(keyword)}&per_page=10`);
        const j = await res.json();
        if (!j.success) throw new Error('Gagal memuat barang');
        const items = j.data || [];
        if (!items.length) {
          list.innerHTML = `<li class="list-group-item small text-muted">Tidak ditemukan</li>`;
          return;
        }
        list.innerHTML = items.map(b => `
        <li class="list-group-item list-group-item-action" data-id="${b.id}">
          <div class="fw-semibold">${b.material} — ${b.material_description}</div>
          <small class="text-muted">${b.plant ?? ''} | Stok: ${b.qty_unrestricted ?? '-'}</small>
        </li>`).join('');
        list.querySelectorAll('li[data-id]').forEach(li => {
          li.addEventListener('click', () => {
            hiddenId.value = li.dataset.id;
            input.value = li.querySelector('.fw-semibold').textContent;
            list.style.display = 'none';
          });
        });
      } catch (e) {
        list.innerHTML = `<li class="list-group-item small text-danger">${e.message}</li>`;
      }
    }

    if (btnAdd && modalAdd && formAdd) {
      const errBox = formAdd.querySelector('[data-role="error"]');
      btnAdd.addEventListener('click', async () => {
        formAdd.reset();
        errBox.classList.add('d-none');
        await initSearchBarang();
        modalAdd.show();
      });

      formAdd.addEventListener('submit', async e => {
        e.preventDefault();
        errBox.classList.add('d-none');
        const fd = new FormData(formAdd);
        const barangId = fd.get('barang_id');
        const qty = parseFloat(fd.get('qty')) || 1;
        if (!barangId || isNaN(barangId)) {
          errBox.textContent = 'Silakan pilih barang dari hasil pencarian.';
          errBox.classList.remove('d-none');
          return;
        }
        const data = {
          tanggal: fd.get('tanggal'),
          due_date: fd.get('due_date'),
          plant: fd.get('plant'),
          note: fd.get('note'),
          items: [{
            barang_id: Number(barangId),
            qty: qty
          }]
        };

        const btn = formAdd.querySelector('button[type="submit"]');
        btn.disabled = true;
        const old = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        try {
          const res = await fetch(PEMINJAMAN_API, {
            method: formAdd.dataset.method || 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
          });
          const j = await res.json();
          if (!j.success) throw new Error(j?.error?.message || 'Gagal menyimpan');
          modalAdd.hide();
          alert('Peminjaman berhasil ditambahkan!');
          load(1);
        } catch (err) {
          errBox.textContent = err.message;
          errBox.classList.remove('d-none');
        } finally {
          btn.disabled = false;
          btn.innerHTML = old;
        }
      });
    }

    load(1);

    const btnCetak = document.getElementById("btnCetak");
    if (btnCetak) {
      btnCetak.addEventListener("click", async () => {
        try {
          btnCetak.disabled = true;
          btnCetak.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1"></span> Mencetak...';
          const url = `${PEMINJAMAN_API}/report/pdf?dl=1`;

          const res = await fetch(url);
          if (!res.ok) throw new Error("Gagal membuat laporan PDF");

          const blob = await res.blob();
          const fileUrl = window.URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.href = fileUrl;
          a.download = `Laporan_Bon_Pinjam_${new Date()
            .toISOString()
            .slice(0, 10)}.pdf`;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          window.URL.revokeObjectURL(fileUrl);
        } catch (err) {
          alert(err.message);
        } finally {
          btnCetak.disabled = false;
          btnCetak.innerHTML =
            '<i class="fas fa-file-invoice me-1"></i> Cetak Laporan';
        }
      });
    }
  });
</script>
<?= $this->endSection() ?>