<?php

namespace App\Controllers\Api;

use App\Libraries\Auth as AuthCtx;
use CodeIgniter\Database\BaseConnection;

class StatsApi extends BaseApiController
{

    protected function resolvePlant(): ?string
    {
        if (AuthCtx::isSuperAdmin()) {
            return $this->request->getGet('plant') ?: null;
        }
        return AuthCtx::plant();
    }

    public function dashboard()
    {
        $db = \Config\Database::connect();
        $plant = $this->resolvePlant();

        return $this->ok([
            'material_total'     => $this->countWithPlant($db, 'barang', $plant),
            'peminjaman_total'   => $this->countWithPlantFirstExisting(
                $db,
                ['peminjaman', 'peminjaman_header'],
                $plant
            ),
            'stock_opname_total' => $this->countWithPlantFirstExisting(
                $db,
                ['stock_opname_sessions', 'stock_opname', 'so_sessions'],
                $plant
            ),
        ]);
    }

    private function countWithPlant(
        BaseConnection $db,
        string $table,
        ?string $plant
    ): int {
        try {
            if (!$this->tableExists($db, $table)) {
                return 0;
            }

            $builder = $db->table($table);

            if ($plant !== null && $this->columnExists($db, $table, 'plant')) {
                $builder->where('plant', $plant);
            }

            return (int) $builder->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function countWithPlantFirstExisting(
        BaseConnection $db,
        array $tables,
        ?string $plant
    ): int {
        foreach ($tables as $table) {
            if ($this->tableExists($db, $table)) {
                return $this->countWithPlant($db, $table, $plant);
            }
        }
        return 0;
    }

    private function tableExists(BaseConnection $db, string $table): bool
    {
        $prefixed = $db->getPrefix() . $table;
        $q = $db->query("SHOW TABLES LIKE ?", [$prefixed]);
        return $q && $q->getNumRows() > 0;
    }

    private function columnExists(
        BaseConnection $db,
        string $table,
        string $column
    ): bool {
        try {
            $q = $db->query("SHOW COLUMNS FROM {$db->getPrefix()}{$table} LIKE ?", [$column]);
            return $q && $q->getNumRows() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
