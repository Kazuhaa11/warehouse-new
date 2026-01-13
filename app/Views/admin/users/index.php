<?= $this->extend('layouts/sbadmin_local') ?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <form id="filterForm" class="d-flex flex-wrap gap-2 align-items-center">
        <div class="input-group input-group-sm">
            <input type="text"
                class="form-control form-control-sm"
                name="q"
                placeholder="Cari nama / email"
                style="min-width:220px">
            <button class="btn btn-sm btn-primary">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" name="plant">
                <option value="">All Plant</option>
                <option value="1200">Plant 1200</option>
                <option value="1300">Plant 1300</option>
            </select>

            <select class="form-select form-select-sm" name="active">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>

        <button type="button" id="btnReset" class="btn btn-sm btn-outline-secondary">
            Reset
        </button>
    </form>

    <button id="btnAdd" class="btn btn-primary btn-sm ms-auto">
        <i class="fas fa-plus me-1"></i> Tambah User
    </button>
</div>

<div class="card">
    <div class="card-header">Manajemen User</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle table-bordered">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th width="120">Role</th>
                        <th width="100">Plant</th>
                        <th width="120">Status</th>
                        <th width="110" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-users">
                    <tr>
                        <td colspan="7" class="text-center text-muted">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= view('components/modal/modal-form', [
    'modalId' => 'modalCreateUser',
    'title' => 'Tambah User',
    'method' => 'POST',
    'submitText' => 'Simpan',
    'size' => 'lg',
    'split' => 3,
    'fields' => [
        ['name' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
        ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true],
        [
            'name' => 'role',
            'label' => 'Role',
            'type' => 'select',
            'required' => true,
            'options' => [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'super_admin', 'label' => 'Super Admin'],
                ['value' => 'mobile', 'label' => 'Mobile User'],
            ]
        ],
        [
            'name' => 'plant',
            'label' => 'Plant (khusus Admin)',
            'type' => 'select',
            'options' => [
                ['value' => '1200', 'label' => 'Plant 1200'],
                ['value' => '1300', 'label' => 'Plant 1300'],
            ]
        ],
    ]
]) ?>

