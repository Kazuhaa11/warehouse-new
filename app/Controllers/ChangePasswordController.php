<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\JwtService;
use App\Models\UserIdentityModel;

class ChangePasswordController extends BaseController
{
    public function index()
    {
        return view('change_password/change_password', [
            'title' => 'Ubah Password',
            'menu'  => ''
        ]);
    }

    public function process()
    {
        $rules = [
            'old_password'     => 'required',
            'new_password'     => 'required|min_length[6]',
            'confirm_password' => 'required|matches[new_password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->to('admin/change-password?error=Input tidak valid');
        }

        $jwt = $this->request->getCookie('access_token');
        if (!$jwt) {
            return redirect()->to('admin/change-password?error=Tidak terautentikasi');
        }

        try {
            $payload = (new JwtService())->validate($jwt);
        } catch (\Throwable $e) {
            return redirect()->to('admin/change-password?error=Token tidak valid');
        }

        $userId = (int) ($payload['sub'] ?? 0);
        if ($userId <= 0) {
            return redirect()->to('admin/change-password?error=User tidak valid');
        }

        $identityModel = new UserIdentityModel();
        $identity = $identityModel
            ->where('user_id', $userId)
            ->where('type', 'email_password')
            ->first();

        if (!$identity) {
            return redirect()->to('admin/change-password?error=Data user tidak ditemukan');
        }

        if (!password_verify($this->request->getPost('old_password'), $identity['secret2'])) {
            return redirect()->to('admin/change-password?error=Password lama salah');
        }

        $newHash = password_hash($this->request->getPost('new_password'), PASSWORD_BCRYPT);

        $identityModel->update($identity['id'], [
            'secret2' => $newHash
        ]);

        return redirect()->to('admin/change-password?success=Password berhasil diubah');
    }
}
