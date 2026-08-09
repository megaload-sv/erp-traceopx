<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkOrders extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'uuid' => ['type'=>'CHAR','constraint'=>36],
            'code' => ['type'=>'VARCHAR','constraint'=>30],
            'service_case_id' => ['type'=>'INT','unsigned'=>true],
            'coordination_plan_id' => ['type'=>'INT','unsigned'=>true],
            'customer_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'mission_leader_employee_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'subject' => ['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'scheduled_start_at' => ['type'=>'DATETIME','null'=>true],
            'estimated_end_at' => ['type'=>'DATETIME','null'=>true],
            'location' => ['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'location_reference' => ['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'priority' => ['type'=>'VARCHAR','constraint'=>20,'default'=>'normal'],
            'scope_snapshot' => ['type'=>'TEXT','null'=>true],
            'coordination_notes_snapshot' => ['type'=>'TEXT','null'=>true],
            'status' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'prepared'],
            'issued_at' => ['type'=>'DATETIME','null'=>true],
            'started_at' => ['type'=>'DATETIME','null'=>true],
            'finished_at' => ['type'=>'DATETIME','null'=>true],
            'closed_at' => ['type'=>'DATETIME','null'=>true],
            'created_by_user_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'entry_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
            'modify_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'modify_date' => ['type'=>'DATETIME','null'=>true],
            'delete_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'delete_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->addUniqueKey('coordination_plan_id');
        $this->forge->addKey('service_case_id');
        $this->forge->createTable('work_orders', true);

        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'work_order_id' => ['type'=>'INT','unsigned'=>true],
            'equipment_id' => ['type'=>'INT','unsigned'=>true],
            'equipment_code_snapshot' => ['type'=>'VARCHAR','constraint'=>50],
            'equipment_name_snapshot' => ['type'=>'VARCHAR','constraint'=>190],
            'assignment_status' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'assigned'],
            'entry_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['work_order_id','equipment_id']);
        $this->forge->createTable('work_order_equipment', true);

        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'work_order_id' => ['type'=>'INT','unsigned'=>true],
            'employee_id' => ['type'=>'INT','unsigned'=>true],
            'equipment_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'resource_role_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'allocation_type' => ['type'=>'VARCHAR','constraint'=>30],
            'employee_code_snapshot' => ['type'=>'VARCHAR','constraint'=>30],
            'employee_name_snapshot' => ['type'=>'VARCHAR','constraint'=>190],
            'role_name_snapshot' => ['type'=>'VARCHAR','constraint'=>150,'null'=>true],
            'assignment_status' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'assigned'],
            'entry_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('work_order_id');
        $this->forge->addKey('employee_id');
        $this->forge->createTable('work_order_team', true);
    }

    public function down()
    {
        $this->forge->dropTable('work_order_team', true);
        $this->forge->dropTable('work_order_equipment', true);
        $this->forge->dropTable('work_orders', true);
    }
}
