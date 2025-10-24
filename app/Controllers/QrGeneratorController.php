<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QrGeneratorController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        $q = trim((string) $this->request->getGet('q'));
        $page = (int) ($this->request->getGet('page') ?? 1);
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $builder = $db->table('barang')->select('id, material, material_description');

        if ($q !== '') {
            $builder->groupStart()
                ->like('material', $q)
                ->orLike('material_description', $q)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);

        $barang = $builder
            ->orderBy('material', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $totalPages = ceil($total / $perPage);

        $data = [
            'menu' => 'generateqr',
            'title' => 'Generate QR Code Barang',
            'barang' => $barang,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'q' => $q,
            'error' => null, 
        ];

        return view('qrgenerator/generate_qr', $data);
    }

    public function generate()
    {
        $ids = (array) $this->request->getPost('barang_ids');
        if (!$ids) {
            $db = \Config\Database::connect();
            $barang = $db->table('barang')->select('id, material, material_description')
                ->orderBy('material', 'ASC')->limit(25)->get()->getResultArray();

            return view('qrgenerator/generate_qr', [
                'menu' => 'generateqr',
                'title' => 'Generate QR Code Barang',
                'barang' => $barang,
                'page' => 1,
                'perPage' => 25,
                'total' => count($barang),
                'totalPages' => 1,
                'q' => '',
                'error' => 'Pilih minimal satu barang.', 
            ]);
        }

        $db = \Config\Database::connect();
        $items = $db->table('barang')
            ->whereIn('id', $ids)
            ->select('material, material_description')
            ->orderBy('material', 'ASC')
            ->get()
            ->getResultArray();

        $labels = [];
        foreach ($items as $b) {
            $payload = "Material: {$b['material']}\nDesc: {$b['material_description']}";
            $qr = Builder::create()
                ->writer(new PngWriter())
                ->data($payload)
                ->size(460)
                ->margin(10)
                ->build();

            $dataUri = 'data:image/png;base64,' . base64_encode($qr->getString());

            $labels[] = [
                'material' => $b['material'],
                'desc' => $b['material_description'],
                'dataUri' => $dataUri,
            ];
        }

        $data = [
            'menu' => 'generateqr',
            'title' => 'Hasil Generate QR Barang',
            'labels' => $labels,
        ];

        return view('qrgenerator/qr_result', $data);
    }
}
