<?php

namespace App\Models;

use CodeIgniter\Model;

class ReservasiListModel extends Model
{
    protected $table = 'reservasi_list';
    protected $primaryKey = 'id';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'material',
        'material_description',
        'reservation',
        'plant',
        'storage_location',
        'qty_in_un_of_entry',
        'base_unit_of_measure',
        'posting_date',
        'movement_type',
        'batch',
        'purchase_order',
        'order',
        'username',
        'material_document',
        'text',
        'created_at'
    ];
}
