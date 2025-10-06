<?php
namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StockOpnameExcel
{
    protected array $excelMap = [
        'Material' => 'material',
        'Material Description' => 'material_description',
        'Plant' => 'plant',
        'Material Group' => 'material_group',
        'Storage Location' => 'storage_location',
        'Descr. of Storage Loc.' => 'storage_location_desc',
        'DF stor. loc. level' => 'df_stor_loc_level',
        'Base Unit of Measure' => 'base_unit_of_measure',
        'Unrestricted' => 'qty_unrestricted',
        'Transit and Transfer' => 'qty_transit_and_transfer',
        'Blocked' => 'qty_blocked',
        'Material Type' => 'material_type',
        // kolom opname (opsional)
        'Counted' => 'counted_qty',
        'Note' => 'note',
    ];

    /**
     * 
     * 
     * @return array 
     */
    public function import(string $filePath, int $sessionId, string $barangTable = 'barang'): array
    {
        helper('stockopname');

        $db = \Config\Database::connect();
        $ins = $skip = 0;
        $errs = [];

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($filePath)->getActiveSheet();

        $headerRow = 1;
        $header = [];
        $highestCol = $sheet->getHighestColumn();
        for ($c = 'A'; $c <= $highestCol; $c++) {
            $v = $sheet->getCell($c . $headerRow)->getValue();
            if ($v === null)
                break;
            $header[] = trim((string) $v);
        }

        if (!in_array('Material', $header, true)) {
            throw new \RuntimeException('Header Excel tidak sesuai. Kolom "Material" wajib ada.');
        }

        $col = [];
        foreach ($this->excelMap as $excel => $field) {
            $idx = array_search($excel, $header, true);
            if ($idx === false)
                continue;
            $col[$field] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
        }

        $maxRow = $sheet->getHighestRow();

        for ($r = $headerRow + 1; $r <= $maxRow; $r++) {
            $mat = trim((string) $this->cell($sheet, $col, 'material', $r));
            if ($mat === '') {
                $skip++;
                continue;
            }

            $sloc = (string) $this->cell($sheet, $col, 'storage_location', $r);

            $u = $this->toDecimal($this->calc($sheet, $col, 'qty_unrestricted', $r));
            $tt = $this->toDecimal($this->calc($sheet, $col, 'qty_transit_and_transfer', $r));
            $b = $this->toDecimal($this->calc($sheet, $col, 'qty_blocked', $r));
            $cnt = $this->toDecimal($this->calc($sheet, $col, 'counted_qty', $r)); 

            $row = [
                'session_id' => $sessionId,
                'material' => $mat,
                'material_description' => (string) $this->cell($sheet, $col, 'material_description', $r),
                'plant' => (string) $this->cell($sheet, $col, 'plant', $r),
                'material_group' => (string) $this->cell($sheet, $col, 'material_group', $r),
                'storage_location' => $sloc,
                'storage_location_desc' => (string) $this->cell($sheet, $col, 'storage_location_desc', $r),
                'df_stor_loc_level' => (string) $this->cell($sheet, $col, 'df_stor_loc_level', $r),
                'base_unit_of_measure' => (string) $this->cell($sheet, $col, 'base_unit_of_measure', $r),
                'material_type' => (string) $this->cell($sheet, $col, 'material_type', $r),

                'qty_unrestricted' => $u,
                'qty_transit_and_transfer' => $tt,
                'qty_blocked' => $b,

                'counted_qty' => $cnt,
                'note' => (string) $this->cell($sheet, $col, 'note', $r) ?: null,
            ];

            [$ru, $rtt, $rb, $rMat, $rSloc, $bid] = resolveSystemQty([
                'material' => $row['material'],
                'storage_location' => $row['storage_location'],
            ], $barangTable);

            $row['barang_id'] = $bid ?: null;

            if (($u + $tt + $b) == 0.0) {
                $row['qty_unrestricted'] = $ru;
                $row['qty_transit_and_transfer'] = $rtt;
                $row['qty_blocked'] = $rb;
            }
            if ($sloc === '' && $rSloc) {
                $row['storage_location'] = $rSloc;
            }

            foreach (['material_description', 'plant', 'material_group', 'storage_location', 'storage_location_desc', 'df_stor_loc_level', 'base_unit_of_measure', 'material_type', 'note'] as $k) {
                if (array_key_exists($k, $row) && $row[$k] === '')
                    $row[$k] = null;
            }

            try {
                $ok = $db->table('stock_opname_items')->insert($row);
                if ($ok)
                    $ins++;
                else
                    $skip++;
            } catch (\Throwable $e) {
                $errs[] = "Row $r [$mat] error: " . $e->getMessage();
            }
        }

        return [$ins, $skip, $errs];
    }

    public function exportRows(int $sessionId): array
    {
        $db = \Config\Database::connect();

        $rows = $db->table('stock_opname_items')
            ->where('session_id', $sessionId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $headers = [
            'Material',
            'Material Description',
            'Plant',
            'Material Group',
            'Storage Location',
            'Descr. of Storage Loc.',
            'DF stor. loc. level',
            'Base Unit of Measure',
            'Unrestricted',
            'Transit and Transfer',
            'Blocked',
            'Counted',  
            'Diff',     
            'Material Type',
            'Note',
        ];

        $data = [];
        foreach ($rows as $r) {
            $u = (float) ($r['qty_unrestricted'] ?? 0);
            $tt = (float) ($r['qty_transit_and_transfer'] ?? 0);
            $b = (float) ($r['qty_blocked'] ?? 0);
            $cnt = (float) ($r['counted_qty'] ?? 0);
            $diff = $cnt - ($u + $tt + $b);

            $data[] = [
                $r['material'] ?? '',
                $r['material_description'] ?? '',
                $r['plant'] ?? '',
                $r['material_group'] ?? '',
                $r['storage_location'] ?? '',
                $r['storage_location_desc'] ?? '',
                $r['df_stor_loc_level'] ?? '',
                $r['base_unit_of_measure'] ?? '',
                $u,
                $tt,
                $b,
                $cnt,
                $diff,
                $r['material_type'] ?? '',
                $r['note'] ?? '',
            ];
        }

        return [$headers, $data];
    }

    public function makeSpreadsheet(array $headers, array $rows): Spreadsheet
    {
        $ss = new Spreadsheet();
        $s = $ss->getActiveSheet();
        $s->fromArray($headers, null, 'A1');
        if ($rows)
            $s->fromArray($rows, null, 'A2');
        for ($i = 1; $i <= count($headers); $i++) {
            $s->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
        return $ss;
    }

    protected function cell($sheet, array $col, string $key, int $row)
    {
        if (!isset($col[$key]))
            return '';
        return $sheet->getCell($col[$key] . $row)->getValue();
    }
    protected function calc($sheet, array $col, string $key, int $row)
    {
        if (!isset($col[$key]))
            return 0;
        return $sheet->getCell($col[$key] . $row)->getCalculatedValue();
    }
    protected function toDecimal($v): float
    {
        if ($v === null || $v === '')
            return 0.0;
        if (is_string($v)) {
            $v = str_replace([' ', '.'], '', $v);
            $v = str_replace(',', '.', $v);
        }
        return (float) $v;
    }
}
