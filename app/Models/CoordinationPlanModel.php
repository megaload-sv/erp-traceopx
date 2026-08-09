<?php

namespace App\Models;

class CoordinationPlanModel extends BaseModel
{
    protected $table = 'coordination_plans';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid','code','service_case_id','requested_start_at','scheduled_start_at','estimated_end_at',
        'location','location_reference','priority','status','scope_notes','coordination_notes',
        'prepared_by_user_id','prepared_at','approved_by_user_id','approved_at',
        'entry_user','modify_user','delete_user',
    ];

    public function detail(int $id): ?array
    {
        return $this->select('coordination_plans.*, service_cases.code AS service_case_code, customers.business_name')
            ->join('service_cases', 'service_cases.id = coordination_plans.service_case_id', 'left')
            ->join('customers', 'customers.id = service_cases.customer_id', 'left')
            ->find($id);
    }

    public function workspaceList(): array
    {
        return $this->select('coordination_plans.*, service_cases.code AS service_case_code, customers.business_name')
            ->join('service_cases', 'service_cases.id = coordination_plans.service_case_id', 'left')
            ->join('customers', 'customers.id = service_cases.customer_id', 'left')
            ->orderBy('coordination_plans.entry_date', 'DESC')
            ->findAll();
    }
}
