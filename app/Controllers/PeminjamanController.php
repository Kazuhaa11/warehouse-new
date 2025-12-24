<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Auth;
use CodeIgniter\HTTP\ResponseInterface;

class PeminjamanController extends BaseController
{
    public function index()
    {
        return view('peminjaman/index', ['title' => 'Peminjaman', 'menu' => 'peminjaman',  'isSuperAdmin' => Auth::isSuperAdmin(), 'userPlant' => Auth::plant()]);
    }
}
