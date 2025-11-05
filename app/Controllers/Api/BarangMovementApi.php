<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class BarangMovementApi extends ResourceController
{
    protected $format = 'json';

    public function list()
    {
        $type = $this->request->getGet('type');
        $monthParam = $this->request->getGet('month');
        $page = (int) ($this->request->getGet('page') ?? 1);
        $perPage = (int) ($this->request->getGet('per_page') ?? 25);
        $offset = ($page - 1) * $perPage;

        $db = \Config\Database::connect();

        if ($monthParam) {
            $monthDate = date('Y-m', strtotime($monthParam));
            $start = $monthDate . '-01';
            $end = date('Y-m-t', strtotime($start));
        } else {
            $start = date('Y-m-01');
            $end = date('Y-m-t');
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
                COALESCE(SUM(pi.requested_qty), 0) AS total_keluar
            FROM barang b
            LEFT JOIN peminjaman_items pi 
                ON pi.material = b.material 
                AND pi.created_at BETWEEN ? AND ?
            GROUP BY b.id, b.material, b.material_description, b.plant, b.storage_location, b.storage_location_desc, b.qty_unrestricted
            ORDER BY b.material ASC
        ";

        $rowsAll = $db->query($sql, [$start, $end])->getResultArray();

        $filtered = array_filter($rowsAll, function ($r) use ($type) {
            $keluar = (float) ($r['total_keluar'] ?? 0);
            if ($type === 'fast') return $keluar >= 3;
            if ($type === 'slow') return $keluar > 0 && $keluar < 3;
            if ($type === 'dead') return $keluar == 0;
            return true; 
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
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
    }

    public function trend()
    {
        $db = \Config\Database::connect();

        $labels = [];
        $dataFast = $dataSlow = $dataDead = [];

        for ($i = 11; $i >= 0; $i--) {
            $start = date('Y-m-01', strtotime("-$i months"));
            $end = date('Y-m-01', strtotime("-$i months +1 month"));
            $label = date('M Y', strtotime($start));
            $labels[] = $label;

            $sql = "
                SELECT 
                    (COALESCE(SUM(pi.requested_qty), 0) / NULLIF(AVG(NULLIF(b.qty_unrestricted, 0)), 0)) AS turnover
                FROM barang b
                LEFT JOIN peminjaman_items pi 
                    ON pi.material = b.material
                    AND pi.created_at >= ? AND pi.created_at < ?
                GROUP BY b.id
            ";

            $rows = $db->query($sql, [$start, $end])->getResultArray();

            $fast = $slow = $dead = 0;
            foreach ($rows as $r) {
                $rate = (float) ($r['turnover'] ?? 0);
                if ($rate > 1)
                    $fast++;
                elseif ($rate > 0.1)
                    $slow++;
                else
                    $dead++;
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
                'dead' => $dataDead,
            ]
        ]);
    }
}
