<?php
namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class BarangExcel
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
    ];

    public function import(string $filePath): array
    {
        $db = \Config\Database::connect();
        $ins = $upd = $skip = 0;
        $errs = [];

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($filePath)->getActiveSheet();

        // Header
        $headerRow = 1;
        $header = [];
        $highestCol = $sheet->getHighestColumn();
        for ($c = 'A'; $c <= $highestCol; $c++) {
            $v = $sheet->getCell($c . $headerRow)->getValue();
            if ($v === null)
                break;
            $header[] = trim((string) $v);
        }
        $required = array_keys($this->excelMap);
        $missing = array_diff($required, $header);
        if ($missing)
            throw new \RuntimeException('Header Excel tidak sesuai. Kurang: ' . implode(', ', $missing));

        // Map kolom
        $col = [];
        foreach ($this->excelMap as $excel => $dbField) {
            $idx = array_search($excel, $header, true);
            $col[$dbField] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
        }

        $importBatch = date('YmdHis');
        $maxRow = $sheet->getHighestRow();

        for ($r = $headerRow + 1; $r <= $maxRow; $r++) {
            $mat = trim((string) $sheet->getCell($col['material'] . $r)->getValue());
            if ($mat === '') {
                $skip++;
                continue;
            }

            $d = [
                'material' => $mat,
                'material_description' => (string) $sheet->getCell($col['material_description'] . $r)->getValue(),
                'plant' => (string) $sheet->getCell($col['plant'] . $r)->getValue(),
                'material_group' => (string) $sheet->getCell($col['material_group'] . $r)->getValue(),
                'storage_location' => (string) $sheet->getCell($col['storage_location'] . $r)->getValue(),
                'storage_location_desc' => (string) $sheet->getCell($col['storage_location_desc'] . $r)->getValue(),
                'df_stor_loc_level' => (string) $sheet->getCell($col['df_stor_loc_level'] . $r)->getValue(),
                'base_unit_of_measure' => (string) $sheet->getCell($col['base_unit_of_measure'] . $r)->getValue(),
                'qty_unrestricted' => $this->toDecimal($sheet->getCell($col['qty_unrestricted'] . $r)->getCalculatedValue()),
                'qty_transit_and_transfer' => $this->toDecimal($sheet->getCell($col['qty_transit_and_transfer'] . $r)->getCalculatedValue()),
                'qty_blocked' => $this->toDecimal($sheet->getCell($col['qty_blocked'] . $r)->getCalculatedValue()),
                'material_type' => (string) $sheet->getCell($col['material_type'] . $r)->getValue(),
                'import_batch' => $importBatch,
            ];

            try {
                [$ok, $isUpdate] = $this->upsert($db, $d);
                if ($ok) {
                    $isUpdate ? $upd++ : $ins++;
                } else {
                    $skip++;
                }
            } catch (\Throwable $e) {
                $errs[] = "Row $r [$mat] error: " . $e->getMessage();
            }
        }

        return [$ins, $upd, $skip, $errs];
    }

    public function exportRows(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->query('SELECT * FROM v_barang_excel')->getResultArray();

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
            'Material Type'
        ];

        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                $r['Material'],
                $r['Material Description'],
                $r['Plant'],
                $r['Material Group'],
                $r['Storage Location'],
                $r['Descr. of Storage Loc.'],
                $r['DF stor. loc. level'],
                $r['Base Unit of Measure'],
                $r['Unrestricted'],
                $r['Transit and Transfer'],
                $r['Blocked'],
                $r['Material Type'],
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
        for ($i = 1; $i <= count($headers); $i++)
            $s->getColumnDimensionByColumn($i)->setAutoSize(true);
        return $ss;
    }

    protected function upsert(BaseConnection $db, array $d): array
    {
        $db->query("INSERT INTO barang
          (material, material_description, plant, material_group, storage_location, storage_location_desc, df_stor_loc_level, base_unit_of_measure, qty_unrestricted, qty_transit_and_transfer, qty_blocked, material_type, import_batch)
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
          ON DUPLICATE KEY UPDATE
            material_description=VALUES(material_description),
            plant=VALUES(plant),
            material_group=VALUES(material_group),
            storage_location=VALUES(storage_location),
            storage_location_desc=VALUES(storage_location_desc),
            df_stor_loc_level=VALUES(df_stor_loc_level),
            base_unit_of_measure=VALUES(base_unit_of_measure),
            qty_unrestricted=VALUES(qty_unrestricted),
            qty_transit_and_transfer=VALUES(qty_transit_and_transfer),
            qty_blocked=VALUES(qty_blocked),
            material_type=VALUES(material_type),
            import_batch=VALUES(import_batch),
            updated_at=CURRENT_TIMESTAMP", [
            $d['material'],
            $d['material_description'],
            $d['plant'],
            $d['material_group'],
            $d['storage_location'],
            $d['storage_location_desc'],
            $d['df_stor_loc_level'],
            $d['base_unit_of_measure'],
            $d['qty_unrestricted'],
            $d['qty_transit_and_transfer'],
            $d['qty_blocked'],
            $d['material_type'],
            $d['import_batch'],
        ]);
        $existed = $db->table('barang')->select('id')->where('material', $d['material'])->get(1)->getRowArray();
        return [true, (bool) $existed];
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
