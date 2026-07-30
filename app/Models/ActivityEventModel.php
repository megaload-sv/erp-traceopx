<?php

namespace App\Models;

class ActivityEventModel extends BaseModel
{
    protected $table = 'activity_events';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'entity_type', 'entity_id', 'event_key', 'title', 'description',
        'metadata_json', 'actor_user', 'occurred_at',
        'entry_user', 'modify_user', 'delete_user',
    ];

    public function forEntity(string $entityType, int $entityId): array
    {
        return $this->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('occurred_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}
