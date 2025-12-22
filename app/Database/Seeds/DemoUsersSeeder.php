<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    public function run()
    {
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');

        $create = function (
            string $email,
            string $username,
            string $role,
            ?string $plant,       
            string $password
        ) use ($db, $now) {

            $exists = $db->table('auth_identities')
                ->where([
                    'type'   => 'email_password',
                    'secret' => $email,
                ])
                ->countAllResults();

            if ($exists) {
                echo "Skip: {$email} sudah ada\n";
                return;
            }

            $db->table('users')->insert([
                'username'   => $username,
                'role'       => $role,
                'plant'      => $plant,   
                'active'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $uid = (int) $db->insertID();

            $db->table('auth_identities')->insert([
                'user_id'    => $uid,
                'type'       => 'email_password',
                'name'       => $username,
                'secret'     => $email,
                'secret2'    => password_hash($password, PASSWORD_BCRYPT),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $plantLabel = $plant ?? 'ALL';
            echo "OK: user#$uid ({$email}) role={$role} plant={$plantLabel}\n";
        };


        $create(
            'user@gmail.com',
            'user',
            'mobile',
            null,
            'user123'
        );

        $create(
            'admin1200@gmail.com',
            'admin1200',
            'admin',
            '1200',
            'admin1200'
        );

        $create(
            'admin1300@gmail.com',
            'admin1300',
            'admin',
            '1300',
            'admin1300'
        );

        $create(
            'super@gmail.com',
            'super',
            'admin',
            null,
            'super123'
        );
    }
}
