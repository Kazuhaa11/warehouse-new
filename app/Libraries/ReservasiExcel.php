<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;


class ReservasiExcel
{
    protected array $excelMap = [
        'Material'            => 'material',
        'Material Description' => 'material_description',
        'Plant'               => 'plant',
        'Storage Location'    => 'storage_location',
        'Posting Date'        => 'posting_date',
        'Qty in Un. of Entry' => 'qty_in_un_of_entry',
        'Reservation'         => 'reservation',
        'Purchase Order'      => 'purchase_order',
        'User name'           => 'user_name',
        'Batch'               => 'batch',
        'Movement Type'       => 'movement_type',
        'Material Document'   => 'material_document',
        'Text'                => 'text',
    ];

    public function import(string $filePath): array
    {
        $db = \Config\Database::connect();
        $ins = $upd = $skip = 0;

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($filePath)->getActiveSheet();

        $header = [];
        foreach (range('A', $sheet->getHighestColumn()) as $c) {
            $v = trim((string) $sheet->getCell($c . '1')->getValue());
            if ($v === '') break;
            $header[] = $v;
        }

        $missing = array_diff(array_keys($this->excelMap), $header);
        if ($missing) {
            throw new \RuntimeException(
                'Header tidak sesuai. Kurang: ' . implode(', ', $missing)
            );
        }

        $col = [];
        foreach ($this->excelMap as $excel => $dbField) {
            $idx = array_search($excel, $header, true);
            $col[$dbField] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
        }

        $maxRow = $sheet->getHighestRow();

        $db->transBegin();

        try {
            for ($r = 2; $r <= $maxRow; $r++) {

                $material = trim((string) $sheet->getCell($col['material'] . $r)->getValue());
                if ($material === '') {
                    $skip++;
                    continue;
                }

                $qty = $this->toInt(
                    $sheet->getCell($col['qty_in_un_of_entry'] . $r)->getCalculatedValue()
                );


                $postingRaw = $sheet->getCell($col['posting_date'] . $r)->getValue();

                $data = [
                    'material'           => $material,
                    'material_description' => (string) $sheet->getCell($col['material_description'] . $r)->getValue(),
                    'plant'              => (string) $sheet->getCell($col['plant'] . $r)->getValue(),
                    'storage_location'   => (string) $sheet->getCell($col['storage_location'] . $r)->getValue(),
                    'posting_date'       => $this->excelDateToYmd($postingRaw),
                    'qty_in_un_of_entry' => $qty,
                    'reservation'        => (string) $sheet->getCell($col['reservation'] . $r)->getValue(),
                    'purchase_order'     => (string) $sheet->getCell($col['purchase_order'] . $r)->getValue(),
                    'user_name'          => (string) $sheet->getCell($col['user_name'] . $r)->getValue(),
                    'batch'              => (string) $sheet->getCell($col['batch'] . $r)->getValue(),
                    'movement_type'      => (string) $sheet->getCell($col['movement_type'] . $r)->getValue(),
                    'material_document'  => (string) $sheet->getCell($col['material_document'] . $r)->getValue(),
                    'text'               => (string) $sheet->getCell($col['text'] . $r)->getValue(),
                ];

                [$isInsert, $isUpdate, $oldQty] = $this->upsertReservasi($db, $data);
                if ($isInsert) {
                    $ins++;
                    $this->applyStock($db, $data['material'], $qty);
                } elseif ($isUpdate) {
                    $upd++;
                    $delta = $qty - $oldQty;
                    if ($delta != 0) {
                        $this->applyStock($db, $data['material'], $delta);
                    }
                } else {
                    $skip++;
                }
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return [$ins, $upd, $skip];
    }

    protected function upsertReservasi(BaseConnection $db, array $d): array
    {
        $old = $db->table('reservasi_list')
            ->where([
                'material' => $d['material'],
                'plant' => $d['plant'],
                'storage_location' => $d['storage_location'],
                'posting_date' => $d['posting_date'],
                'reservation' => $d['reservation'],
                'purchase_order' => $d['purchase_order'],
            ])
            ->get()
            ->getRowArray();

        $oldQty = $old['qty_in_un_of_entry'] ?? 0;

        $db->query("
        INSERT INTO reservasi_list
        (material, material_description, plant, storage_location, posting_date,
         qty_in_un_of_entry, reservation, purchase_order, user_name, batch,
         movement_type, material_document, text)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            qty_in_un_of_entry = VALUES(qty_in_un_of_entry),
            user_name = VALUES(user_name),
            batch = VALUES(batch),
            movement_type = VALUES(movement_type),
            material_document = VALUES(material_document),
            text = VALUES(text),
            updated_at = CURRENT_TIMESTAMP
    ", array_values($d));

        $affected = $db->affectedRows();

        return [
            $affected === 1,
            $affected === 2,
            $oldQty
        ];
    }


    protected function applyStock(BaseConnection $db, string $material, float $qty): void
    {
        $db->query("
            UPDATE barang
            SET qty_unrestricted = qty_unrestricted + ?
            WHERE material = ?
        ", [$qty, $material]);
    }

    protected function toInt($v): int
    {
        if ($v === null || $v === '') {
            return 0;
        }

        if (is_numeric($v)) {
            return (int) round($v);
        }

        if (is_string($v)) {
            $v = str_replace([' ', '.'], '', $v);
            $v = str_replace(',', '.', $v);
            return (int) round((float) $v);
        }

        return 0;
    }


    protected function excelDateToYmd($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $value = trim((string) $value);

        $dt = \DateTime::createFromFormat('d/m/Y', $value);
        if ($dt !== false) {
            return $dt->format('Y-m-d');
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        if ($dt !== false) {
            return $dt->format('Y-m-d');
        }

        return null;
    }
}
