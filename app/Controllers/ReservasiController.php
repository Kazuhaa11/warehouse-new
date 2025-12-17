<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ReservasiController extends BaseController
{
    public function index()
    {
        return view('reservasi/list', ['title' => 'Reservasi', 'menu' => 'reservasi']);
    }
}
