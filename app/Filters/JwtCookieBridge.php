<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class JwtCookieBridge implements FilterInterface
{
    protected string $loginUrl = '/';
    protected string $forbiddenUrl = '/forbidden';

    protected function isHtmlRequest(RequestInterface $request): bool
    {
        $path = trim($request->getUri()->getPath(), '/');
        // Hindari str_starts_with untuk kompatibilitas luas
        if (strpos($path, 'api/') === 0) {
            return false;
        }
        $accept = (string) ($request->getHeaderLine('Accept') ?: '');
        return $accept === '' || stripos($accept, 'text/html') !== false;
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        // Jika sudah ada Bearer header, biarkan
        $authLine = $request->getHeaderLine('Authorization');
        if (stripos($authLine, 'Bearer ') === 0) {
            return;
        }

        // Ambil token dari cookie (kompatibel CI4 ^4.0)
        $cookieToken = method_exists($request, 'getCookie')
            ? $request->getCookie('access_token')
            : ($_COOKIE['access_token'] ?? null);

        if (!empty($cookieToken)) {
            // Di CI4 lawas, setHeader() bisa tidak tersedia → cukup isi superglobal
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $cookieToken;
            // Tidak perlu return $request; cukup kembalikan null
            return;
        }

        // Tidak ada token di header/cookie → kalau HTML, redirect ke login
        if ($this->isHtmlRequest($request)) {
            $returnTo = current_url(true);
            return redirect()->to($this->loginUrl . '?return_to=' . urlencode((string) $returnTo));
        }

        // Untuk API JSON biarkan lanjut; filter JWT akan jawab 401
        return;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (!$this->isHtmlRequest($request)) {
            return;
        }

        $code = (int) $response->getStatusCode();
        if ($code === 401) {
            $returnTo = current_url(true);
            return redirect()->to($this->loginUrl . '?return_to=' . urlencode((string) $returnTo));
        }
        if ($code === 403) {
            return redirect()->to($this->forbiddenUrl);
        }
        return;
    }
}
