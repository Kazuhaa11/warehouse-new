<?php

namespace App\Models;

use CodeIgniter\Model;

class BarangFotoModel extends Model
{
    protected $table = 'barang_foto';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'barang_id',
        'path',
        'caption',
        'is_primary',
        'created_at'
    ];
    protected $useTimestamps = false;
}
