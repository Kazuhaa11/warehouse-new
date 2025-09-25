<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #1e58e7;
            min-height: 100vh;
        }

        .login-card {
            max-width: 430px;
            width: 100%;
        }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center">
    <div class="card shadow login-card">
        <div class="card-body p-4 p-md-5">
            <h4 class="text-center mb-4">Login</h4>

            <div id="alert" class="alert alert-danger d-none" role="alert"></div>

            <form id="loginForm" novalidate>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" placeholder="you@example.com" required>
                    <div class="invalid-feedback">Email wajib diisi</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" placeholder="••••••••" required>
                    <div class="invalid-feedback">Password wajib diisi</div>
                </div>

                <button id="btnSubmit" class="btn btn-primary w-100" type="submit">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="btnSpinner"></span>
                    Log in
                </button>
            </form>

            <p class="text-muted small mt-3 mb-0">
                contoh: admin@example.com / admin123
            </p>
        </div>
    </div>

    <script src="<?= base_url('js/api.js') ?>"></script>

    <script>
        const apiLoginUrl = "<?= esc($apiLoginUrl) ?>";
        const redirectAdminUrl = "<?= esc($redirectAdminUrl) ?>";
        const redirectMobileUrl = "<?= esc($redirectMobileUrl) ?>";

        const form = document.getElementById('loginForm');
        const btn = document.getElementById('btnSubmit');
        const spinner = document.getElementById('btnSpinner');
        const alertEl = document.getElementById('alert');

        function showError(msg) { alertEl.textContent = msg || 'Login gagal.'; alertEl.classList.remove('d-none'); }
        function hideError() { alertEl.classList.add('d-none'); alertEl.textContent = ''; }
        function setBusy(b) { btn.disabled = b; spinner.classList.toggle('d-none', !b); }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideError();

            const email = document.getElementById('email');
            const pass = document.getElementById('password');

            if (!email.value) { email.classList.add('is-invalid'); return; } else email.classList.remove('is-invalid');
            if (!pass.value) { pass.classList.add('is-invalid'); return; } else pass.classList.remove('is-invalid');

            setBusy(true);
            try {
                const res = await fetch(apiLoginUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: email.value,
                        password: pass.value,
                    })
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    const msg = data?.messages?.error || data?.message || 'Login gagal.';
                    showError(msg);
                    setBusy(false);
                    return;
                }

                // simpan token
                localStorage.setItem('access_token', data.access_token);
                localStorage.setItem('refresh_token', data.refresh_token || '');
                localStorage.setItem('token_type', data.token_type || 'Bearer');

                if (data.expires_in) {
                    const expAt = Date.now() + (data.expires_in * 1000);
                    localStorage.setItem('access_token_expires_at', String(expAt));
                }
                if (data.user) {
                    localStorage.setItem('user', JSON.stringify(data.user));
                }

                try {
                    const me = await apiFetch("<?= base_url('api/v1/auth/me') ?>");
                    const role = (me?.role || data.user?.role || '').toLowerCase();
                    window.location.href = role === 'admin' ? redirectAdminUrl : redirectMobileUrl;
                } catch (e) {
                    const role = (data.user?.role || '').toLowerCase();
                    window.location.href = role === 'admin' ? redirectAdminUrl : redirectMobileUrl;
                }
            } catch (err) {
                console.error(err);
                showError('Gagal menghubungi server.');
            } finally {
                setBusy(false);
            }
        });
    </script>

</body>

</html>