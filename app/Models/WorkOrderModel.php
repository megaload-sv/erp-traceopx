<?php

namespace App\Models;

class WorkOrderModel extends BaseModel
{
    protected $table = 'work_orders';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid','code','service_case_id','coordination_plan_id','customer_id','mission_leader_employee_id',
        'subject','scheduled_start_at','estimated_end_at','location','location_reference','priority',
        'scope_snapshot','coordination_notes_snapshot','status','issued_at','issued_by_user_id',
        'issued_to_employee_id','issuance_notes','started_at','started_by_user_id','start_notes',
        'finished_at','closed_at','created_by_user_id','entry_user','modify_user','delete_user',
    ];

    public function detail(int $id): ?array
    {
        return $this->select('work_orders.*, customers.business_name, service_cases.code AS service_case_code, coordination_plans.code AS coordination_code, mission_leader.name AS mission_leader_name, mission_leader.employee_code AS mission_leader_code')
            ->join('customers', 'customers.id = work_orders.customer_id', 'left')
            ->join('service_cases', 'service_cases.id = work_orders.service_case_id', 'left')
            ->join('coordination_plans', 'coordination_plans.id = work_orders.coordination_plan_id', 'left')
            ->join('employees mission_leader', 'mission_leader.id = work_orders.mission_leader_employee_id', 'left')
            ->find($id);
    }

    public function workspaceList(): array
    {
        return $this->select('work_orders.*, customers.business_name, service_cases.code AS service_case_code, coordination_plans.code AS coordination_code')
            ->join('customers', 'customers.id = work_orders.customer_id', 'left')
            ->join('service_cases', 'service_cases.id = work_orders.service_case_id', 'left')
            ->join('coordination_plans', 'coordination_plans.id = work_orders.coordination_plan_id', 'left')
            ->where('work_orders.delete_date', null)
            ->orderBy('work_orders.id', 'DESC')
            ->findAll();
    }
}
