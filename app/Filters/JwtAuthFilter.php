<?php

namespace App\Filters;

use App\Libraries\Auth as AuthCtx;
use App\Libraries\JwtService;
use App\Models\UserIdentityModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class JwtAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if ($authHeader === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = (string) $_SERVER['HTTP_AUTHORIZATION'];
        }

        if ($authHeader === '') {
            $cookieToken = method_exists($request, 'getCookie')
                ? $request->getCookie('access_token')
                : ($_COOKIE['access_token'] ?? null);
            if (!empty($cookieToken)) {
                $authHeader = 'Bearer ' . $cookieToken;
            }
        }

        if (stripos($authHeader, 'Bearer ') !== 0) {
            return service('response')->setJSON(['message' => 'Unauthorized'])->setStatusCode(401);
        }

        $token = trim(substr($authHeader, 7));

        try {
            $jwt = new JwtService();
            $data = $jwt->validate($token);
            $sub = (int) ($data['sub'] ?? 0);
            if ($sub <= 0) {
                return service('response')->setJSON(['message' => 'Invalid token subject'])->setStatusCode(401);
            }

            $idModel = new UserIdentityModel();
            $identity = $idModel->where('type', 'email_password')
                ->where('user_id', $sub)
                ->orderBy('id', 'DESC')
                ->first();

            if (!$identity) {
                return service('response')->setJSON(['message' => 'User not found'])->setStatusCode(401);
            }

            $user = $idModel->buildUserFromIdentity($identity);
            AuthCtx::setUser($user);

        } catch (\Throwable $e) {
            log_message('error', 'JWT validate failed: {msg}', ['msg' => $e->getMessage()]);
            return service('response')->setJSON(['message' => 'Invalid/Expired token'])->setStatusCode(401);
        }

        return;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {

    }
}
