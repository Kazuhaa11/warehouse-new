<?php
namespace App\Models;

use CodeIgniter\Model;

class BarangModel extends Model
{
    protected $table = 'barang';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'material',
        'material_description',
        'plant',
        'material_group',
        'storage_location',
        'storage_location_desc',
        'df_stor_loc_level',
        'base_unit_of_measure',
        'qty_unrestricted',
        'qty_transit_and_transfer',
        'qty_blocked',
        'material_type',
        'import_batch',
    ];
}
