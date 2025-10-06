<?php ?>
<?= $this->extend('layouts/sbadmin_local') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-end mb-3">
  <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalOpname">
    <i class="fas fa-plus me-1"></i> Buat Sesi
  </button>
</div>

<div class="card">
  <div class="card-header">Stock Opname</div>
  <div class="card-body">
    <p class="text-muted mb-0">Klik <strong>Buat Sesi</strong> untuk membuat sesi opname. Daftar sesi akan muncul di
      bawah.</p>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Daftar Sesi</span>
    <div class="d-flex gap-2">
      <button id="btnRefresh" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-sync-alt me-1"></i> Refresh
      </button>
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle" id="tblSessions">
        <thead class="table-light">
          <tr>
            <th style="width:1%;">#</th>
            <th>Kode</th>
            <th>Jadwal</th>
            <th>Finalized</th>
            <th>Catatan</th>
            <th style="width:1%;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="6" class="text-center text-muted">Memuat data...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= view('components/modal/modal-form', [
  'modalId' => 'modalOpname',
  'formId' => 'modalOpnameForm',
  'title' => 'Buat Sesi Stock Opname',
  'api' => base_url('api/v1/stock-opname/sessions'),
  'method' => 'POST',
  'submitText' => 'Buat Sesi',
  'size' => 'md',
  'split' => 1,
  'fields' => [
    ['name' => 'scheduled_at', 'label' => 'Jadwal Opname', 'type' => 'datetime-local', 'required' => false],
    ['name' => 'note', 'label' => 'Catatan', 'type' => 'textarea', 'placeholder' => 'Catatan (opsional)'],
  ],
]) ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
  (function () {
    window.notify = window.notify || function (type, message) {
      try {
        const area = document.getElementById("toastArea") || (function () {
          const d = document.createElement("div");
          d.id = "toastArea";
          d.className = "toast-container position-fixed top-0 end-0 p-3";
          d.style.zIndex = 1080;
          document.body.appendChild(d);
          return d;
        })();
        const el = document.createElement("div");
        el.className = "toast align-items-center border-0 show";
        el.innerHTML = `
        <div class="d-flex">
          <div class="toast-body ${type === 'success' ? 'text-bg-success' : 'text-bg-danger'} p-2 rounded-start">
            ${message}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>`;
        area.appendChild(el);
        setTimeout(() => el.remove(), 3500);
      } catch (e) { alert(message); }
    };
  })();
</script>

<script>
  document.getElementById('modalOpname')?.addEventListener('show.bs.modal', () => {
    const id = 'modalOpnameForm_scheduled_at';
    const el = document.getElementById(id);
    if (!el || el.value) return;
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    el.value = d.toISOString().slice(0, 16); 
  });

  window.addEventListener('modal:beforeSubmit', (ev) => {
    if (ev.detail?.modalId !== 'modalOpname') return;
    const form = document.getElementById('modalOpnameForm');
    if (!form) return;
    const el = form.querySelector('input[name="scheduled_at"]');
    if (el && el.value) {
      const normalized = el.value.replace('T', ' ') + ':00';
      el.value = normalized;
    }
  });

  window.addEventListener('modal:success', (ev) => {
    if (ev.detail?.modalId === 'modalOpname') {
      notify('success', 'Sesi berhasil dibuat.');
      loadSessions();
    }
  });

  document.getElementById('btnRefresh')?.addEventListener('click', loadSessions);

  function fmtDateTime(s) {
    if (!s) return '-';
    const dt = new Date(s.replace(' ', 'T'));
    if (isNaN(dt.getTime())) return s;
    return dt.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
  }

  async function loadSessions() {
    const url = '<?= base_url('api/v1/stock-opname/sessions') ?>';
    const tb = document.querySelector('#tblSessions tbody');
    tb.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Memuat data...</td></tr>`;
    try {
      const json = await apiFetch(url);
      const rows = (json?.data ?? []);
      if (!rows.length) {
        tb.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Belum ada sesi.</td></tr>`;
        return;
      }
      tb.innerHTML = rows.map((s, i) => {
        const isFinal = !!s.finalized_at;
        const itemsUrl = '<?= base_url('admin/opname') ?>' + '/' + s.id; 
        return `
          <tr>
            <td>${i + 1}</td>
            <td class="fw-semibold">${s.code ?? '-'}</td>
            <td>${fmtDateTime(s.scheduled_at)}</td>
            <td>${isFinal ? ('✅ ' + fmtDateTime(s.finalized_at)) : '—'}</td>
            <td>${(s.note ?? '').toString().replace(/</g, '&lt;')}</td>
            <td class="text-nowrap">
              <a class="btn btn-sm btn-outline-primary me-1" href="${itemsUrl}">
                <i class="fas fa-list me-1"></i> Items
              </a>
              ${isFinal ? '' : `
                <button class="btn btn-sm btn-success" data-id="${s.id}" onclick="finalizeSess(${s.id})">
                  <i class="fas fa-check me-1"></i> Finalize
                </button>
              `}
            </td>
          </tr>
        `;
      }).join('');
    } catch (e) {
      tb.innerHTML = `<tr><td colspan="6" class="text-danger text-center">Gagal memuat data.</td></tr>`;
    }
  }

  async function finalizeSess(id) {
    if (!confirm('Finalize sesi ini? Setelah final, item tidak dapat diubah.')) return;
    const url = '<?= base_url('api/v1/stock-opname/sessions') ?>' + '/' + id + '/finalize';
    try {
      const json = await apiFetch(url, { method: 'POST' });
      if (json?.success) {
        notify('success', 'Sesi berhasil difinalkan.');
        loadSessions();
      } else {
        notify('error', json?.message || 'Gagal finalize.');
      }
    } catch (e) {
      notify('error', 'Gagal finalize.');
    }
  }

  document.addEventListener('DOMContentLoaded', loadSessions);
</script>

<script src="<?= base_url('sbadmin/js/modal-form.js') ?>"></script>
<?= $this->endSection() ?>