<?php

namespace App\Models;

class EquipmentModel extends BaseModel
{
    protected $table = 'equipment';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid','code','category_id','name','brand','model','serial_number','plate_number','year',
        'operational_status','maintenance_status','meter_type','current_meter','notes','status',
        'entry_user','modify_user','delete_user',
    ];

    protected $validationRules = [
        'code' => 'required|max_length[40]',
        'name' => 'required|max_length[190]',
        'operational_status' => 'required|in_list[available,reserved,assigned,in_operation,returning,out_of_service]',
        'maintenance_status' => 'required|in_list[ok,preventive_due,preventive,corrective,out_of_service]',
    ];

    public function workspaceList(): array
    {
        return $this->select('equipment.*, equipment_categories.name AS category_name')
            ->join('equipment_categories', 'equipment_categories.id = equipment.category_id', 'left')
            ->orderBy('equipment.code', 'ASC')
            ->findAll();
    }

    public function detail(int $id): ?array
    {
        $row = $this->select('equipment.*, equipment_categories.name AS category_name')
            ->join('equipment_categories', 'equipment_categories.id = equipment.category_id', 'left')
            ->where('equipment.id', $id)
            ->first();

        return is_array($row) ? $row : null;
    }
}
