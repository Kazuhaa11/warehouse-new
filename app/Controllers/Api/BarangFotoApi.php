<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Files\File;

class BarangFotoApi extends ResourceController
{
    protected $format = 'json';

    public function upload($barangId = null)
    {
        helper(['filesystem']);
        $barangId = (int) $barangId;

        if (!$barangId) {
            return $this->failValidationErrors('Barang ID wajib diisi');
        }

        $file = $this->request->getFile('foto');
        if (!$file || !$file->isValid()) {
            return $this->failValidationErrors('File foto tidak valid');
        }

        $newName = $file->getRandomName();
        $path = WRITEPATH . '../public/uploads/barang/';

        if (!is_dir($path))
            mkdir($path, 0777, true);

        $file->move($path, $newName);

        $db = \Config\Database::connect();
        $db->table('barang_foto')->insert([
            'barang_id' => $barangId,
            'path' => $newName,
            'caption' => $this->request->getPost('caption'),
            'is_primary' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->respond([
            'success' => true,
            'message' => 'Foto berhasil diupload',
            'data' => [
                'path' => base_url('uploads/barang/' . $newName),
            ]
        ]);
    }

    public function delete($id = null)
    {
        $id = (int) $id;
        $db = \Config\Database::connect();

        $foto = $db->table('barang_foto')->where('id', $id)->get()->getRowArray();
        if (!$foto) {
            return $this->failNotFound('Foto tidak ditemukan');
        }

        $filePath = WRITEPATH . '../public/uploads/barang/' . $foto['path'];
        if (is_file($filePath))
            unlink($filePath);

        $db->table('barang_foto')->delete(['id' => $id]);

        return $this->respond(['success' => true, 'message' => 'Foto berhasil dihapus']);
    }
}
