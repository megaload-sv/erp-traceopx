<?php

namespace App\Services;

use App\Models\ActivityEventModel;

class ActivityService
{
    public function record(
        string $entityType,
        int $entityId,
        string $eventKey,
        string $title,
        ?string $description = null,
        array $metadata = []
    ): bool {
        $actor = (string) (session()->get('auth_user_email') ?? session()->get('auth_user_name') ?? 'system');

        return (new ActivityEventModel())->insert([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'event_key' => $eventKey,
            'title' => $title,
            'description' => $description,
            'metadata_json' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE),
            'actor_user' => $actor,
            'occurred_at' => date('Y-m-d H:i:s'),
        ]) !== false;
    }
}
