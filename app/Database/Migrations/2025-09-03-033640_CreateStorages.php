<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateStorages extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],

            // scope lokasi SAP
            'plant' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'storage_location' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'storage_location_desc' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true], // dropdown di UI

            // flat detail lokasi fisik (tanpa parent/level/code)
            'zone' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'rack' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'bin' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],

            // info tambahan
            'name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'capacity' => ['type' => 'INT', 'constraint' => 10, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'note' => ['type' => 'TEXT', 'null' => true],

            // audit
            'created_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['plant', 'storage_location']);
        $this->forge->addKey(['zone', 'rack', 'bin']);
        // Unik per kombinasi lokasi (catatan: NULL dianggap berbeda oleh MySQL)
        $this->forge->addUniqueKey(['plant', 'storage_location', 'zone', 'rack', 'bin'], 'uniq_pl_sl_zrb');

        $this->forge->createTable('storages', true, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
        ]);
    }

    public function down()
    {
        // drop total (bersih)
        $this->forge->dropTable('storages', true);
    }
}
