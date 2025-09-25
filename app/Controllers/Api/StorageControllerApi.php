<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\StorageModel;
use CodeIgniter\HTTP\ResponseInterface;

class StorageControllerApi extends BaseApiController
{
    protected StorageModel $model;

    public function __construct()
    {
        $this->model = new StorageModel();
    }

    public function index()
    {
        try {
            $page = max(1, (int) ($this->request->getGet('page') ?? 1));
            $perPage = max(1, (int) ($this->request->getGet('per_page') ?? 50));

            $filters = [
                'q' => trim((string) $this->request->getGet('q')),
                'plant' => $this->request->getGet('plant'),
                'storage_location' => $this->request->getGet('storage_location'),
                'zone' => $this->request->getGet('zone'),
                'rack' => $this->request->getGet('rack'),
                'bin' => $this->request->getGet('bin'),
                'active' => $this->request->getGet('active'),
            ];

            [$rows, $total] = $this->model->listWithPath($filters, $page, $perPage);

            return $this->respond([
                'success' => true,
                'data' => $rows,
                'meta' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / max(1, $perPage)),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respond([
                'success' => false,
                'error' => ['message' => 'Gagal mengambil data storage', 'details' => $e->getMessage()]
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id = null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            return $this->respond(['success' => false, 'error' => ['message' => 'Storage tidak ditemukan']], 404);
        }
        $parts = array_filter([$row['zone'] ?? '', $row['rack'] ?? '', $row['bin'] ?? ''], fn($v) => $v !== '' && $v !== null);
        $row['path'] = implode(' / ', $parts);

        return $this->respond(['success' => true, 'data' => $row]);
    }

    public function store()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();

        $rules = [
            'plant' => 'required',
            'storage_location' => 'required',
            'storage_location_desc' => 'permit_empty|max_length[100]',
            'zone' => 'permit_empty|max_length[50]',
            'rack' => 'permit_empty|max_length[50]',
            'bin' => 'permit_empty|max_length[50]',
            'name' => 'permit_empty|max_length[100]',
            'capacity' => 'permit_empty|integer',   // kolom baru
            'note' => 'permit_empty',
            'is_active' => 'permit_empty|in_list[0,1]',
            'created_by' => 'permit_empty|integer',
        ];
        if (!$this->validate($rules)) {
            return $this->respond([
                'success' => false,
                'error' => [
                    'message' => 'Validasi gagal',
                    'details' => $this->validator->getErrors()
                ]
            ], 422);
        }

        if (empty($data['zone']) && empty($data['rack']) && empty($data['bin'])) {
            return $this->respond([
                'success' => false,
                'error' => [
                    'message' => 'Minimal isi salah satu dari Zone / Rack / Bin'
                ]
            ], 422);
        }

        $data['is_active'] = isset($data['is_active']) ? (int) $data['is_active'] : 1;
        $data['capacity'] = isset($data['capacity']) ? (int) $data['capacity'] : null;
        $data['created_by'] = $data['created_by'] ?? null; // ganti user_id() sesuai autentikasi kamu

        try {
            $this->model->insert($data, true);
            $new = $this->model->find($this->model->getInsertID());
            return $this->respondCreated(['success' => true, 'data' => $new]);
        } catch (\Throwable $e) {
            return $this->respond([
                'success' => false,
                'error' => [
                    'message' => 'Gagal menyimpan storage',
                    'details' => $e->getMessage()
                ]
            ], 400);
        }
    }

    public function update($id = null)
    {
        $row = $this->model->find($id);
        if (!$row) {
            return $this->respond([
                'success' => false,
                'error' => ['message' => 'Storage tidak ditemukan'],
            ], 404);
        }

        $payload = $this->request->getJSON(true);
        if ($payload === null) {
            $payload = $this->request->getRawInput() ?? [];
        }

        foreach (['zone', 'rack', 'bin', 'name', 'note', 'storage_location_desc'] as $k) {
            if (array_key_exists($k, $payload) && $payload[$k] === '') {
                $payload[$k] = null;
            }
        }
        if (array_key_exists('is_active', $payload)) {
            $payload['is_active'] = (int) $payload['is_active'];
        }
        if (array_key_exists('capacity', $payload)) {
            $payload['capacity'] = ($payload['capacity'] === '' ? null : (int) $payload['capacity']);
        }

        $allowed = [
            'plant',
            'storage_location',
            'storage_location_desc',
            'zone',
            'rack',
            'bin',
            'name',
            'capacity',
            'is_active',
            'note',
            'created_by',
        ];
        $data = array_intersect_key($payload, array_flip($allowed));

        if ($data === []) {
            return $this->respond([
                'success' => false,
                'error' => ['message' => 'Tidak ada perubahan data untuk disimpan'],
            ], 422);
        }

        $zone = $data['zone'] ?? $row['zone'];
        $rack = $data['rack'] ?? $row['rack'];
        $bin = $data['bin'] ?? $row['bin'];
        if (($zone === null || $zone === '') && ($rack === null || $rack === '') && ($bin === null || $bin === '')) {
            return $this->respond([
                'success' => false,
                'error' => ['message' => 'Minimal isi salah satu dari Zone / Rack / Bin'],
            ], 422);
        }

        try {
            $this->model->update($id, $data);
            return $this->respond([
                'success' => true,
                'data' => $this->model->find($id),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Storage update error: {msg}', ['msg' => $e->getMessage()]);

            return $this->respond([
                'success' => false,
                'error' => [
                    'message' => 'Gagal update storage',
                    'details' => (ENVIRONMENT === 'development') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }


    public function delete($id = null)
    {
        $id = (int) $id;
        $row = $this->model->find($id);
        if (!$row) {
            return $this->respond([
                'success' => false,
                'error' => ['message' => 'Storage tidak ditemukan']
            ], 404);
        }

        $db = \Config\Database::connect();

        try {
            $db->transStart();
            $db->table('barang')
                ->where('storage_id', $id)
                ->set('storage_id', null)
                ->update();
            $affectedBarang = $db->affectedRows();

            $db->table('peminjaman_items')->where('storage_id', $id)->set('storage_id', null)->update();
            $affectedPeminjamanItems = $db->affectedRows();

            $ok = $this->model->delete($id, true);
            if ($ok === false) {
                $db->transRollback();
                return $this->respond([
                    'success' => false,
                    'error' => [
                        'message' => 'Gagal menghapus',
                        'details' => $this->model->errors() ?: null
                    ]
                ], 400);
            }

            $db->transComplete();
            if (!$db->transStatus()) {
                return $this->respond([
                    'success' => false,
                    'error' => ['message' => 'Transaksi gagal saat menghapus storage']
                ], 400);
            }

            return $this->respond([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'deleted' => true,
                    'nullified_barang' => $affectedBarang,
                    'nullified_peminjaman_items' => $affectedPeminjamanItems ?? 0,
                ]
            ]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $code = 400;
            if (stripos($msg, '1451') !== false || stripos($msg, 'foreign key') !== false) {
                $code = 409;
                $msg = 'Tidak dapat menghapus karena data masih digunakan';
            }
            return $this->respond([
                'success' => false,
                'error' => [
                    'message' => 'Gagal menghapus',
                    'details' => $msg
                ]
            ], $code);
        }
    }

    

    public function presetStorLocDesc()
    {
        $db = \Config\Database::connect();
        $plant = $this->request->getGet('plant');
        $sl = $this->request->getGet('storage_location');

        if (!$plant || !$sl) {
            return $this->respond(['success' => true, 'data' => []]);
        }

        $rows = $db->table('barang')
            ->select('storage_location_desc')
            ->where('plant', $plant)
            ->where('storage_location', $sl)
            ->where('storage_location_desc IS NOT NULL')
            ->groupBy('storage_location_desc')
            ->orderBy('storage_location_desc')
            ->get()->getResultArray();

        $opts = array_values(array_unique(array_map(
            fn($r) => (string) $r['storage_location_desc'],
            $rows
        )));

        return $this->respond(['success' => true, 'data' => $opts]);
    }
}
