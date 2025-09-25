<?= $this->extend('layouts/sbadmin_local') ?>
<?= $this->section('content') ?>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card border-primary">
      <div class="card-header bg-primary text-white">Import Excel Barang</div>
      <div class="card-body">
        <form id="importForm">
          <div class="mb-3">
            <label class="form-label">File Excel (.xlsx / .xls)</label>
            <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
          </div>
          <button class="btn btn-primary btn-sm" type="submit">
            <i class="fas fa-upload me-1"></i> Upload & Import
          </button>
        </form>
        <div id="importResult" class="mt-3"></div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card border-success">
      <div class="card-header bg-success text-white">Export Excel Barang</div>
      <div class="card-body">
        <a href="<?= base_url('api/v1/barang/export') ?>" class="btn btn-success btn-sm">
          <i class="fas fa-download me-1"></i> Download Excel
        </a>
      </div>
    </div>
  </div>
</div>

<?= $this->section('scripts') ?>
<script>
  document.getElementById('importForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    const box = document.getElementById('importResult');
    box.innerHTML = 'Uploading...';

    try {
      const res = await fetch('<?= base_url('api/v1/barang/import') ?>', { method: 'POST', body: fd });
      const json = await res.json();
      if (!res.ok || !json.success) throw new Error(json?.error?.message || 'Import gagal');

      const { ins, upd, skip, errs } = json.data;
      box.innerHTML = `
      <div class="alert alert-success mb-2">Import OK. Inserted: ${ins}, Updated: ${upd}, Skipped: ${skip}.</div>
      ${errs && errs.length ? '<div class="alert alert-warning"><b>Detail:</b><ul>' + errs.map(e => '<li>' + e + '</li>').join('') + '</ul></div>' : ''}
    `;
    } catch (err) {
      box.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
    }
  });
</script>
<?= $this->endSection() ?>
<?= $this->endSection() ?>