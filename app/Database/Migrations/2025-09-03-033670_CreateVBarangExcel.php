<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVBarangExcel extends Migration
{
    public function up()
    {
        $this->db->query(<<<'SQL'
CREATE OR REPLACE VIEW v_barang_excel AS
SELECT
  material                             AS `Material`,
  material_description                 AS `Material Description`,
  plant                                AS `Plant`,
  material_group                       AS `Material Group`,
  storage_location                     AS `Storage Location`,
  storage_location_desc                AS `Descr. of Storage Loc.`,
  df_stor_loc_level                    AS `DF stor. loc. level`,
  base_unit_of_measure                 AS `Base Unit of Measure`,
  qty_unrestricted                     AS `Unrestricted`,
  qty_transit_and_transfer             AS `Transit and Transfer`,
  qty_blocked                          AS `Blocked`,
  material_type                        AS `Material Type`
FROM barang
SQL);
    }

    public function down()
    {
        $this->db->query('DROP VIEW IF EXISTS v_barang_excel');
    }
}
