<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Database;

class StockPredictController extends Controller
{
    public function index()
    {
        $db = Database::connect();

        $page = (int) ($this->request->getGet('page') ?? 1);
        $perPage = (int) ($this->request->getGet('per_page') ?? 25);
        $offset = ($page - 1) * $perPage;
        $q = trim((string) $this->request->getGet('q') ?? '');

        $builder = $db->table('v_barang_demand_summary');
        if ($q !== '') {
            $builder->groupStart()
                ->like('material', $q)
                ->orLike('material_description', $q)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $rows = $builder
            ->orderBy('material', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $defaultLeadTime = 7;
        $results = [];

        foreach ($rows as $data) {
            $ADD = (float) ($data['avg_daily_demand'] ?? 0); 
            $σd  = (float) ($data['stddev_daily_demand'] ?? 0);
            $LT  = $defaultLeadTime;
            $σLT = 0;

            if ($ADD >= 50) $class = 'Fast';
            elseif ($ADD >= 10) $class = 'Slow';
            else $class = 'Dead';

            $Z = match ($class) {
                'Fast' => 1.65,
                'Slow' => 1.28,
                default => 0.84,
            };

            $DDLT   = $ADD * $LT;
            $σDDLT  = sqrt(($LT * pow($σd, 2)) + (pow($ADD, 2) * pow($σLT, 2)));
            $SafetyStock = $Z * $σDDLT;
            $ROP = $DDLT + $SafetyStock;

            $SafetyStock = is_nan($SafetyStock) || $SafetyStock < 0 ? 0 : $SafetyStock;
            $ROP = is_nan($ROP) || $ROP < 0 ? 0 : $ROP;

            if ($SafetyStock <= 0) $SafetyStock = 5;
            if ($ROP <= 0) $ROP = 10;

            if ($class === 'Fast') {
                $MOQ = ceil(1.5 * $ROP);
            } elseif ($class === 'Slow') {
                $MOQ = ceil(1.0 * $ROP);
            } else {
                $MOQ = ceil(0.5 * $ROP);
            }

            $daysCount = max(1, (float) ($data['days_count'] ?? 0));
            $totalOut = $ADD * $daysCount;
            $EOQ = ($totalOut > 0 && $ADD > 0) ? sqrt($totalOut * $ADD) : 0;

            $packSize = 10;
            $ROP_rounded = ceil($ROP / $packSize) * $packSize;
            $MOQ = max(10, ceil($MOQ / $packSize) * $packSize);

            $current = (float) ($data['current_stock'] ?? 0);

            if ($ADD <= 0) {
                $status = "Belum Ada Data Pemakaian";
                $daysUntilOrder = 0;
                $nextOrderDate = date('Y-m-d');
            } else {
                if ($current <= $SafetyStock) {
                    $status = "Pesan Sekarang";
                    $daysUntilOrder = 0;
                    $nextOrderDate = date('Y-m-d');
                } else {
                    $daysUntilOrder = (int) ceil(($current - $SafetyStock) / max($ADD, 1));
                    $status = "Stok Aman ({$daysUntilOrder} hari lagi)";
                    $nextOrderDate = date('Y-m-d', strtotime("+{$daysUntilOrder} days"));
                }
            }

            $orderQtySuggested = max($MOQ, $EOQ);

            $roundHalf = fn($v) => (int) floor($v + 0.5);

            $SafetyStock = $roundHalf($SafetyStock);
            $ROP = $roundHalf($ROP);
            $ROP_rounded = $roundHalf($ROP_rounded);
            $EOQ = $roundHalf($EOQ);
            $MOQ = $roundHalf($MOQ);
            $orderQtySuggested = $roundHalf($orderQtySuggested);
            $current = $roundHalf($current);
            $ADD = $roundHalf($ADD);
            $σd = $roundHalf($σd);
            $daysUntilOrder = $roundHalf($daysUntilOrder);

            $results[] = [
                'barang_id' => $data['barang_id'],
                'material' => $data['material'],
                'description' => $data['material_description'],
                'class' => $class,
                'avg_daily_demand' => $ADD,
                'stddev_daily_demand' => $σd,
                'safety_stock' => $SafetyStock,
                'rop' => $ROP,
                'rop_rounded' => $ROP_rounded,
                'eoq' => $EOQ,
                'moq_dynamic' => $MOQ,
                'order_qty_suggested' => $orderQtySuggested,
                'current_stock' => $current,
                'days_until_order' => $daysUntilOrder,
                'next_order_date' => $nextOrderDate,
                'order_status' => $status,
            ];
        }

        $meta = [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage)
        ];

        return $this->response->setJSON([
            'data' => $results,
            'meta' => $meta
        ]);
    }

    public function view()
    {
        return view('stock_management/stock_predict');
    }
}
