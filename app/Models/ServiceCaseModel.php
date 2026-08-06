<?php

namespace App\Models;

class ServiceCaseModel extends BaseModel
{
    protected $table = 'service_cases';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid', 'code', 'customer_id', 'commercial_request_id', 'accepted_quotation_id',
        'responsible_user_id', 'current_stage', 'operational_status', 'billing_status',
        'collection_status', 'health_score', 'next_action_code', 'next_action_label',
        'next_action_due_at', 'opened_at', 'operationally_closed_at',
        'financially_closed_at', 'archived_at', 'entry_user', 'modify_user', 'delete_user',
    ];
}
