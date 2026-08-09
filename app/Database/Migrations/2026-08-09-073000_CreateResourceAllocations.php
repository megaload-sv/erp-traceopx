<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateResourceAllocations extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'coordination_plan_id' => ['type'=>'INT','unsigned'=>true],
            'equipment_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'resource_role_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'employee_id' => ['type'=>'INT','unsigned'=>true],
            'allocation_type' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'operational_role'],
            'allocation_status' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'reserved'],
            'starts_at' => ['type'=>'DATETIME','null'=>true],
            'ends_at' => ['type'=>'DATETIME','null'=>true],
            'assigned_by_user_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'assigned_at' => ['type'=>'DATETIME','null'=>true],
            'released_at' => ['type'=>'DATETIME','null'=>true],
            'notes' => ['type'=>'TEXT','null'=>true],
            'status' => ['type'=>'TINYINT','constraint'=>1,'default'=>1],
            'entry_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
            'modify_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'modify_date' => ['type'=>'DATETIME','null'=>true],
            'delete_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'delete_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('coordination_plan_id');
        $this->forge->addKey('equipment_id');
        $this->forge->addKey('resource_role_id');
        $this->forge->addKey('employee_id');
        $this->forge->addKey(['employee_id','starts_at','ends_at']);
        $this->forge->createTable('coordination_resource_allocations', true);
    }

    public function down()
    {
        $this->forge->dropTable('coordination_resource_allocations', true);
    }
}
