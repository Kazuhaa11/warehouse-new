<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use CodeIgniter\Config\Services;

if (!function_exists('peminjaman_report_build_html')) {
    /**
     * 
     *
     * @param array $headers       
     * @param array $itemsByHeader 
     * @param array $filters       
     */
    function peminjaman_report_build_html(array $headers, array $itemsByHeader, array $filters): string
    {
        $style = '<style>
            body{font-family:DejaVu Sans,Arial,sans-serif;font-size:12px}
            .nota{border:1px solid #bbb;padding:10px 12px;margin:10px 0;border-radius:6px}
            table{width:100%;border-collapse:collapse;margin-top:6px}
            th,td{border:1px solid #ccc;padding:5px}
            th{background:#f2f2f2}
            .right{text-align:right}
            .row{display:flex;flex-wrap:wrap}
            .col{flex:1;min-width:200px;padding-right:10px}
        </style>';

        $filterText = 'Filter: ';
        if (($filters['mode'] ?? 'range') === 'range') {
            $filterText .= "Tanggal {$filters['fromDate']} s.d. {$filters['toDate']}";
        } else {
            $filterText .= "Bulan {$filters['month']}/{$filters['year']}";
        }
        if (!empty($filters['plant'])) {
            $filterText .= " • Plant {$filters['plant']}";
        }
        $filterText .= " • Sort: " . ($filters['sortBy'] ?? 'tanggal') . ' ' . strtoupper($filters['sortDir'] ?? 'DESC');

        $out = "{$style}<h2>Laporan Peminjaman</h2>
                <div style='color:#555;font-size:11px;margin-bottom:8px'>{$filterText}</div>";

        if (!$headers) {
            return $out . '<p>Tidak ada data sesuai filter.</p>';
        }

        foreach ($headers as $h) {
            $items = $itemsByHeader[$h['id']] ?? [];
            $plants = $h['plants'] ?: '-';
            $peminjam = $h['peminjam_username'] ?: '-';
            $note = htmlspecialchars((string) ($h['note'] ?? ''), ENT_QUOTES, 'UTF-8');

            $out .= "<div class='nota'>
              <div class='row'>
                <div class='col'>
                  <div><b>No Nota:</b> {$h['no_nota']}</div>
                  <div><b>Tanggal:</b> {$h['tanggal']}</div>
                  <div><b>Plant:</b> {$plants}</div>
                </div>
                <div class='col'>
                  <div><b>Peminjam:</b> {$peminjam}</div>
                  <div><b>Status:</b> " . strtoupper($h['status']) . "</div>
                  <div><b>Catatan:</b> {$note}</div>
                </div>
              </div>
              <table>
                <thead>
                  <tr>
                    <th style='width:35px'>#</th>
                    <th>Material</th>
                    <th>Deskripsi</th>
                    <th style='width:90px'>Stor. Loc</th>
                    <th style='width:90px' class='right'>Qty</th>
                    <th style='width:70px'>UoM</th>
                  </tr>
                </thead>
                <tbody>";

            if (!$items) {
                $out .= "<tr><td colspan='6' style='color:#777;font-size:11px'>Tidak ada item</td></tr>";
            } else {
                $i = 1;
                foreach ($items as $it) {
                    $mat = htmlspecialchars((string) ($it['material'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $desc = htmlspecialchars((string) ($it['material_description'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $sloc = htmlspecialchars((string) ($it['storage_location'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $qty = number_format((float) ($it['requested_qty'] ?? 0), 3);
                    $uom = htmlspecialchars((string) ($it['uom'] ?? ''), ENT_QUOTES, 'UTF-8');

                    $out .= "<tr>
                      <td class='right'>{$i}</td>
                      <td>{$mat}</td>
                      <td>{$desc}</td>
                      <td>{$sloc}</td>
                      <td class='right'>{$qty}</td>
                      <td>{$uom}</td>
                    </tr>";
                    $i++;
                }
            }

            $out .= "</tbody></table></div>";
        }

        return $out;
    }
}

if (!function_exists('peminjaman_report_send_pdf')) {
    /**
     * 
     *
     * @param string $html
     * @param string $filename
     * @param bool   $inline   
     */
    function peminjaman_report_send_pdf(string $html, string $filename = 'laporan.pdf', bool $inline = true)
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $response = Services::response();
        $response->setHeader('Content-Type', 'application/pdf');
        $dispo = ($inline ? 'inline' : 'attachment') . '; filename="' . $filename . '"';
        $response->setHeader('Content-Disposition', $dispo);
        $response->setBody($dompdf->output());
        return $response;
    }
}
