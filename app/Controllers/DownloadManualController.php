<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class DownloadManualController extends Controller
{
    public function manualBook()
    {
        $file = FCPATH . 'manual_book/Manual_Book_WHS_Polytron.pdf';

        if (!is_file($file)) {
            return redirect()->back()
                ->with('error', 'File manual book tidak ditemukan');
        }

        return $this->response->download($file, null);
    }
}
