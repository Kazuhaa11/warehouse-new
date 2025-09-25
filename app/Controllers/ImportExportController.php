<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ImportExportController extends BaseController
{

    public function index()
    {
        return view('import/index');
    }
}


