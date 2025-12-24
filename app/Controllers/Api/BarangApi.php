<?php

namespace App\Controllers\Api;

use App\Libraries\BarangExcel;
use App\Models\BarangModel;
use App\Libraries\Auth as AuthCtx;
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
        $db  = \Config\Database::connect();
        $req = $this->request;

        $isSuperAdmin = AuthCtx::isSuperAdmin();
        $plant = AuthCtx::plant();
        $plant = AuthCtx::plant();
        if (!$plant && !$isSuperAdmin) {
            return $this->failMsg('Plant user tidak valid', 403);
        }

        $q   = trim((string) $req->getGet('q'));
        $sl  = $req->getGet('storage_location');
        $slp = $req->getGet('storage_loc_prefix');
        $mg  = $req->getGet('material_group');
        $mt  = $req->getGet('material_type');

        $page = max(1, (int) ($req->getGet('page') ?? 1));
        $per  = max(1, min(100, (int) ($req->getGet('per_page') ?? 20)));
        $offset = ($page - 1) * $per;

        $b = $db->table('barang');
        if (!$isSuperAdmin) {
            $b->where('barang.plant', $plant);
        }

        if ($q !== '') {
            $b->groupStart()
                ->like('barang.material', $q)
                ->orLike('barang.material_description', $q)
                ->groupEnd();
        }
        if ($sl) {
            $b->where('barang.storage_location', $sl);
        }
        if ($slp !== null && $slp !== '') {
            $b->like('barang.storage_location', $slp, 'after');
        }
        if ($mg) {
            $b->where('barang.material_group', $mg);
        }
        if ($mt) {
            $b->where('barang.material_type', $mt);
        }

        $rows = $b->select("
            barang.id,
            barang.material,
            barang.material_description,
            barang.plant,
            barang.storage_location,
            barang.storage_location_desc,
            barang.material_group,
            barang.base_unit_of_measure,
            barang.qty_unrestricted,
            barang.qty_transit_and_transfer,
            barang.qty_blocked,
            barang.material_type,
            barang.storage_id,
            barang.import_batch,
            barang.created_at,
            barang.updated_at,
            barang.harga,
            barang.total_harga,
            s.zone AS stor_zone,
            s.rack AS stor_rack,
            s.bin  AS stor_bin,
            s.dak  AS stor_dak
        ")
            ->join('storages s', 's.id = barang.storage_id', 'left')
            ->orderBy('barang.material', 'ASC')
            ->limit($per, $offset)
            ->get()
            ->getResultArray();

        foreach ($rows as &$r) {
            $dip = $db->table('peminjaman_items pi')
                ->select('SUM(pi.requested_qty) AS qty_dipinjam')
                ->join('peminjaman p', 'p.id = pi.peminjaman_id')
                ->where('pi.barang_id', $r['id'])
                ->whereIn('p.status', ['draft', 'approved', 'returned', 'success'])
                ->get()
                ->getRowArray();

            $r['qty_dipinjam'] = (float) ($dip['qty_dipinjam'] ?? 0);
        }
        unset($r);

        $count = $db->table('barang');
        if (!$isSuperAdmin) {
            $count->where('plant', $plant);
        }

        if ($q !== '') {
            $count->groupStart()
                ->like('material', $q)
                ->orLike('material_description', $q)
                ->groupEnd();
        }
        if ($sl)  $count->where('storage_location', $sl);
        if ($slp !== null && $slp !== '') $count->like('storage_location', $slp, 'after');
        if ($mg)  $count->where('material_group', $mg);
        if ($mt)  $count->where('material_type', $mt);

        $total = (int) $count->countAllResults();

        return $this->ok($rows, [
            'page'        => $page,
            'per_page'   => $per,
            'total'      => $total,
            'total_pages' => (int) ceil($total / $per),
        ]);
    }

    public function show($id)
    {
        $db    = \Config\Database::connect();
        $isSuperAdmin = AuthCtx::isSuperAdmin();
        $plant = AuthCtx::plant();

        $q = $db->table('barang')
            ->select('
            id, material, material_description, plant, material_group,
            storage_location, storage_location_desc, df_stor_loc_level,
            base_unit_of_measure, qty_unrestricted, qty_transit_and_transfer,
            qty_blocked, material_type, storage_id, import_batch,
            created_at, updated_at, harga, total_harga
        ')
            ->where('id', (int) $id);

        if (!$isSuperAdmin) {
            $q->where('plant', $plant);
        }

        $row = $q->get()->getRowArray();

        if (!$row) {
            return $this->failMsg('Barang tidak ditemukan', 404);
        }

        $fotos = $db->table('barang_foto')
            ->select('id, path, is_primary')
            ->where('barang_id', (int) $id)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $row['fotos'] = $fotos;

        $dip = $db->table('peminjaman_items pi')
            ->select('SUM(pi.requested_qty) AS qty_dipinjam')
            ->join('peminjaman p', 'p.id = pi.peminjaman_id')
            ->where('pi.barang_id', $id)
            ->whereIn('p.status', ['draft', 'approved', 'returned', 'success'])
            ->get()
            ->getRowArray();

        $row['qty_dipinjam'] = (float) ($dip['qty_dipinjam'] ?? 0);

        return $this->ok($row);
    }

    public function update($id)
    {
        $db    = \Config\Database::connect();
        $isSuperAdmin = AuthCtx::isSuperAdmin();
        $plant = AuthCtx::plant();
        $id    = (int) $id;

        $q = $db->table('barang')
            ->select('id, plant, qty_unrestricted')
            ->where('id', $id);

        if (!$isSuperAdmin) {
            if (!$plant) {
                return $this->failMsg('Plant user tidak valid', 403);
            }
            $q->where('plant', $plant);
        }

        $barang = $q->get()->getRowArray();

        if (!$barang) {
            return $this->failMsg('Barang tidak ditemukan', 404);
        }

        $p = $this->request->getJSON(true) ?? $this->request->getRawInput();

        $harga = null;
        if (array_key_exists('harga', $p)) {
            $clean = preg_replace('/[^\d]/', '', (string) $p['harga']);
            $harga = $clean !== '' ? (float) $clean : null;
        }

        $qty = array_key_exists('qty_unrestricted', $p)
            ? (float) $p['qty_unrestricted']
            : (float) $barang['qty_unrestricted'];

        $totalHarga = $harga !== null ? $harga * $qty : null;

        $allowed = [
            'base_unit_of_measure',
            'material_type',
            'material_group',
            'storage_id',
            'harga',
            'qty_unrestricted',
        ];

        $data = [];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $p)) continue;

            if ($field === 'harga') {
                $data['harga'] = $harga;
                $data['total_harga'] = $totalHarga;
            } else {
                $data[$field] = $p[$field] === '' ? null : $p[$field];
            }
        }

        if (empty($data)) {
            return $this->failMsg('Tidak ada perubahan.', 400);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        $db->table('barang')->where('id', $id)->update($data);

        return $this->ok(
            $db->table('barang')
                ->select('id, material, harga, total_harga, qty_unrestricted, updated_at')
                ->where('id', $id)
                ->get()
                ->getRowArray()
        );
    }

    public function delete($id)
    {
        $db    = \Config\Database::connect();
        $isSuperAdmin = AuthCtx::isSuperAdmin();
        $plant = AuthCtx::plant();
        $id    = (int) $id;

        $q = $db->table('barang')
            ->where('id', $id);

        if (!$isSuperAdmin) {
            if (!$plant) {
                return $this->failMsg('Plant user tidak valid', 403);
            }
            $q->where('plant', $plant);
        }

        $row = $q->get()->getRowArray();

        if (!$row) {
            return $this->failMsg('Barang tidak ditemukan', 404);
        }

        $db->table('barang')->where('id', $id)->delete();
        return $this->ok(['deleted' => true]);
    }

    public function create()
    {
        $p = $this->request->getJSON(true);
        if (!$p) {
            $p = $this->request->getPost();
        }

        $user = AuthCtx::user();
        if (!$user) {
            return $this->failMsg('Unauthenticated', 401);
        }

        $role = strtolower((string) ($user['role'] ?? ''));
        $plant = null;

        if ($role === 'super_admin') {
            $plant = trim((string) ($p['plant'] ?? ''));
            if ($plant === '') {
                return $this->failMsg('Plant wajib dipilih oleh Super Admin', 400);
            }
        } else {
            $plant = AuthCtx::plant();
            if (!$plant) {
                return $this->failMsg('Plant user tidak valid', 403);
            }
        }

        if (!in_array($plant, ['1200', '1300'], true)) {
            return $this->failMsg('Plant hanya boleh 1200 atau 1300', 400);
        }

        $rawHarga = $p['harga'] ?? null;
        $harga = null;

        if ($rawHarga !== null && $rawHarga !== '') {
            $harga = preg_replace('/[^\d,]/', '', $rawHarga);
            $harga = str_replace(',', '.', $harga);
            $harga = is_numeric($harga) ? (float) $harga : null;
        }

        $qty = (float) ($p['qty_unrestricted'] ?? 0);
        $totalHarga = ($harga !== null && $qty > 0) ? $harga * $qty : null;

        $data = [
            'material' => trim((string) ($p['material'] ?? '')),
            'material_description' => trim((string) ($p['material_description'] ?? '')),
            'plant' => $plant,
            'material_group' => trim((string) ($p['material_group'] ?? '')),
            'storage_location' => trim((string) ($p['storage_location'] ?? '')),
            'storage_location_desc' => trim((string) ($p['storage_location_desc'] ?? '')),
            'df_stor_loc_level' => null,
            'base_unit_of_measure' => trim((string) ($p['base_unit_of_measure'] ?? '')),
            'qty_unrestricted' => (string) ($p['qty_unrestricted'] ?? '0'),
            'qty_transit_and_transfer' => (string) ($p['qty_transit_and_transfer'] ?? '0'),
            'qty_blocked' => (string) ($p['qty_blocked'] ?? '0'),
            'storage_id' => (int) ($p['storage_id'] ?? 0) ?: null,
            'material_type' => trim((string) ($p['material_type'] ?? '')),
            'import_batch' => date('Ymd_His'),
            'harga' => $harga,
            'total_harga' => $totalHarga,
        ];

        if ($data['material'] === '') {
            return $this->failMsg('Material wajib diisi', 400);
        }

        if ($data['material_group'] === '') {
            return $this->failMsg('Material Group wajib diisi', 400);
        }

        try {
            $db = \Config\Database::connect();

            $sql = "
            INSERT INTO barang (
                material, material_description, plant, material_group,
                storage_location, storage_location_desc,
                harga, total_harga,
                df_stor_loc_level, base_unit_of_measure,
                qty_unrestricted, qty_transit_and_transfer, qty_blocked,
                storage_id, material_type, import_batch,
                created_at, updated_at
            ) VALUES (
                :material:, :material_description:, :plant:, :material_group:,
                :storage_location:, :storage_location_desc:,
                :harga:, :total_harga:,
                :df_stor_loc_level:, :base_unit_of_measure:,
                :qty_unrestricted:, :qty_transit_and_transfer:, :qty_blocked:,
                :storage_id:, :material_type:, :import_batch:,
                CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ";

            $db->query($sql, $data);

            $row = $db->table('barang')
                ->select('id, material, material_description, plant, material_group,
                      storage_location, storage_location_desc, df_stor_loc_level,
                      base_unit_of_measure, qty_unrestricted,
                      qty_transit_and_transfer, qty_blocked,
                      storage_id, material_type, import_batch,
                      created_at, updated_at')
                ->where('material', $data['material'])
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

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
