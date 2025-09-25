<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePeminjaman extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'no_nota' => ['type' => 'VARCHAR', 'constraint' => 30],

            // hanya satu kolom peminjam_id
            'peminjam_id' => ['type' => 'BIGINT', 'unsigned' => true],

            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'submitted', 'approved', 'rejected', 'loaned', 'returned', 'lost'], 'default' => 'draft'],
            'borrow_date' => ['type' => 'DATETIME'],
            'due_date' => ['type' => 'DATETIME', 'null' => true],
            'return_date' => ['type' => 'DATETIME', 'null' => true],
            'note' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('no_nota');
        $this->forge->addKey('status');
        $this->forge->addKey(['borrow_date', 'due_date']);

        // FK: peminjam_id -> users.id
        $this->forge->addForeignKey('peminjam_id', 'users', 'id', 'CASCADE', 'RESTRICT');

        $this->forge->createTable('peminjaman', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('peminjaman', true);
    }
}
