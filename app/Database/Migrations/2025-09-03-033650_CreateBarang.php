<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateBarang extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],

            'material' => ['type' => 'VARCHAR', 'constraint' => 100],
            'material_description' => ['type' => 'VARCHAR', 'constraint' => 255],

            // tetap dipertahankan untuk kompatibilitas data SAP (denormalized)
            'plant' => ['type' => 'VARCHAR', 'constraint' => 20],
            'material_group' => ['type' => 'VARCHAR', 'constraint' => 50],

            'storage_location' => ['type' => 'VARCHAR', 'constraint' => 50],
            'storage_location_desc' => ['type' => 'VARCHAR', 'constraint' => 150],
            'df_stor_loc_level' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],

            'base_unit_of_measure' => ['type' => 'VARCHAR', 'constraint' => 20],

            'qty_unrestricted' => ['type' => 'DECIMAL', 'constraint' => '20,3', 'default' => 0],
            'qty_transit_and_transfer' => ['type' => 'DECIMAL', 'constraint' => '20,3', 'default' => 0],
            'qty_blocked' => ['type' => 'DECIMAL', 'constraint' => '20,3', 'default' => 0],

            'material_type' => ['type' => 'VARCHAR', 'constraint' => 50],

            // NEW: normalisasi ke tabel storages
            'storage_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],

            'import_batch' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('material');
        $this->forge->addKey('plant');
        $this->forge->addKey('material_group');
        $this->forge->addKey('storage_location');
        $this->forge->addKey('material_type');

        // index untuk storage_id
        $this->forge->addKey('storage_id');

        // FK ke storages
        $this->forge->addForeignKey('storage_id', 'storages', 'id', 'SET NULL', 'RESTRICT');

        $this->forge->createTable('barang', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);

        // Indeks kombinasi (filter cepat)
        $this->db->query('CREATE INDEX idx_barang_search_combo ON barang (storage_location, material_group, material_type)');

        // FULLTEXT untuk pencarian cepat (pastikan engine mendukung)
        $this->db->query('ALTER TABLE barang ADD FULLTEXT ftx_barang_text (material, material_description)');
    }

    public function down()
    {
        $this->forge->dropTable('barang', true);
    }
}
