<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'name',
        'email',
        'password_hash',
        'is_active',
        'last_login_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function findActiveByEmail(string $email): ?array
    {
        $user = $this->where('email', mb_strtolower(trim($email)))
            ->where('is_active', 1)
            ->first();

        return is_array($user) ? $user : null;
    }
}
