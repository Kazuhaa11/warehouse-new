<?php
namespace App\Models;

use CodeIgniter\Model;

class StorageModel extends Model
{
    protected $table = 'storages';
    protected $primaryKey = 'id';

    protected $allowedFields = [
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

    protected $useTimestamps = true;         
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * @return array
     */
    public function listWithPath(array $filters, int $page, int $perPage): array
    {
        $b = $this->builder();
        $b->select('storages.*');
        $b->select('CONCAT_WS(" / ", NULLIF(zone,""), NULLIF(rack,""), NULLIF(bin,"")) AS path', false);

        if ($filters['active'] === null || $filters['active'] === '') {
            $b->where('storages.is_active', 1);
        } else {
            $b->where('storages.is_active', (int) $filters['active']);
        }

        if (!empty($filters['plant']))
            $b->where('storages.plant', $filters['plant']);
        if (!empty($filters['storage_location']))
            $b->where('storages.storage_location', $filters['storage_location']);
        if (!empty($filters['zone']))
            $b->where('storages.zone', $filters['zone']);
        if (!empty($filters['rack']))
            $b->where('storages.rack', $filters['rack']);
        if (!empty($filters['bin']))
            $b->where('storages.bin', $filters['bin']);

        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $b->groupStart()
                ->like('storages.zone', $q)
                ->orLike('storages.rack', $q)
                ->orLike('storages.bin', $q)
                ->orLike('storages.name', $q)
                ->orLike('storages.note', $q)
                ->orLike('storages.storage_location_desc', $q)
                ->groupEnd();
        }

        $bCount = clone $b;
        $total = (int) $bCount->select('COUNT(*) AS cnt', false)->get()->getRow('cnt');

        $b->orderBy('plant ASC, storage_location ASC, zone ASC, rack ASC, bin ASC', '', false);
        $rows = $b->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return [$rows, $total];
    }
}
