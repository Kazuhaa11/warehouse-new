<?php
namespace App\Controllers\Api;

class StatsApi extends BaseApiController
{
    public function dashboard()
    {
        $db = \Config\Database::connect();

        $data = [
            'material_total' => $this->countTable($db, 'barang'),
            'peminjaman_total' => $this->countFirstExisting($db, ['peminjaman', 'peminjaman_header']),
            'stock_opname_total' => $this->countFirstExisting($db, ['stock_opname', 'stock_opname_sessions', 'so_sessions']),
        ];

        return $this->ok($data);
    }

    private function countTable($db, string $table): int
    {
        try {
            if (!$this->tableExists($db, $table))
                return 0;
            return (int) $db->table($table)->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countFirstExisting($db, array $candidates): int
    {
        foreach ($candidates as $t) {
            if ($this->tableExists($db, $t)) {
                try {
                    return (int) $db->table($t)->countAllResults();
                } catch (\Throwable $e) {
                    return 0;
                }
            }
        }
        return 0;
    }

    private function tableExists($db, string $table): bool
    {
        $prefixed = $db->getPrefix() . $table;
        $q = $db->query("SHOW TABLES LIKE ?", [$prefixed]);
        return $q && $q->getNumRows() > 0;
    }
}
