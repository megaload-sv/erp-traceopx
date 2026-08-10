<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkOrderIncidentTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'work_order_id' => ['type' => 'INT', 'unsigned' => true],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'incident_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'severity' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'medium'],
            'title' => ['type' => 'VARCHAR', 'constraint' => 180],
            'description' => ['type' => 'TEXT'],
            'equipment_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'work_order_team_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'open'],
            'requires_resource_change' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'occurred_at' => ['type' => 'DATETIME'],
            'resolved_at' => ['type' => 'DATETIME', 'null' => true],
            'resolution_notes' => ['type' => 'TEXT', 'null' => true],
            'reported_by' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('work_order_id');
        $this->forge->addKey('service_case_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('work_order_id', 'work_orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('service_case_id', 'service_cases', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('work_order_incidents', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'incident_id' => ['type' => 'INT', 'unsigned' => true],
            'work_order_id' => ['type' => 'INT', 'unsigned' => true],
            'change_type' => ['type' => 'VARCHAR', 'constraint' => 30],
            'work_order_team_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'outgoing_employee_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'incoming_employee_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'equipment_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resource_role_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'proposed'],
            'reason' => ['type' => 'TEXT'],
            'proposed_by' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'proposed_at' => ['type' => 'DATETIME', 'null' => true],
            'approved_by' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'executed_at' => ['type' => 'DATETIME', 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('incident_id');
        $this->forge->addKey('work_order_id');
        $this->forge->addForeignKey('incident_id', 'work_order_incidents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('work_order_id', 'work_orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('work_order_resource_changes', true);
    }

    public function down()
    {
        $this->forge->dropTable('work_order_resource_changes', true);
        $this->forge->dropTable('work_order_incidents', true);
    }
}
