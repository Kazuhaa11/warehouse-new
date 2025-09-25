<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateStockOpnameSessions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code'         => ['type' => 'VARCHAR', 'constraint' => 30],
            'scheduled_at' => ['type' => 'DATETIME'],
            'finalized_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by'   => ['type' => 'BIGINT', 'unsigned' => true],
            'note'         => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'   => [
                'type'      => 'TIMESTAMP',
                'null'      => false,
                'default'   => new RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'RESTRICT');

        $this->forge->createTable('stock_opname_sessions', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('stock_opname_sessions', true);
    }
}
