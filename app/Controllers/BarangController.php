<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class BarangController extends BaseController
{
    public function index()
    {
        return view('barang/index', ['title' => 'Barang', 'menu' => 'barang']);
    }
}
