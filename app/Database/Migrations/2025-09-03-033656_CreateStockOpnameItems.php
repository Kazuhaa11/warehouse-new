<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateStockOpnameItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'session_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'barang_id' => ['type' => 'BIGINT', 'unsigned' => true],

            // NEW: normalisasi ke storages
            'storage_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],

            'material' => ['type' => 'VARCHAR', 'constraint' => 100],

            // Snapshot (opsional) untuk jejak teks dari SAP
            'storage_location' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],

            'counted_qty' => ['type' => 'DECIMAL', 'constraint' => '20,3', 'default' => 0],
            'system_qty_unrestricted' => ['type' => 'DECIMAL', 'constraint' => '20,3', 'default' => 0],
            // diff_qty ditambahkan via ALTER setelah createTable
            'note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('material');
        $this->forge->addKey('storage_id'); // index ke FK
        $this->forge->addForeignKey('session_id', 'stock_opname_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('barang_id', 'barang', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('storage_id', 'storages', 'id', 'SET NULL', 'RESTRICT');

        $this->forge->createTable('stock_opname_items', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);

        // Kolom selisih sebagai generated column
        $this->db->query("
            ALTER TABLE stock_opname_items
            ADD COLUMN diff_qty DECIMAL(20,3) AS (counted_qty - system_qty_unrestricted) STORED
        ");
    }

    public function down()
    {
        $this->forge->dropTable('stock_opname_items', true);
    }
}
