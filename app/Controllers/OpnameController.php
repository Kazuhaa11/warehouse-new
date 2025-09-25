<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class OpnameController extends BaseController
{
    public function index()
    {
        return view('opname/index', [
            'title' => 'Stock Opname',
            'menu' => 'opname',
            'apiSessions' => base_url('api/v1/stock-opname/sessions'), // dipakai JS
        ]);
    }

    // Halaman items suatu sesi
    public function items(int $id)
    {
        return view('opname/items', [
            'title' => 'Items Stock Opname',
            'menu' => 'opname',
            'sessionId' => $id,
            'api' => [
                'session' => base_url("api/v1/stock-opname/sessions/{$id}"),
                'items' => base_url("api/v1/stock-opname/sessions/{$id}/items"),
                'finalize' => base_url("api/v1/stock-opname/sessions/{$id}/finalize"),
                'recap' => base_url("api/v1/stock-opname/sessions/{$id}/recap"),
            ],
        ]);
    }
}
