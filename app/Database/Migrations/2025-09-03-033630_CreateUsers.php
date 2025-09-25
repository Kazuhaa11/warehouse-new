<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateUsers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 191],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 191],
            'role'          => ['type' => 'ENUM', 'constraint' => ['admin','user'], 'default' => 'user'],
            'phone'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'    => [
                'type'      => 'TIMESTAMP',
                'null'      => false,
                'default'   => new RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('users', true);
    }
}
