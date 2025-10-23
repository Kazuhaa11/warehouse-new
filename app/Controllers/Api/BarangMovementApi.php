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
        $params = [];
        $whereDate = '';

        if ($monthParam) {
            $monthDate = date('Y-m', strtotime($monthParam));
            $start = $monthDate . '-01';
            $end = date('Y-m-t', strtotime($start));
            $whereDate = 'AND pi.created_at BETWEEN ? AND ?';
            $params = [$start, $end];
        } else {
            $whereDate = 'AND pi.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)';
        }

        $db = \Config\Database::connect();
        $sql = "
        SELECT 
            b.id,
            b.material,
            b.material_description,
            b.plant,
            b.storage_location,
            b.storage_location_desc,
            b.qty_unrestricted,
            COALESCE(SUM(pi.requested_qty), 0) AS total_keluar,
            (COALESCE(SUM(pi.requested_qty), 0) / NULLIF(AVG(NULLIF(b.qty_unrestricted, 0)), 0)) AS turnover
        FROM barang b
        LEFT JOIN peminjaman_items pi 
            ON pi.material = b.material $whereDate
        GROUP BY b.id
    ";

        $rows = $db->query($sql, $params)->getResultArray();

        $filtered = array_filter($rows, function ($r) use ($type) {
            $t = (float) ($r['turnover'] ?? 0);
            if ($type === 'fast')
                return $t > 1;
            if ($type === 'slow')
                return $t > 0.1 && $t <= 1;
            if ($type === 'dead')
                return $t <= 0.1;
            return true;
        });

        return $this->respond([
            'success' => true,
            'data' => array_values($filtered)
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
