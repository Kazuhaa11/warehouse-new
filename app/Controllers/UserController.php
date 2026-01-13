<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Auth as AuthCtx;
use Config\Database;
use CodeIgniter\HTTP\ResponseInterface;

class UserController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    private function assertSuperAdmin(): ?ResponseInterface
    {
        $user = AuthCtx::user();

        if (!$user || strtolower((string)($user['role'] ?? '')) !== 'super_admin') {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'message' => 'Forbidden: super_admin required'
                ]);
        }

        return null;
    }

    public function index()
    {
        $user = AuthCtx::user();

        if (!$user || strtolower($user['role']) !== 'super_admin') {
            return redirect()->to('/login');
        }

        return view('admin/users/index');
    }

    public function data()
    {
        if ($res = $this->assertSuperAdmin()) {
            return $res;
        }

        $q      = trim((string)($this->request->getGet('q') ?? ''));
        $plant  = $this->request->getGet('plant');
        $active = $this->request->getGet('active');

        $builder = $this->db->table('users u')
            ->select('
                u.id,
                u.username,
                u.role,
                u.plant,
                u.active,
                u.created_at,
                ai.secret AS email
            ')
            ->join(
                'auth_identities ai',
                'ai.user_id = u.id AND ai.type = "email_password"',
                'left'
            );

        if ($q !== '') {
            $builder->groupStart()
                ->like('u.username', $q)
                ->orLike('ai.secret', $q)
                ->groupEnd();
        }

        if ($plant !== null && $plant !== '') {
            $builder->where('u.plant', $plant);
        }

        if ($active !== null && $active !== '') {
            $builder->where('u.active', (int)$active);
        }

        $rows = $builder
            ->orderBy('u.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'data' => $rows
        ]);
    }

    public function store()
    {
        if ($res = $this->assertSuperAdmin()) {
            return $res;
        }

        $data = $this->request->getJSON(true);

        if (!$data) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Invalid JSON payload'
            ]);
        }

        $exists = $this->db->table('auth_identities')
            ->where('type', 'email_password')
            ->where('secret', $data['email'])
            ->countAllResults();

        if ($exists) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Email sudah digunakan'
            ]);
        }

        if ($data['role'] === 'super_admin') {
            $data['plant'] = null;
        }

        if (
            in_array($data['role'], ['admin', 'mobile'], true) &&
            empty($data['plant'])
        ) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Role Admin & Mobile wajib memiliki plant'
            ]);
        }

        $this->db->transStart();

        $this->db->table('users')->insert([
            'username'   => $data['username'],
            'role'       => $data['role'],
            'plant'      => $data['plant'] ?? null,
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $userId = (int)$this->db->insertID();

        $this->db->table('auth_identities')->insert([
            'user_id'    => $userId,
            'type'       => 'email_password',
            'name'       => $data['username'],
            'secret'     => $data['email'],
            'secret2'    => password_hash($data['password'], PASSWORD_BCRYPT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();

        return $this->response->setJSON([
            'success' => true,
            'message' => 'User berhasil dibuat'
        ]);
    }

    public function update($id)
    {
        if ($res = $this->assertSuperAdmin()) {
            return $res;
        }

        $data = $this->request->getJSON(true);

        if (!$data) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Invalid JSON payload'
            ]);
        }

        if ($data['role'] === 'super_admin') {
            $data['plant'] = null;
        }

        if (
            in_array($data['role'], ['admin', 'mobile'], true) &&
            empty($data['plant'])
        ) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Role Admin & Mobile wajib memiliki plant'
            ]);
        }

        $this->db->transStart();

        $this->db->table('users')
            ->where('id', $id)
            ->update([
                'username'   => $data['username'],
                'role'       => $data['role'],
                'plant'      => $data['plant'] ?? null,
                'active'     => !empty($data['active']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if (!empty($data['password'])) {
            $this->db->table('auth_identities')
                ->where('user_id', $id)
                ->where('type', 'email_password')
                ->update([
                    'secret'     => $data['email'],
                    'secret2'    => password_hash($data['password'], PASSWORD_BCRYPT),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        } else {
            $this->db->table('auth_identities')
                ->where('user_id', $id)
                ->where('type', 'email_password')
                ->update([
                    'secret'     => $data['email'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        $this->db->transComplete();

        return $this->response->setJSON([
            'success' => true,
            'message' => 'User berhasil diperbarui'
        ]);
    }

    public function delete($id)
    {
        if ($res = $this->assertSuperAdmin()) {
            return $res;
        }

        $actor = AuthCtx::user();
        if ((int)$actor['id'] === (int)$id) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Tidak boleh menonaktifkan akun sendiri'
            ]);
        }

        $this->db->table('users')
            ->where('id', $id)
            ->update([
                'active'     => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'User berhasil dinonaktifkan'
        ]);
    }
}
