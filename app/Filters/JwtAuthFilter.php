<?php

namespace App\Filters;

use App\Libraries\Auth as AuthCtx;
use App\Libraries\JwtService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class JwtAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($request->getMethod() === 'options') {
            return;
        }

        $authHeader = $request->getHeaderLine('Authorization');

        if ($authHeader === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = (string) $_SERVER['HTTP_AUTHORIZATION'];
        }

        if ($authHeader === '') {
            $cookieToken = $request->getCookie('access_token');
            if ($cookieToken) {
                $authHeader = 'Bearer ' . $cookieToken;
            }
        }

        if (stripos($authHeader, 'Bearer ') !== 0) {
            return service('response')
                ->setJSON(['message' => 'Unauthorized'])
                ->setStatusCode(401);
        }

        $token = trim(substr($authHeader, 7));

        try {
            $jwt  = new JwtService();
            $data = $jwt->validate($token);

            $userId = (int) ($data['sub'] ?? 0);
            if ($userId <= 0) {
                return service('response')
                    ->setJSON(['message' => 'Invalid token subject'])
                    ->setStatusCode(401);
            }

            $db = \Config\Database::connect();
            $user = $db->table('users')
                ->select('id, username, role, plant')
                ->where('id', $userId)
                ->get()
                ->getRowArray();

            if (!$user) {
                return service('response')
                    ->setJSON(['message' => 'User not found'])
                    ->setStatusCode(401);
            }

            AuthCtx::setUser([
                'id'       => (int) $user['id'],
                'username' => $user['username'],
                'role'     => strtolower((string) $user['role']),
                'plant'    => $user['plant'],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'JWT error: {msg}', ['msg' => $e->getMessage()]);

            return service('response')
                ->setJSON(['message' => 'Invalid or expired token'])
                ->setStatusCode(401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        AuthCtx::clear();
    }
}
