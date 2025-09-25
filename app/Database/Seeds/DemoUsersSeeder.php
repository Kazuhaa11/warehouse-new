<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    public function run()
    {
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');

        $create = function (string $email, string $username, string $role, string $password) use ($db, $now) {
            $exists = $db->table('auth_identities')
                ->where(['type' => 'email_password', 'secret' => $email])
                ->countAllResults();

            if ($exists) {
                echo "Skip: $email sudah ada\n";
                return;
            }


            $db->table('users')->insert([
                'username'   => $username,
                'role'       => $role,  
                'active'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $uid = (int) $db->insertID();

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $db->table('auth_identities')->insert([
                'user_id'    => $uid,
                'type'       => 'email_password',
                'name'       => $username,
                'secret'     => $email,      
                'secret2'    => $hash,      
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            echo "OK: user#$uid ($email) dibuat sebagai $role\n";
        };

        // === bikin 2 akun contoh ===
        $create('admin@gmail.com', 'admin', 'admin',  'admin123'); // login web: aud=web
        $create('user@gmail.com',  'user',  'mobile', 'user123');  // login mobile: aud=mobile
    }
}
