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
            'menu'       => 'generateqr',
            'title'      => 'Generate QR Code Barang',
            'barang'     => $barang,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
            'q'          => $q,
            'error'      => null,
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
                'menu'       => 'generateqr',
                'title'      => 'Generate QR Code Barang',
                'barang'     => $barang,
                'page'       => 1,
                'perPage'    => 25,
                'total'      => count($barang),
                'totalPages' => 1,
                'q'          => '',
                'error'      => 'Pilih minimal satu barang.',
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
        $zplAll = "";

        foreach ($items as $b) {

            $payload = json_encode([
                'material' => $b['material'],
                'desc'     => $b['material_description']
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $zpl = "^XA\n";
            $zpl .= "^CF0,30\n";
            $zpl .= "^FO40,20^FD" . $b['material'] . "^FS\n";
            $zpl .= "^FO40,70\n";
            $zpl .= "^BQN,2,3\n";
            $zpl .= "^FDLA," . $payload . "^FS\n";
            $zpl .= "^CF0,25\n";
            $zpl .= "^FO40,330^FD" . $b['material_description'] . "^FS\n";
            $zpl .= "^XZ\n\n";

            $zplAll .= $zpl;

            $qr = Builder::create()
                ->writer(new PngWriter())
                ->data($payload)
                ->size(460)
                ->margin(10)
                ->build();

            $dataUri = 'data:image/png;base64,' . base64_encode($qr->getString());

            $labels[] = [
                'material' => $b['material'],
                'desc'     => $b['material_description'],
                'dataUri'  => $dataUri,
            ];
        }

        return view('qrgenerator/qr_result', [
            'menu'     => 'generateqr',
            'title'    => 'Hasil Generate QR Barang',
            'labels'   => $labels,
            'zplData'  => $zplAll,   
        ]);
    }

    public function downloadZpl()
    {
        $zpl = $this->request->getPost('zplData');

        return $this->response
            ->setHeader('Content-Type', 'application/zpl')
            ->setHeader('Content-Disposition', 'attachment; filename=labels.zpl')
            ->setBody($zpl);
    }
}
