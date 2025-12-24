<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QrGeneratorController extends BaseController
{
    private function zplTemplate(): string
    {
        return <<<ZPL
            ^XA
            ^DFR:QR_LABEL.ZPL
            ^PW400
            ^L    H0,0
            ^PR2

            ^FO10,0
            ^BQN,2,3
            ^FN3^FS

            ^FO180,5
            ^A0N,24,28
            ^FB220,2,0,L,0
            ^FN1^FS

            ^FO181,5
            ^A0N,24,28
            ^FB220,2,0,L,0
            ^FN1^FS

            ^FO180,6
            ^A0N,24,28
            ^FB220,2,0,L,0
            ^FN1^FS

            ^FO180,45
            ^A0N,24,28
            ^FB220,2,0,L,0
            ^FN2^FS

            ^FO181,45
            ^A0N,24,28
            ^FB220,2,0,L,0
            ^FN2^FS

            ^FO180,46
            ^A0N,24,28
            ^FB220,2,0,L,0
            ^FN2^FS

            ^XZ
            ZPL;
    }


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

        return view('qrgenerator/generate_qr', [
            'menu'       => 'generateqr',
            'title'      => 'Generate QR Code Barang',
            'barang'     => $barang,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
            'q'          => $q,
            'error'      => null,
        ]);
    }

    public function generate()
    {
        $ids = (array) $this->request->getPost('barang_ids');

        if (!$ids) {
            return redirect()->back()->with('error', 'Pilih minimal satu barang.');
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

        $zplAll .= $this->zplTemplate() . "\n\n";

        foreach ($items as $b) {

            $payload = json_encode([
                'material' => $b['material'],
                'desc'     => $b['material_description']
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $zpl = "^XA\n";
            $zpl .= "^XFR:QR_LABEL.ZPL\n";
            $zpl .= "^FN1^FD{$b['material']}^FS\n";
            $zpl .= "^FN2^FD{$b['material_description']}^FS\n";
            $zpl .= "^FN3^FDLA,{$payload}^FS\n";
            $zpl .= "^XZ\n\n";

            $zplAll .= $zpl;

            $qr = Builder::create()
                ->writer(new PngWriter())
                ->data($payload)
                ->size(460)
                ->margin(10)
                ->build();

            $labels[] = [
                'material' => $b['material'],
                'desc'     => $b['material_description'],
                'dataUri'  => 'data:image/png;base64,' . base64_encode($qr->getString()),
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
