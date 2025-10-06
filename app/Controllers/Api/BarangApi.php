<?php

namespace App\Controllers\Api;

use App\Libraries\BarangExcel;
use App\Models\BarangModel;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BarangApi extends BaseApiController
{
    protected BarangModel $barang;

    public function __construct()
    {
        $this->barang = new BarangModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();
        $req = $this->request;

        $q = trim((string) $req->getGet('q'));
        $plant = $req->getGet('plant');
        $sl = $req->getGet('storage_location');
        $slp = $req->getGet('storage_loc_prefix');
        $mg = $req->getGet('material_group');
        $mt = $req->getGet('material_type');
        $page = max(1, (int) ($req->getGet('page') ?? 1));
        $per = max(1, min(100, (int) ($req->getGet('per_page') ?? 20)));

        $b = $db->table('barang');

        if ($q !== '') {
            $b->groupStart()
                ->like('material', $q)
                ->orLike('material_description', $q)
                ->groupEnd();
        }
        if ($plant)
            $b->where('plant', $plant);
        if ($sl)
            $b->where('storage_location', $sl);
        if ($slp !== null && $slp !== '')
            $b->like('storage_location', $slp, 'after');
        if ($mg)
            $b->where('material_group', $mg);
        if ($mt)
            $b->where('material_type', $mt);

        $count = clone $b;
        $total = (int) $count->select('COUNT(*) AS c')->get()->getRow('c');

        $rows = $b->select('id, material, material_description, plant, material_group, storage_location, storage_location_desc, df_stor_loc_level, base_unit_of_measure, qty_unrestricted, qty_transit_and_transfer, qty_blocked, material_type, import_batch, created_at, updated_at')
            ->orderBy('material', 'ASC')
            ->limit($per, ($page - 1) * $per)
            ->get()->getResultArray();

        return $this->ok($rows, [
            'page' => $page,
            'per_page' => $per,
            'total' => $total,
            'total_pages' => (int) ceil($total / $per),
        ]);
    }

    public function show($id)
    {
        $db = \Config\Database::connect();
        $row = $db->table('barang')->select(
            'id, material, material_description, plant, material_group, storage_location, storage_location_desc,
             df_stor_loc_level, base_unit_of_measure, qty_unrestricted, qty_transit_and_transfer, qty_blocked,
             material_type, storage_id, import_batch, created_at, updated_at'
        )->where('id', (int) $id)->get()->getRowArray();

        if (!$row) {
            return $this->failMsg('Barang tidak ditemukan', 404);
        }
        return $this->ok($row);
    }

    public function update($id)
    {
        $db = \Config\Database::connect();
        $id = (int) $id;

        $exists = $db->table('barang')->select('id')->where('id', $id)->get()->getRowArray();
        if (!$exists) {
            return $this->failMsg('Barang tidak ditemukan', 404);
        }

        $p = $this->request->getJSON(true) ?? $this->request->getRawInput();

        $allowed = [
            'base_unit_of_measure',
            'material_type',
            'material_group',
            'storage_id',
        ];

        $data = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $p)) {
                $data[$k] = $p[$k] === '' ? null : $p[$k];
            }
        }

        if (empty($data)) {
            return $this->failMsg('Tidak ada perubahan.', 400);
        }

        try {
            $ok = $db->table('barang')->where('id', $id)->update($data + ['updated_at' => date('Y-m-d H:i:s')]);
            if (!$ok) {
                return $this->failMsg('Gagal mengupdate barang', 422);
            }

            $row = $db->table('barang')->select(
                'id, material, material_description, plant, material_group, storage_location, storage_location_desc,
                 df_stor_loc_level, base_unit_of_measure, qty_unrestricted, qty_transit_and_transfer, qty_blocked,
                 material_type, storage_id, import_batch, created_at, updated_at'
            )->where('id', $id)->get()->getRowArray();

            return $this->ok($row);
        } catch (\Throwable $e) {
            return $this->failMsg('Gagal mengupdate barang', 500, $e->getMessage());
        }
    }

    public function delete($id)
    {
        $db = \Config\Database::connect();
        $id = (int) $id;

        $row = $db->table('barang')->select('id')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return $this->failMsg('Barang tidak ditemukan', 404);
        }

        try {
            $db->table('barang')->where('id', $id)->delete();
            return $this->ok(['deleted' => true]);
        } catch (\Throwable $e) {
            return $this->failMsg('Gagal menghapus barang', 500, $e->getMessage());
        }
    }


    //create barang
    public function create()
    {
        $p = $this->request->getJSON(true);
        if (!$p) {
            $p = $this->request->getPost();
        }

        $data = [
            'material'                 => trim((string) ($p['material'] ?? '')),
            'material_description'     => trim((string) ($p['material_description'] ?? '')),
            'plant'                    => trim((string) ($p['plant'] ?? '')),
            'material_group'           => trim((string) ($p['material_group'] ?? '')),
            'storage_location'         => trim((string) ($p['storage_location'] ?? '')),
            'storage_location_desc'    => trim((string) ($p['storage_location_desc'] ?? '')),
            'df_stor_loc_level'        => null,
            'base_unit_of_measure'     => trim((string) ($p['base_unit_of_measure'] ?? '')),
            'qty_unrestricted'         => (string) ($p['qty_unrestricted'] ?? '0'),
            'qty_transit_and_transfer' => (string) ($p['qty_transit_and_transfer'] ?? '0'),
            'qty_blocked'              => (string) ($p['qty_blocked'] ?? '0'),
            'storage_id'               => (int) ($p['storage_id'] ?? 0) ?: null,
            'material_type'            => trim((string) ($p['material_type'] ?? '')),
            'import_batch'             => date('Ymd_His'),
        ];

        if ($data['material'] === '') {
            return $this->failMsg('Material wajib diisi', 400);
        }
        if ($data['plant'] !== '' && !in_array($data['plant'], ['1200', '1300'], true)) {
            return $this->failMsg('Plant hanya boleh 1200 atau 1300', 400);
        }
        if ($data['material_group'] === '') {
            return $this->failMsg('Material Group wajib diisi', 400);
        }

        try {
            $db = \Config\Database::connect();

            $sql = "
            INSERT INTO barang
            (material, material_description, plant, material_group, storage_location, storage_location_desc,
             df_stor_loc_level, base_unit_of_measure, qty_unrestricted, qty_transit_and_transfer, qty_blocked,
             storage_id, material_type, import_batch, created_at, updated_at)
            VALUES
            (:material:, :material_description:, :plant:, :material_group:, :storage_location:, :storage_location_desc:,
             :df_stor_loc_level:, :base_unit_of_measure:, :qty_unrestricted:, :qty_transit_and_transfer:, :qty_blocked:,
             :storage_id:, :material_type:, :import_batch:, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                material_description       = VALUES(material_description),
                plant                      = VALUES(plant),
                material_group             = VALUES(material_group),
                storage_location           = VALUES(storage_location),
                storage_location_desc      = VALUES(storage_location_desc),
                df_stor_loc_level          = VALUES(df_stor_loc_level),
                base_unit_of_measure       = VALUES(base_unit_of_measure),
                qty_unrestricted           = VALUES(qty_unrestricted),
                qty_transit_and_transfer   = VALUES(qty_transit_and_transfer),
                qty_blocked                = VALUES(qty_blocked),
                storage_id                 = VALUES(storage_id),
                material_type              = VALUES(material_type),
                import_batch               = VALUES(import_batch),
                updated_at                 = CURRENT_TIMESTAMP
        ";

            $db->query($sql, $data);

            $row = $db->table('barang')
                ->select('id, material, material_description, plant, material_group, storage_location, storage_location_desc, df_stor_loc_level, base_unit_of_measure, qty_unrestricted, qty_transit_and_transfer, qty_blocked, storage_id, material_type, import_batch, created_at, updated_at')
                ->where('material', $data['material'])
                ->get()->getRowArray();

            return $this->ok($row);
        } catch (\Throwable $e) {
            return $this->failMsg('Gagal menyimpan data barang', 400, $e->getMessage());
        }
    }


    public function import()
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->failMsg('File tidak valid', 400);
        }

        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return $this->failMsg('Format harus .xlsx/.xls/.csv', 400);
        }

        @is_dir(WRITEPATH . 'uploads') || @mkdir(WRITEPATH . 'uploads', 0775, true);
        $tmp = WRITEPATH . 'uploads/' . uniqid('excel_', true) . '.' . $file->getExtension();
        $file->move(dirname($tmp), basename($tmp));

        $excel = new BarangExcel();
        try {
            [$ins, $upd, $skip, $errs] = $excel->import($tmp);
            @unlink($tmp);
            return $this->ok(compact('ins', 'upd', 'skip', 'errs'));
        } catch (\Throwable $e) {
            @unlink($tmp);
            return $this->failMsg('Import gagal', 500, $e->getMessage());
        }
    }

    public function export()
    {
        $excel = new BarangExcel();
        [$headers, $rows] = $excel->exportRows();

        $ss = $excel->makeSpreadsheet($headers, $rows);
        @is_dir(WRITEPATH . 'exports') || @mkdir(WRITEPATH . 'exports', 0775, true);
        $file = WRITEPATH . 'exports/barang_' . date('Ymd_His') . '.xlsx';
        (new Xlsx($ss))->save($file);

        return $this->response->download($file, null)->setFileName(basename($file));
    }
}
