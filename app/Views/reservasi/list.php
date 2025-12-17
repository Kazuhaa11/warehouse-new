<?= $this->extend('layouts/sbadmin_local') ?>

<?= $this->section('content') ?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">

    <form id="filterForm" class="d-flex flex-wrap gap-2 align-items-center">

        <div class="input-group input-group-sm">
            <input type="text" class="form-control form-control-sm" name="q"
                placeholder="Cari material / description / PO / reservation"
                style="min-width:220px">
            <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
        </div>

        <div class="row g-2">
            <div class="col-6">
                <select name="plant" class="form-select form-select-sm">
                    <option value="">All Plant</option>
                    <option value="1200">1200</option>
                    <option value="1300">1300</option>
                </select>
            </div>

            <div class="col-6">
                <select name="sloc" class="form-select form-select-sm">
                    <option value="">All SLoc</option>
                    <option value="2642">2642</option>
                    <option value="2691">2691</option>
                    <option value="3619">3619</option>
                    <option value="3691">3691</option>
                </select>
            </div>
        </div>

        <button type="button" id="btnReset" class="btn btn-sm btn-outline-secondary">Reset</button>
    </form>

    <button id="btnImport"
        class="btn btn-success btn-sm ms-auto"
        data-bs-toggle="modal" data-bs-target="#modalImport">
        <i class="fas fa-file-excel me-1"></i> Import Excel
    </button>

</div>

<div class="card">
    <div class="card-header fw-bold">List Reservasi</div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Material</th>
                        <th>Description</th>
                        <th>Plant</th>
                        <th>SLoc</th>
                        <th>Posting Date</th>
                        <th class="text-end">Qty</th>
                        <th>Reference</th>
                        <th style="width:80px">Detail</th>
                    </tr>
                </thead>

                <tbody id="tbody-reservasi">
                    <tr>
                        <td colspan="8" class="text-center text-muted">Memuat data...</td>
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


<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog">
        <form id="formImport" enctype="multipart/form-data" class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Import Excel Reservasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="file" name="file" accept=".xls,.xlsx" class="form-control" required>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-success btn-sm">Upload</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            </div>

        </form>
    </div>
</div>


<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Detail Reservasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="modalDetailBody">
                Memuat detail...
            </div>
        </div>
    </div>
</div>


