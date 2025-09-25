(function () {
  // ======= toast kecil =======
  function notify(type, message) {
    const area =
      document.getElementById("toastArea") ||
      (() => {
        const d = document.createElement("div");
        d.id = "toastArea";
        d.className = "toast-container position-fixed top-0 end-0 p-3";
        d.style.zIndex = 1080;
        document.body.appendChild(d);
        return d;
      })();

    const el = document.createElement("div");
    el.className =
      "toast text-white " + (type === "success" ? "bg-success" : "bg-danger");
    el.role = "alert";
    el.ariaLive = "assertive";
    el.ariaAtomic = "true";
    el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                data-bs-dismiss="toast" aria-label="Close"></button>
      </div>`;
    area.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 3000 });
    t.show();
    el.addEventListener("hidden.bs.toast", () => el.remove());
  }

  // ===== error box di dalam form =====
  function showErr(form, msg) {
    const box = form.querySelector('[data-role="error"]');
    if (!box) return;
    box.textContent = msg || "Terjadi kesalahan.";
    box.classList.remove("d-none");
  }
  function hideErr(form) {
    const box = form.querySelector('[data-role="error"]');
    if (!box) return;
    box.classList.add("d-none");
    box.textContent = "";
  }

  // ===== casting & serialize -> JSON =====
  function castValue(el, raw) {
    const cast = (el.dataset.cast || "").toLowerCase();
    const toFloat = (v) =>
      v === "" || v == null ? null : Number.isNaN(+v) ? null : parseFloat(v);
    const toInt = (v) =>
      v === "" || v == null ? null : Number.isNaN(+v) ? null : parseInt(v, 10);

    if (cast === "float" || cast === "number") return toFloat(raw);
    if (cast === "int" || cast === "integer") return toInt(raw);
    if (cast === "bool" || cast === "boolean") return !!raw;
    if (cast === "string") return raw == null ? "" : String(raw);

    if (el.type === "number") return toFloat(raw);
    const val = (raw ?? "").toString().trim();
    return val === "" ? null : val;
  }

  function serializeForm(form) {
    const payload = {};
    form
      .querySelectorAll("input[name], select[name], textarea[name]")
      .forEach((el) => {
        if (el.disabled || !el.name) return;

        if (el.type === "radio") {
          if (!el.checked) return;
          payload[el.name] = castValue(el, el.value);
          return;
        }
        if (el.type === "checkbox") {
          payload[el.name] = !!el.checked; 
          return;
        }
        if (el.tagName === "SELECT" && el.multiple) {
          payload[el.name] = Array.from(el.selectedOptions || []).map((o) =>
            castValue(el, o.value)
          );
          return;
        }
        payload[el.name] = castValue(el, el.value);
      });
    return payload;
  }

  function formatValidationError(json) {
    if (json?.error?.details && typeof json.error.details === "object") {
      const msgs = Object.entries(json.error.details).map(
        ([k, v]) => `${k}: ${Array.isArray(v) ? v.join(", ") : v}`
      );
      if (msgs.length) return msgs.join(" | ");
    }
    return json?.error?.message || "Validasi gagal";
  }

  // ===== SUBMIT (POST/PUT kirim JSON, tanpa spoof) =====
  async function submitModalForm(e) {
    const form = e.target.closest("form[data-modal-form]");
    if (!form) return;
    e.preventDefault();
    hideErr(form);

    const url = form.dataset.api;
    const method = (form.dataset.method || "POST").toUpperCase();
    const btn = form.querySelector('button[type="submit"]');

    const modalId = form.closest(".modal")?.id || null;
    const detail = { modalId, payload: serializeForm(form) };
    window.dispatchEvent(new CustomEvent("modal:beforeSubmit", { detail }));
    const payload = detail.payload; 
    try {
      if (btn) btn.disabled = true;

      const json = await apiFetch(url, {
        method,
        body: JSON.stringify(payload),
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          Accept: "application/json",
        },
      });

      if (!json?.success) throw new Error(formatValidationError(json));

      // Sukses
      form.reset();
      const modalEl = form.closest(".modal");
      if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
      notify(
        "success",
        method === "PUT"
          ? "Data berhasil diperbarui."
          : "Data berhasil disimpan."
      );

      window.dispatchEvent(
        new CustomEvent("modal:success", {
          detail: { modalId, data: json.data ?? null },
        })
      );
    } catch (err) {
      const msg = String(err.message || "");
      if (msg.includes("API error 401") || msg.includes("API error 403")) {
        notify("danger", "Sesi berakhir. Silakan login kembali.");
        setTimeout(() => (window.location.href = "<?= base_url('/') ?>"), 800);
        return;
      }
      showErr(form, msg);
      notify("danger", msg);
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  document.addEventListener("submit", function (e) {
    if (e.target.matches("form[data-modal-form]")) submitModalForm(e);
  });
})();
