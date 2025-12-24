<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Auth;
use CodeIgniter\HTTP\ResponseInterface;

class BarangController extends BaseController
{
    public function index()
    {
        return view('barang/index', ['title' => 'Barang', 'menu' => 'barang', 'isSuperAdmin' => Auth::isSuperAdmin(), 'userPlant' => Auth::plant()]);
    }
}