<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const API = "<?= base_url('api/v1/reservasi') ?>";

        const tbody = document.getElementById("tbody-reservasi");
        const pager = document.getElementById("pager");
        const metaText = document.getElementById("metaText");

        const form = document.getElementById("filterForm");
        const q = form.querySelector("input[name='q']");
        const plant = form.querySelector("select[name='plant']");
        const sloc = form.querySelector("select[name='sloc']");
        const btnReset = document.getElementById("btnReset");

        let detailModal = null;

        form.addEventListener("submit", e => {
            e.preventDefault();
            load(1);
        });

        btnReset.addEventListener("click", () => {
            q.value = "";
            plant.value = "";
            sloc.value = "";
            load(1);
        });

        async function load(page = 1) {

            const params = new URLSearchParams({
                page,
                per_page: 50
            });

            if (q.value.trim()) params.set("q", q.value.trim());
            if (plant.value) params.set("plant", plant.value);
            if (sloc.value) params.set("sloc", sloc.value);

            tbody.innerHTML =
                `<tr><td colspan="8" class="text-center text-muted">Memuat data...</td></tr>`;
            pager.innerHTML = "";
            metaText.textContent = "—";

            try {
                const res = await fetch(`${API}?${params.toString()}`);
                const json = await res.json();

                if (!json.success) throw new Error("Gagal memuat data");

                renderRows(json.data);
                renderPager(json.meta);

                metaText.textContent =
                    `Halaman ${json.meta.page}/${json.meta.total_pages} • Total ${json.meta.total}`;

            } catch (err) {
                tbody.innerHTML =
                    `<tr><td colspan="8" class="text-center text-danger">${err.message}</td></tr>`;
            }
        }

        function renderRows(rows) {

            if (!rows.length) {
                tbody.innerHTML =
                    `<tr><td colspan="8" class="text-center text-muted">Tidak ada data</td></tr>`;
                return;
            }

            tbody.innerHTML = rows.map(r => {

                const qty = parseInt(r.qty_in_un_of_entry, 10) || 0;
                const qtyClass = qty < 0 ? "text-danger fw-bold" : "text-success fw-bold";

                return `
            <tr>
                <td>${esc(r.material)}</td>
                <td>${esc(r.material_description)}</td>
                <td>${esc(r.plant)}</td>
                <td>${esc(r.storage_location)}</td>
                <td>${esc(r.posting_date)}</td>
                <td class="text-end ${qtyClass}">${qty}</td>
                <td>${esc(r.reference)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary"
                            data-id="${r.id}" data-action="detail">
                        <i class="fa fa-eye"></i>
                    </button>
                </td>
            </tr>`;
            }).join("");
        }

        function renderPager(meta) {

            const total = meta.total_pages;
            const current = meta.page;

            pager.innerHTML = "";

            const add = (page, label, active = false, disabled = false) => {
                pager.insertAdjacentHTML("beforeend", `
            <li class="page-item ${active ? "active" : ""} ${disabled ? "disabled" : ""}">
                <a href="#" class="page-link" data-page="${page}">${label}</a>
            </li>`);
            };

            add(current - 1, "&laquo;", false, current === 1);

            for (let p = 1; p <= total; p++) {
                add(p, p, p === current);
            }

            add(current + 1, "&raquo;", false, current === total);

            pager.querySelectorAll("a[data-page]").forEach(a => {
                a.addEventListener("click", e => {
                    e.preventDefault();
                    load(parseInt(a.dataset.page, 10));
                });
            });
        }

        tbody.addEventListener("click", e => {
            const btn = e.target.closest("button[data-action='detail']");
            if (!btn) return;
            openDetail(btn.dataset.id);
        });

        async function openDetail(id) {

            detailModal = new bootstrap.Modal(
                document.getElementById("modalDetail")
            );

            const body = document.getElementById("modalDetailBody");
            body.innerHTML = "Memuat detail...";
            detailModal.show();

            try {
                const res = await fetch(`${API}/${id}`);
                const j = await res.json();

                if (!j.success) throw new Error("Gagal membaca detail");

                const r = j.data;
                const qty = parseInt(r.qty_in_un_of_entry, 10) || 0;

                body.innerHTML = `
                <form id="formUpdate" data-id="${r.id}">
                <table class="table table-bordered table-sm">
                    <tr><th>Material</th><td>${esc(r.material)}</td></tr>
                    <tr><th>Description</th><td>${esc(r.material_description)}</td></tr>
                    <tr><th>Plant</th><td>${esc(r.plant)}</td></tr>
                    <tr><th>SLoc</th><td>${esc(r.storage_location)}</td></tr>
                    <tr><th>Posting Date</th><td>${esc(r.posting_date)}</td></tr>

                    <tr>
                        <th>Qty</th>
                        <td>
                            <input type="number" step="1" min="0"
                                class="form-control form-control-sm"
                                name="qty_in_un_of_entry"
                                value="${qty}" required>
                        </td>
                    </tr>

                    <tr><th>User</th>
                        <td><input class="form-control form-control-sm"
                            name="user_name" value="${esc(r.user_name)}"></td>
                    </tr>

                    <tr><th>Batch</th>
                        <td><input class="form-control form-control-sm"
                            name="batch" value="${esc(r.batch)}"></td>
                    </tr>

                    <tr><th>Movement Type</th>
                        <td><input class="form-control form-control-sm"
                            name="movement_type" value="${esc(r.movement_type)}"></td>
                    </tr>

                    <tr><th>Material Doc</th>
                        <td><input class="form-control form-control-sm"
                            name="material_document" value="${esc(r.material_document)}"></td>
                    </tr>

                    <tr><th>Text</th>
                        <td><textarea class="form-control form-control-sm"
                            name="text">${esc(r.text)}</textarea></td>
                    </tr>
                </table>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-save"></i> Update
                    </button>
                </div>
            </form>`;
            } catch (err) {
                body.innerHTML = `<div class="text-danger">${err.message}</div>`;
            }
        }

        document.addEventListener("submit", async function(e) {

            const form = e.target;
            if (form.id !== "formUpdate") return;

            e.preventDefault();

            const id = form.dataset.id;
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());

            payload.qty_in_un_of_entry = parseInt(payload.qty_in_un_of_entry, 10) || 0;

            try {
                const res = await fetch(`${API}/${id}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const j = await res.json();

                if (!j.success) {
                    alert(j.message || "Gagal update");
                    return;
                }

                alert("Update berhasil");
                detailModal.hide();
                load(1);

            } catch (err) {
                alert(err.message);
            }
        });

        function esc(s) {
            return String(s ?? "").replace(/[&<>"']/g, m => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            } [m]));
        }
        const formImport = document.getElementById("formImport");

        formImport.addEventListener("submit", async function(e) {
            e.preventDefault();

            const fd = new FormData(formImport);

            try {
                const res = await fetch(`${API}/import`, {
                    method: "POST",
                    body: fd
                });

                const j = await res.json();

                if (!j.success) {
                    alert(j.message || "Import gagal");
                    return;
                }

                alert(`Import berhasil\nInserted: ${j.inserted}\nSkipped: ${j.skipped}`);

                bootstrap.Modal.getInstance(
                    document.getElementById("modalImport")
                ).hide();

                formImport.reset();
                load(1);

            } catch (err) {
                alert("Import error: " + err.message);
            }
        });

        load(1);
    });
</script>
<?= $this->endSection() ?>