<?= view('components/modal/modal-form', [
    'modalId' => 'modalEditUser',
    'title' => 'Update User',
    'method' => 'PUT',
    'submitText' => 'Update',
    'size' => 'lg',
    'split' => 3,
    'fields' => [
        ['name' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
        ['name' => 'password', 'label' => 'Password (opsional)', 'type' => 'password'],
        [
            'name' => 'role',
            'label' => 'Role',
            'type' => 'select',
            'required' => true,
            'options' => [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'super_admin', 'label' => 'Super Admin'],
                ['value' => 'mobile', 'label' => 'Mobile User'],
            ]
        ],
        [
            'name' => 'plant',
            'label' => 'Plant (khusus Admin)',
            'type' => 'select',
            'options' => [
                ['value' => '1200', 'label' => 'Plant 1200'],
                ['value' => '1300', 'label' => 'Plant 1300'],
            ]
        ],
        [
            'name' => 'active',
            'label' => 'Status',
            'type' => 'select',
            'options' => [
                ['value' => '1', 'label' => 'Active'],
                ['value' => '0', 'label' => 'Inactive'],
            ]
        ],
    ]
]) ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {

        const API = '<?= base_url('admin/users') ?>';

        const tbody = document.getElementById('tbody-users');
        const formFilter = document.getElementById('filterForm');

        const modalCreateEl = document.getElementById('modalCreateUser');
        const modalEditEl = document.getElementById('modalEditUser');

        const modalCreate = new bootstrap.Modal(modalCreateEl);
        const modalEdit = new bootstrap.Modal(modalEditEl);

        const formCreate = modalCreateEl.querySelector('form');
        const formEdit = modalEditEl.querySelector('form');

        let editId = null;

        function handleRolePlant(form) {
            const role = form.querySelector("select[name='role']");
            const plant = form.querySelector("select[name='plant']");
            const wrapper = plant?.closest('.mb-2');

            if (!role || !plant || !wrapper) return;

            const toggle = () => {
                if (role.value === 'super_admin') {
                    wrapper.style.display = 'none';
                    plant.value = '';
                } else {
                    wrapper.style.display = '';
                }
            };

            role.addEventListener('change', toggle);
            toggle();
        }

        handleRolePlant(formCreate);
        handleRolePlant(formEdit);

        async function load() {
            const params = new URLSearchParams(new FormData(formFilter));
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Memuat data...</td></tr>`;

            try {
                const res = await fetch(`${API}/data?${params.toString()}`);
                const j = await res.json();

                if (!j.success || !j.data.length) {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>`;
                    return;
                }

                tbody.innerHTML = j.data.map((u, i) => `
                <tr>
                    <td>${i + 1}</td>
                    <td>${esc(u.username)}</td>
                    <td>${esc(u.email)}</td>
                    <td>${esc(u.role)}</td>
                    <td>${u.plant ?? 'ALL'}</td>
                    <td>${u.active == 1 ? 'Active' : 'Inactive'}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary"
                            data-action="edit" data-id="${u.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${u.active == 1 ? `
                        <button class="btn btn-sm btn-outline-danger"
                            data-action="delete" data-id="${u.id}">
                            <i class="fas fa-trash"></i>
                        </button>` : ``}
                    </td>
                </tr>
            `).join('');

            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center">Gagal memuat data</td></tr>`;
            }
        }

        formFilter.addEventListener('submit', e => {
            e.preventDefault();
            load();
        });

        document.getElementById('btnReset').addEventListener('click', () => {
            formFilter.reset();
            load();
        });

        document.getElementById('btnAdd').addEventListener('click', () => {
            formCreate.reset();
            handleRolePlant(formCreate);
            modalCreate.show();
        });

        formCreate.addEventListener('submit', async e => {
            e.preventDefault();

            const data = Object.fromEntries(new FormData(formCreate));

            if (data.role === 'admin' && !data.plant) {
                alert('Role Admin wajib memilih Plant');
                return;
            }

            const res = await fetch(API, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const j = await res.json();

            if (!j.success) {
                alert(j.message);
                return;
            }

            modalCreate.hide();
            alert('User berhasil dibuat');
            load();
        });

        tbody.addEventListener('click', async e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;

            const id = btn.dataset.id;
            const action = btn.dataset.action;

            if (action === 'delete') {
                if (!confirm('Nonaktifkan user ini?')) return;

                const res = await fetch(`${API}/${id}`, {
                    method: 'DELETE'
                });
                const j = await res.json();

                if (!j.success) {
                    alert(j.message);
                    return;
                }

                alert('User berhasil dinonaktifkan');
                load();
            }

            if (action === 'edit') {
                const res = await fetch(`${API}/data`);
                const j = await res.json();
                const user = j.data.find(x => x.id == id);

                editId = id;
                fillForm(formEdit, user);
                modalEdit.show();
            }
        });

        formEdit.addEventListener('submit', async e => {
            e.preventDefault();

            const data = Object.fromEntries(new FormData(formEdit));
            data.active = data.active ? 1 : 0;

            if (data.role === 'admin' && !data.plant) {
                alert('Role Admin wajib memilih Plant');
                return;
            }

            const res = await fetch(`${API}/${editId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const j = await res.json();

            if (!j.success) {
                alert(j.message);
                return;
            }

            modalEdit.hide();
            alert('User berhasil diperbarui');
            load();
        });

        function fillForm(form, data) {
            form.querySelectorAll('input,select').forEach(el => {
                el.value = data[el.name] ?? '';
            });
            handleRolePlant(form);
        }

        function esc(s) {
            return String(s ?? '').replace(/[&<>"']/g, m => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            } [m]));
        }
        load();
    });
</script>

<?= $this->endSection() ?>