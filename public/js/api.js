// public/js/api.js

// ===== API Helper untuk Warehouse CI4 =====

const API = {
  getAccessToken: () => localStorage.getItem("access_token") || "",
  getRefreshToken: () => localStorage.getItem("refresh_token") || "",
  getTokenType: () => localStorage.getItem("token_type") || "Bearer",

  setTokens: ({ access_token, refresh_token, expires_in, token_type }) => {
    if (access_token) localStorage.setItem("access_token", access_token);
    if (refresh_token !== undefined)
      localStorage.setItem("refresh_token", refresh_token || "");
    if (token_type) localStorage.setItem("token_type", token_type);
    if (typeof expires_in === "number") {
      const expAt = Date.now() + expires_in * 1000;
      localStorage.setItem("access_token_expires_at", String(expAt));
    }
  },

  clear: () => {
    localStorage.removeItem("access_token");
    localStorage.removeItem("refresh_token");
    localStorage.removeItem("token_type");
    localStorage.removeItem("access_token_expires_at");
    localStorage.removeItem("user");
  },
};

async function apiFetch(url, options = {}) {
  const opts = { method: "GET", credentials: "same-origin", ...options };
  const headers = new Headers(opts.headers || {});

  // set Content-Type hanya bila body bukan FormData dan belum diset
  if (
    !headers.has("Content-Type") &&
    opts.body &&
    !(opts.body instanceof FormData)
  ) {
    headers.set("Content-Type", "application/json");
  }

  // tambahkan Authorization hanya jika ada token
  const at = API.getAccessToken();
  const tt = API.getTokenType();
  if (at && !headers.has("Authorization")) {
    headers.set("Authorization", `${tt} ${at}`);
  }
  opts.headers = headers;

  let res = await fetch(url, opts);
  if (res.status !== 401) return parseJson(res);

  // ==== Kalau 401, coba refresh token sekali ====
  const rt = API.getRefreshToken();
  if (!rt) throw await errorFromResponse(res);

  const refreshRes = await fetch("/api/v1/auth/refresh", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "same-origin",
    body: JSON.stringify({ refresh_token: rt }),
  });

  if (!refreshRes.ok) {
    API.clear();
    throw await errorFromResponse(refreshRes);
  }

  const refreshData = await refreshRes.json();
  API.setTokens(refreshData);

  // ulangi request asli dengan token baru
  const retry = { ...opts, headers: new Headers(headers) };
  retry.headers.set(
    "Authorization",
    `${API.getTokenType()} ${API.getAccessToken()}`
  );
  res = await fetch(url, retry);
  return parseJson(res);
}

async function parseJson(res) {
  const ct = (res.headers.get("Content-Type") || "").toLowerCase();
  if (ct.includes("application/json")) {
    const data = await res.json();
    if (!res.ok) {
      const err = new Error(
        data?.message || data?.messages?.error || `HTTP ${res.status}`
      );
      err.data = data;
      err.response = res;
      throw err;
    }
    return data;
  }
  if (!res.ok) {
    const text = await res.text();
    const err = new Error(text || `HTTP ${res.status}`);
    err.data = { message: text };
    err.response = res;
    throw err;
  }
  return res;
}

async function errorFromResponse(res) {
  try {
    const j = await res.json();
    const err = new Error(
      j?.message || j?.messages?.error || `HTTP ${res.status}`
    );
    err.data = j;
    err.response = res;
    return err;
  } catch {
    const t = await res.text();
    const err = new Error(t || `HTTP ${res.status}`);
    err.data = { message: t };
    err.response = res;
    return err;
  }
}

// logout global
async function apiLogout({ endpoint, redirectUrl } = {}) {
  const token = API.getAccessToken();
  const rtoken = API.getRefreshToken();
  try {
    await fetch(endpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        ...(token ? { Authorization: `${API.getTokenType()} ${token}` } : {}),
      },
      body: JSON.stringify({ refresh_token: rtoken || "" }),
    });
  } catch (e) {
    console.warn("logout API error:", e);
  } finally {
    API.clear();
    window.location.href = redirectUrl || "/";
  }
}

window.apiFetch = apiFetch;
window.API = API;
