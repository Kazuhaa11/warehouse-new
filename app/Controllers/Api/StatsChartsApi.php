<?php
namespace App\Controllers\Api;

class StatsChartsApi extends BaseApiController
{
    public function material()
    {
        return $this->ok($this->monthlySeries('barang', 'created_at', 6));
    }

    public function peminjaman()
    {
        // coba beberapa kandidat nama tabel header peminjaman
        $candidates = ['peminjaman', 'peminjaman_header'];
        return $this->ok($this->monthlySeriesFirstExisting($candidates, 'created_at', 6));
    }

    public function stockOpname()
    {
        // coba beberapa kandidat nama tabel sesi stock opname
        $candidates = ['stock_opname', 'stock_opname_sessions', 'so_sessions'];
        return $this->ok($this->monthlySeriesFirstExisting($candidates, 'created_at', 6));
    }

    // === Helpers ===
    private function monthlySeriesFirstExisting(array $candidates, string $dateCol, int $months): array
    {
        $db = \Config\Database::connect();
        foreach ($candidates as $t) {
            if ($this->tableExists($db, $t)) {
                return $this->monthlySeries($t, $dateCol, $months);
            }
        }
        return $this->emptySeries($months);
    }

    private function monthlySeries(string $table, string $dateCol, int $months): array
    {
        $db = \Config\Database::connect();

        // buat daftar bulan (label) dari paling lama ke terbaru
        $labels = [];
        $start = new \DateTime('first day of -' . ($months - 1) . ' month'); // 6 bulan ke belakang
        for ($i = 0; $i < $months; $i++) {
            $labels[] = $start->format('Y-m');
            $start->modify('+1 month');
        }

        if (!$this->tableExists($db, $table)) {
            return $this->emptySeries($months);
        }

        // ambil agregat per bulan
        $prefix = $db->getPrefix();
        $tbl = $prefix . $table;

        // ambil dari awal bulan pertama
        $firstMonth = $labels[0] . '-01';

        $sql = "
            SELECT DATE_FORMAT($dateCol, '%Y-%m') AS ym, COUNT(*) AS cnt
            FROM $tbl
            WHERE $dateCol >= ?
            GROUP BY ym
        ";
        $rows = $db->query($sql, [$firstMonth])->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['ym']] = (int) $r['cnt'];
        }

        $series = [];
        foreach ($labels as $ym) {
            $series[] = $map[$ym] ?? 0;
        }

        // label human-readable (mis. Apr 2025)
        $labelsPretty = array_map(static function ($ym) {
            $dt = \DateTime::createFromFormat('Y-m', $ym);
            return $dt ? $dt->format('M Y') : $ym;
        }, $labels);

        return [
            'labels' => $labelsPretty,  // ["Apr 2025", ...]
            'series' => $series,        // [10, 7, ...]
            'raw_labels' => $labels,        // ["2025-04", ...]
            'table' => $table,
            'months' => $months,
        ];
    }

    private function emptySeries(int $months): array
    {
        $labels = [];
        $start = new \DateTime('first day of -' . ($months - 1) . ' month');
        for ($i = 0; $i < $months; $i++) {
            $labels[] = $start->format('M Y');
            $start->modify('+1 month');
        }
        return [
            'labels' => $labels,
            'series' => array_fill(0, $months, 0),
            'raw_labels' => $labels,
            'table' => null,
            'months' => $months,
        ];
    }

    private function tableExists($db, string $table): bool
    {
        $prefixed = $db->getPrefix() . $table;
        $q = $db->query("SHOW TABLES LIKE ?", [$prefixed]);
        return $q && $q->getNumRows() > 0;
    }
}
