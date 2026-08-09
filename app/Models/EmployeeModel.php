<?php

namespace App\Models;

class EmployeeModel extends BaseModel
{
    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid','user_id','employee_code','name','email','phone','employment_status','availability_status','notes','status',
        'entry_user','entry_date','modify_user','modify_date','delete_user','delete_date'
    ];

    public function workspaceList(): array
    {
        return $this->select('employees.*')
            ->where('employees.status', 1)
            ->orderBy('employees.name')
            ->findAll();
    }
}
