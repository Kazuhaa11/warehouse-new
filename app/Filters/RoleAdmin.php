<?php
namespace App\Filters;

use App\Libraries\Auth as AuthCtx;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleAdmin implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = AuthCtx::user();
        if (!$user) {
            return service('response')->setJSON(['message' => 'Unauthenticated'])->setStatusCode(401);
        }
        $role = strtolower((string) ($user['role'] ?? ''));
        if ($role !== 'admin') {
            return service('response')->setJSON(['message' => 'Forbidden: role admin required'])->setStatusCode(403);
        }
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
