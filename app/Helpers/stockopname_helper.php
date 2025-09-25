<?php

use CodeIgniter\Database\BaseConnection;

if (!function_exists('normHeader')) {
    function normHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = str_replace('&', 'and', $h);
        $h = preg_replace('/[^a-z0-9]+/i', '', $h) ?? $h;
        return $h;
    }
}

if (!function_exists('headerMap')) {
    function headerMap(): array
    {
        return [
            // qty system
            'unrestricted' => 'system_qty_unrestricted',
            'qtyunrestricted' => 'system_qty_unrestricted',
            'unrestrictedstock' => 'system_qty_unrestricted',
            'stockunrestricted' => 'system_qty_unrestricted',

            'transitandtransfer' => 'system_qty_transit_and_transfer',
            'transittransfer' => 'system_qty_transit_and_transfer',
            'transit' => 'system_qty_transit_and_transfer',
            'transfer' => 'system_qty_transit_and_transfer',

            'blocked' => 'system_qty_blocked',
            'blockedstock' => 'system_qty_blocked',

            // counted
            'counted' => 'counted_qty',
            'countedqty' => 'counted_qty',
            'count' => 'counted_qty',
            'qtycounted' => 'counted_qty',

            // master keys
            'material' => 'material',
            'materialcode' => 'material',
            'matnr' => 'material',

            'storagelocation' => 'storage_location',
            'sloc' => 'storage_location',
            'storloc' => 'storage_location',

            // material description -> masuk ke note
            'materialdescription' => '__material_description',
            'maktx' => '__material_description',
            'description' => '__material_description',
            'desc' => '__material_description',

            // catatan
            'note' => 'note',
            'remark' => 'note',
            'remarks' => 'note',
        ];
    }
}

/**
 * Ambil nilai kolom dari row (array indexed) berdasarkan peta header->index dan key internal yang diinginkan.
 *
 * @param array $row  Baris data dari XLSX/CSV (numeric keys).
 * @param array $map  Peta [normHeader => columnIndex].
 * @param string $wantKey  Key internal (mis. 'system_qty_unrestricted', 'material', '__material_description', dst).
 * @param mixed $default  Nilai default bila tidak ditemukan.
 */
if (!function_exists('getValByHeader')) {
    function getValByHeader(array $row, array $map, string $wantKey, $default = null)
    {
        $hm = headerMap();
        foreach ($map as $normHeader => $idx) {
            $internal = $hm[$normHeader] ?? null;
            if ($internal === $wantKey && array_key_exists($idx, $row)) {
                return $row[$idx];
            }
        }
        return $default;
    }
}

/**
 *
 * @param array               $data        input (material, storage_location, barang_id)
 * @param string              $barangTable nama tabel barang (default 'barang')
 * @param BaseConnection|null $db          optional DB connection
 * @return array [u, tt, b, material, storage_location, barang_id]
 */
function resolveSystemQty(array $data, string $barangTable = 'barang', BaseConnection $db = null): array
{
    $db = $db ?? db_connect();
    $material = $data['material'] ?? null;
    $sloc = $data['storage_location'] ?? null;
    $barangId = $data['barang_id'] ?? null;

    $u = $tt = $b = 0.0;
    $matOut = $material;
    $slocOut = $sloc;
    $bidOut = $barangId;

    $take = function (array $row) use (&$u, &$tt, &$b, &$matOut, &$slocOut, &$bidOut) {
        $u = (float) ($row['qty_unrestricted'] ?? 0);
        $tt = (float) ($row['qty_transit_and_transfer'] ?? 0);
        $b = (float) ($row['qty_blocked'] ?? 0);
        $matOut = $matOut ?? ($row['material'] ?? null);
        $slocOut = $slocOut ?? ($row['storage_location'] ?? null);
        $bidOut = (int) ($row['id'] ?? 0) ?: $bidOut;
    };

    if ($barangId) {
        if ($r = $db->table($barangTable)->where('id', $barangId)->get()->getRowArray())
            $take($r);
    } elseif ($material && $sloc) {
        if ($r = $db->table($barangTable)->where('material', $material)->where('storage_location', $sloc)->get()->getRowArray())
            $take($r);
    } elseif ($material) {
        if ($r = $db->table($barangTable)->where('material', $material)->get()->getRowArray())
            $take($r);
    }

    return [$u, $tt, $b, $matOut, $slocOut, $bidOut];
}

function buildSummary(array $items): array
{
    $s = [
        'total_items' => count($items),
        'total_counted' => 0.0,
        'total_system_u' => 0.0,
        'total_system_tt' => 0.0,
        'total_system_b' => 0.0,
        'total_system_all' => 0.0,
        'total_diff' => 0.0,
        'plus' => 0,
        'minus' => 0,
        'equal' => 0,
    ];
    foreach ($items as $it) {
        $u = (float) ($it['qty_unrestricted'] ?? 0);
        $tt = (float) ($it['qty_transit_and_transfer'] ?? 0);
        $b = (float) ($it['qty_blocked'] ?? 0);
        $sys = $u + $tt + $b;
        $cnt = (float) ($it['counted_qty'] ?? 0);
        $dif = $cnt - $sys;
        $s['total_system_u'] += $u;
        $s['total_system_tt'] += $tt;
        $s['total_system_b'] += $b;
        $s['total_system_all'] += $sys;
        $s['total_counted'] += $cnt;
        $s['total_diff'] += $dif;
        if ($dif > 0)
            $s['plus']++;
        elseif ($dif < 0)
            $s['minus']++;
        else
            $s['equal']++;
    }
    return $s;
}
