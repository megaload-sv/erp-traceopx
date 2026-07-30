<?php

namespace App\Models;

use CodeIgniter\Model;
use Throwable;

abstract class BaseModel extends Model
{
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $createdField = 'entry_date';
    protected $updatedField = 'modify_date';
    protected $deletedField = 'delete_date';
    protected $dateFormat = 'datetime';

    protected $beforeInsert = ['applyInsertAudit'];
    protected $beforeUpdate = ['applyUpdateAudit'];
    protected $beforeDelete = ['applyDeleteAudit'];

    protected function applyInsertAudit(array $data): array
    {
        $data['data']['entry_user'] ??= $this->auditActor();

        return $data;
    }

    protected function applyUpdateAudit(array $data): array
    {
        $data['data']['modify_user'] ??= $this->auditActor();

        return $data;
    }

    protected function applyDeleteAudit(array $data): array
    {
        if (($data['purge'] ?? false) === true || empty($data['id'])) {
            return $data;
        }

        $ids = is_array($data['id']) ? $data['id'] : [$data['id']];

        $this->builder()
            ->whereIn($this->primaryKey, $ids)
            ->update([
                'delete_user' => $this->auditActor(),
                'modify_user' => $this->auditActor(),
                'modify_date' => date('Y-m-d H:i:s'),
            ]);

        return $data;
    }

    protected function auditActor(): string
    {
        try {
            $session = session();

            return (string) (
                $session->get('auth_user_email')
                ?? $session->get('auth_user_name')
                ?? 'system'
            );
        } catch (Throwable) {
            return 'system';
        }
    }
}
