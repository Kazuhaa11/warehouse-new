<?php

namespace App\Controllers\Api;

use App\Libraries\Auth as AuthCtx;
use CodeIgniter\RESTful\ResourceController;

class BarangMovementApi extends ResourceController
{
    protected $format = 'json';

    protected function resolvePlant(): ?string
    {
        $user = AuthCtx::user();
        if (!$user) {
            return null;
        }

        $role = strtolower((string) ($user['role'] ?? ''));

        if ($role === 'super_admin') {
            $plant = trim((string) $this->request->getGet('plant'));
            return $plant !== '' ? $plant : null;
        }

        return AuthCtx::plant();
    }

    public function list()
    {
        $type = $this->request->getGet('type');
        $monthParam = $this->request->getGet('month');

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = max(1, (int) ($this->request->getGet('per_page') ?? 25));
        $offset = ($page - 1) * $perPage;

        $plant = $this->resolvePlant();
        if ($plant === null && strtolower(AuthCtx::role() ?? '') !== 'super_admin') {
            return $this->respond(['message' => 'Plant user tidak valid'], 403);
        }

        if ($monthParam) {
            $start = date('Y-m-01', strtotime($monthParam));
            $end   = date('Y-m-t', strtotime($start));
        } else {
            $start = date('Y-m-01');
            $end   = date('Y-m-t');
        }

        $db = \Config\Database::connect();

        $params = [$start, $end];
        $plantSql = '';

        if ($plant !== null) {
            $plantSql = 'WHERE b.plant = ?';
            $params[] = $plant;
        }

        $sql = "
            SELECT 
                b.id,
                b.material,
                b.material_description,
                b.plant,
                b.storage_location,
                b.storage_location_desc,
                b.qty_unrestricted,

                COALESCE(SUM(
                    CASE 
                        WHEN p.status IN ('approved','success')
                             AND p.approved_at BETWEEN ? AND ?
                        THEN pi.requested_qty
                        ELSE 0
                    END
                ), 0) AS total_keluar

            FROM barang b
            LEFT JOIN peminjaman_items pi ON pi.material = b.material
            LEFT JOIN peminjaman p ON p.id = pi.peminjaman_id

            $plantSql

            GROUP BY 
                b.id, b.material, b.material_description,
                b.plant, b.storage_location,
                b.storage_location_desc, b.qty_unrestricted

            ORDER BY b.material ASC
        ";

        $rows = $db->query($sql, $params)->getResultArray();

        $filtered = array_filter($rows, function ($r) use ($type) {
            $keluar = (float) ($r['total_keluar'] ?? 0);
            return match ($type) {
                'fast' => $keluar >= 3,
                'slow' => $keluar > 0 && $keluar < 3,
                'dead' => $keluar == 0,
                default => true
            };
        });

        $total = count($filtered);
        $paged = array_slice(array_values($filtered), $offset, $perPage);

        return $this->respond([
            'success' => true,
            'data' => $paged,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage)
            ]
        ]);
    }

    public function trend()
    {
        $db = \Config\Database::connect();

        $plant = $this->resolvePlant();
        if ($plant === null && strtolower(AuthCtx::role() ?? '') !== 'super_admin') {
            return $this->respond(['message' => 'Plant user tidak valid'], 403);
        }

        $labels = [];
        $dataFast = [];
        $dataSlow = [];
        $dataDead = [];

        for ($i = 11; $i >= 0; $i--) {
            $start = date('Y-m-01', strtotime("-$i months"));
            $end   = date('Y-m-t', strtotime($start));
            $labels[] = date('M Y', strtotime($start));

            $params = [$start, $end];
            $plantSql = '';

            if ($plant !== null) {
                $plantSql = 'WHERE b.plant = ?';
                $params[] = $plant;
            }

            $sql = "
                SELECT 
                    b.id,
                    COALESCE(SUM(
                        CASE 
                            WHEN p.status IN ('approved','success')
                                 AND p.approved_at BETWEEN ? AND ?
                            THEN pi.requested_qty
                            ELSE 0
                        END
                    ),0) AS total_keluar

                FROM barang b
                LEFT JOIN peminjaman_items pi ON pi.material = b.material
                LEFT JOIN peminjaman p ON p.id = pi.peminjaman_id

                $plantSql

                GROUP BY b.id
            ";

            $rows = $db->query($sql, $params)->getResultArray();

            $fast = $slow = $dead = 0;

            foreach ($rows as $r) {
                $k = (float) $r['total_keluar'];
                if ($k >= 3) $fast++;
                elseif ($k > 0) $slow++;
                else $dead++;
            }

            $dataFast[] = $fast;
            $dataSlow[] = $slow;
            $dataDead[] = $dead;
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'fast' => $dataFast,
                'slow' => $dataSlow,
                'dead' => $dataDead
            ]
        ]);
    }
}
