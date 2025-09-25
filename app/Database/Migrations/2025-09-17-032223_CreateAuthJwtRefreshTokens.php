<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuthJwtRefreshTokens extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'refresh_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'revoked' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'expires_at' => ['type' => 'DATETIME', 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('revoked');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('auth_jwt_refresh_tokens', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('auth_jwt_refresh_tokens', true);
    }
}
