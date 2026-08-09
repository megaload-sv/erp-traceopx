<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCoordinationPlans extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'code' => ['type' => 'VARCHAR', 'constraint' => 30],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'requested_start_at' => ['type' => 'DATETIME', 'null' => true],
            'scheduled_start_at' => ['type' => 'DATETIME', 'null' => true],
            'estimated_end_at' => ['type' => 'DATETIME', 'null' => true],
            'location' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'location_reference' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'priority' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'normal'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'],
            'scope_notes' => ['type' => 'TEXT', 'null' => true],
            'coordination_notes' => ['type' => 'TEXT', 'null' => true],
            'prepared_by_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'prepared_at' => ['type' => 'DATETIME', 'null' => true],
            'approved_by_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
            'delete_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'delete_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('service_case_id');
        $this->forge->createTable('coordination_plans', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'coordination_plan_id' => ['type' => 'INT', 'unsigned' => true],
            'equipment_id' => ['type' => 'INT', 'unsigned' => true],
            'assignment_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'planned'],
            'notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
            'delete_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'delete_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['coordination_plan_id', 'equipment_id']);
        $this->forge->addKey('equipment_id');
        $this->forge->createTable('coordination_plan_equipment', true);
    }

    public function down()
    {
        $this->forge->dropTable('coordination_plan_equipment', true);
        $this->forge->dropTable('coordination_plans', true);
    }
}
