<?= $this->extend('layouts/sbadmin_local') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm col-lg-5 col-md-6 col-sm-12">
    <div class="card-body">
        <h4 class="mb-3">Ubah Password</h4>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= esc($_GET['error']) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= esc($_GET['success']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('admin/change-password') ?>">

            <div class="mb-3">
                <label class="form-label">Password Lama</label>
                <input type="password" name="old_password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>

            <div class="mb-3">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="6">
            </div>

            <button class="btn btn-primary">Simpan Perubahan</button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
