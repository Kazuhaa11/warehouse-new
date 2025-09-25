<?php
namespace App\Models;
use CodeIgniter\Model;

class RefreshTokenModel extends Model
{
    protected $table = 'auth_jwt_refresh_tokens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id','refresh_hash','revoked','expires_at'];

    public function revokeAllForUser(int $userId): int
    {
        return $this->where('user_id',$userId)->set('revoked',1)->update();
    }
    public function revokeById(int $id): bool
    {
        return (bool)$this->update($id,['revoked'=>1]);
    }
}
