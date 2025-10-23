<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class MovementController extends BaseController
{
    public function index()
    {
        $data = [
            'menu' => 'movement',
            'title' => 'Fast / Slow / Dead Item'
        ];
        return view('barang/barang_movement', $data);
    }
}
