<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\ReservasiListModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReservasiApi extends BaseApiController
{
    protected $model;
    protected $db;

    public function __construct()
    {
        $this->model = new ReservasiListModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $q     = trim((string) $this->request->getGet('q'));
        $plant = trim((string) $this->request->getGet('plant'));
        $sloc  = trim((string) $this->request->getGet('sloc'));

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(200, max(1, (int) ($this->request->getGet('per_page') ?? 50)));
        $offset = ($page - 1) * $perPage;

        $b = $this->db->table('reservasi_list r')
            ->select("
            r.id,
            r.material,
            r.material_description,
            r.plant,
            r.storage_location,
            r.posting_date,
            r.qty_in_un_of_entry,
            r.purchase_order,
            r.reservation,
            r.user_name,
            r.batch,
            r.movement_type,
            r.material_document,
            r.text
        ", false);

        if ($q !== '') {
            $b->groupStart()
                ->like('r.material', $q)
                ->orLike('r.material_description', $q)
                ->orLike('r.purchase_order', $q)
                ->orLike('r.reservation', $q)
                ->groupEnd();
        }

        if ($plant !== '') {
            $b->where('r.plant', $plant);
        }

        if ($sloc !== '') {
            $b->where('r.storage_location', $sloc);
        }

        $rows = $b
            ->orderBy('r.posting_date', 'DESC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        foreach ($rows as &$r) {
            if (!empty($r['purchase_order'])) {
                $r['reference'] = 'PO: ' . $r['purchase_order'];
            } elseif (!empty($r['reservation'])) {
                $r['reference'] = 'Resv: ' . $r['reservation'];
            } else {
                $r['reference'] = '-';
            }
        }
        unset($r);

        $count = $this->db->table('reservasi_list r');

        if ($q !== '') {
            $count->groupStart()
                ->like('r.material', $q)
                ->orLike('r.material_description', $q)
                ->orLike('r.purchase_order', $q)
                ->orLike('r.reservation', $q)
                ->groupEnd();
        }

        if ($plant !== '') {
            $count->where('r.plant', $plant);
        }

        if ($sloc !== '') {
            $count->where('r.storage_location', $sloc);
        }

        $total = (int) $count->countAllResults();

        return $this->ok($rows, [
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => (int) ceil($total / ($perPage ?: 1)),
        ]);
    }

    public function show($id)
    {
        $row = $this->db->table('reservasi_list')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$row) {
            return $this->failNotFound("Data tidak ditemukan");
        }

        return $this->respond([
            'success' => true,
            'data'    => $row
        ]);
    }

    public function update($id)
    {
        $payload = $this->request->getJSON(true);

        if (!$payload || !is_array($payload)) {
            return $this->fail('Payload kosong atau tidak valid');
        }

        $row = $this->db->table('reservasi_list')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$row) {
            return $this->failNotFound('Data reservasi tidak ditemukan');
        }
        $allowed = [
            'qty_in_un_of_entry',
            'user_name',
            'batch',
            'movement_type',
            'material_document',
            'text',
        ];

        $data = array_intersect_key($payload, array_flip($allowed));

        if (empty($data)) {
            return $this->fail('Tidak ada field yang boleh diupdate');
        }

        $this->db->transBegin();

        try {
            if (array_key_exists('qty_in_un_of_entry', $data)) {

                $oldQty = (int) $row['qty_in_un_of_entry'];
                $newQty = (int) round($data['qty_in_un_of_entry']);
                $delta  = $newQty - $oldQty;

                if ($delta !== 0.0) {

                    $barang = $this->db->table('barang')
                        ->select('qty_unrestricted')
                        ->where('material', $row['material'])
                        ->get()
                        ->getRowArray();

                    if (!$barang) {
                        throw new \RuntimeException('Barang tidak ditemukan');
                    }

                    $stokAwal = (float) $barang['qty_unrestricted'];
                    $stokAkhir = $stokAwal + $delta;

                    if ($stokAkhir < 0) {
                        throw new \RuntimeException(
                            "Update ditolak: stok akan menjadi minus ({$stokAkhir})"
                        );
                    }

                    $this->db->query(
                        "UPDATE barang 
                     SET qty_unrestricted = qty_unrestricted + ? 
                     WHERE material = ?",
                        [$delta, $row['material']]
                    );
                }
            }

            $data['updated_at'] = date('Y-m-d H:i:s');

            $this->db->table('reservasi_list')
                ->where('id', $id)
                ->update($data);

            if ($this->db->affectedRows() === 0) {
                $this->db->transRollback();
                return $this->respond([
                    'success' => true,
                    'message' => 'Tidak ada perubahan data'
                ]);
            }

            $this->db->transCommit();

            return $this->respond([
                'success' => true,
                'message' => 'Data berhasil diperbarui'
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            return $this->fail(
                'Gagal update: ' . $e->getMessage()
            );
        }
    }

    public function importExcel()
    {
        $file = $this->request->getFile('file');

        if (!$file || !$file->isValid()) {
            return $this->fail("File tidak valid");
        }

        $tmp = WRITEPATH . 'uploads/' . uniqid('reservasi_', true) . '.' . $file->getExtension();
        @is_dir(dirname($tmp)) || mkdir(dirname($tmp), 0775, true);
        $file->move(dirname($tmp), basename($tmp));

        $excel = new \App\Libraries\ReservasiExcel();

        try {
            [$ins, $skip] = $excel->import($tmp);
            @unlink($tmp);

            return $this->respond([
                'success' => true,
                'inserted' => $ins,
                'skipped' => $skip,
            ]);
        } catch (\Throwable $e) {
            @unlink($tmp);

            return $this->fail("Import gagal: " . $e->getMessage());
        }
    }
}
