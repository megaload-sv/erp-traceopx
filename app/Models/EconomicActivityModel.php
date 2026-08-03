<?php

namespace App\Models;

class EconomicActivityModel extends BaseModel
{
    protected $table = 'economic_activities';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'code', 'name', 'source_reference', 'source_updated_at', 'status',
        'entry_user', 'modify_user', 'delete_user',
    ];

    public function activeOptions(): array
    {
        return $this->where('status', 1)->orderBy('name', 'ASC')->findAll();
    }
}
