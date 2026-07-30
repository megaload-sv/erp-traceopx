<?php

namespace App\Models;

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name',
        'email',
        'password_hash',
        'is_active',
        'last_login_at',
        'entry_user',
        'modify_user',
        'delete_user',
    ];

    public function findActiveByEmail(string $email): ?array
    {
        $user = $this->where('email', mb_strtolower(trim($email)))
            ->where('is_active', 1)
            ->first();

        return is_array($user) ? $user : null;
    }
}
