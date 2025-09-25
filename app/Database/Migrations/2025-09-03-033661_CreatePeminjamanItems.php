<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePeminjamanItems extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'peminjaman_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'barang_id' => ['type' => 'BIGINT', 'unsigned' => true],

            // NEW: normalisasi ke storages
            'storage_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],

            'material' => ['type' => 'VARCHAR', 'constraint' => 100],
            'requested_qty' => ['type' => 'DECIMAL', 'constraint' => '20,3', 'default' => 0],
            'approved_qty' => ['type' => 'DECIMAL', 'constraint' => '20,3', 'default' => 0],
            'uom' => ['type' => 'VARCHAR', 'constraint' => 20],

            // Snapshot (opsional)
            'storage_location' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],

            'note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('material');
        $this->forge->addKey('storage_id'); // index ke FK
        $this->forge->addForeignKey('peminjaman_id', 'peminjaman', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('barang_id', 'barang', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('storage_id', 'storages', 'id', 'SET NULL', 'RESTRICT');

        $this->forge->createTable('peminjaman_items', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('peminjaman_items', true);
    }
}
