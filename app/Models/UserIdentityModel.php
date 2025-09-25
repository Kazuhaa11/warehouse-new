<?php
namespace App\Models;

use CodeIgniter\Model;

/**
 * Resolver user & role yang kompatibel dengan beberapa skema:
 * - Tabel identitas: auth_identities (type='email_password')
 *   - name   = username
 *   - secret = email
 *   - secret2= password_hash
 * - Role prioritas:
 *   1) users.role (jika ada)
 *   2) auth_groups_users.`group` (jika ada)
 *   3) join auth_groups_users -> auth_groups.name
 *   4) default: 'mobile'
 */
class UserIdentityModel extends Model
{
    protected $table = 'auth_identities';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    public function findByEmailOrUsername(string $identity): ?array
    {
        $row = $this->where('type', 'email_password')
            ->groupStart()
            ->where('secret', $identity)   // email
            ->orWhere('name', $identity)   // username
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->first();
        return $row ?: null;
    }

    public function getRoleByUserId(int $userId): string
    {
        $db = $this->db;
        $role = null;

        // 1) users.role (jika tabel & kolom ada)
        try {
            if ($db->tableExists('users')) {
                $u = $db->table('users')
                    ->select('role')
                    ->where('id', $userId)
                    ->limit(1)
                    ->get()
                    ->getRowArray();
                if ($u && !empty($u['role'])) {
                    $role = $u['role'];
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 2) auth_groups_users.`group` (schema A)
        if ($role === null && $db->tableExists('auth_groups_users')) {
            try {
                $agu = $db->table('auth_groups_users')
                    ->select('`group`') // backtick karena reserved
                    ->where('user_id', $userId)
                    ->orderBy('id', 'DESC')
                    ->limit(1)
                    ->get()
                    ->getRowArray();
                if ($agu && !empty($agu['group'])) {
                    $role = $agu['group'];
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 3) join ke auth_groups.name (schema B)
        if ($role === null && $db->tableExists('auth_groups_users') && $db->tableExists('auth_groups')) {
            try {
                $jg = $db->table('auth_groups_users agu')
                    ->select('g.name as gname')
                    ->join('auth_groups g', 'g.id = agu.group_id', 'left')
                    ->where('agu.user_id', $userId)
                    ->orderBy('agu.id', 'DESC')
                    ->limit(1)
                    ->get()
                    ->getRowArray();
                if ($jg && !empty($jg['gname'])) {
                    $role = $jg['gname'];
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 4) fallback
        if ($role === null || $role === '') {
            $role = 'mobile';
        }

        return strtolower((string) $role);
    }

    /**
     * Build user payload untuk app (id, email, username, role)
     */
    public function buildUserFromIdentity(array $identity): array
    {
        $userId = (int) $identity['user_id'];
        $email = $identity['secret'] ?? null;
        $uname = $identity['name'] ?? null;
        $role = $this->getRoleByUserId($userId);

        return [
            'id' => $userId,
            'email' => $email,
            'username' => $uname,
            'role' => $role,
        ];
    }
}
