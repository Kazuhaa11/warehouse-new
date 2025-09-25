<?php

namespace App\Models;

use CodeIgniter\Model;

class StockOpnameSessionModel extends Model
{
    protected $table = 'stock_opname_sessions';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['code', 'scheduled_at', 'finalized_at', 'created_by', 'note'];
    protected $returnType = 'array';

    public function nextCode(): string
    {
        $date = date('Ymd');
        $prefix = "SO-$date-";
        $last = $this->select('code')
            ->like('code', $prefix, 'after')
            ->orderBy('id', 'DESC')
            ->first();

        $n = 1;
        if ($last && preg_match('/-(\d{3})$/', $last['code'] ?? '', $m)) {
            $n = intval($m[1]) + 1;
        }
        return $prefix . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }
}
