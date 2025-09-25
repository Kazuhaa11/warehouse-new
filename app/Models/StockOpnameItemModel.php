<?php

namespace App\Models;

use CodeIgniter\Model;

class StockOpnameItemModel extends Model
{
    protected $table = 'stock_opname_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'session_id',
        'barang_id',
        'storage_id',
        'material',
        'material_description',
        'plant',
        'material_group',
        'storage_location',
        'storage_location_desc',
        'df_stor_loc_level',
        'base_unit_of_measure',
        'material_type',
        'qty_unrestricted',
        'qty_transit_and_transfer',
        'qty_blocked',
        'counted_qty',
        'note',
    ];

    public function bySession(int $sessionId): array
    {
        return $this->where('session_id', $sessionId)
            ->orderBy('diff_qty', 'DESC') // generated kolom
            ->findAll();
    }
}
