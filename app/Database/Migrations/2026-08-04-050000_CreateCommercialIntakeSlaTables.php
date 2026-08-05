<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCommercialIntakeSlaTables extends Migration
{
    public function up(): void
    {
        $audit = [
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190],
            'entry_date' => ['type' => 'DATETIME'],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
            'delete_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'delete_date' => ['type' => 'DATETIME', 'null' => true],
        ];

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 30],
            'name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'first_response_minutes' => ['type' => 'INT', 'unsigned' => true],
            'follow_up_minutes' => ['type' => 'INT', 'unsigned' => true],
            'quotation_delivery_minutes' => ['type' => 'INT', 'unsigned' => true],
            'warning_percentage' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 80],
            'supervisor_escalation_percentage' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 120],
            'manager_escalation_percentage' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 150],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $audit);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('commercial_sla_policies', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'code' => ['type' => 'VARCHAR', 'constraint' => 24],
            'channel' => ['type' => 'VARCHAR', 'constraint' => 30],
            'source_detail' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'contact_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'sla_policy_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'assigned_user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 190],
            'description' => ['type' => 'TEXT'],
            'requested_services' => ['type' => 'TEXT', 'null' => true],
            'priority' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'normal'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'new'],
            'sla_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'on_time'],
            'escalation_level' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'received_at' => ['type' => 'DATETIME'],
            'first_response_due_at' => ['type' => 'DATETIME'],
            'first_responded_at' => ['type' => 'DATETIME', 'null' => true],
            'quotation_due_at' => ['type' => 'DATETIME'],
            'next_follow_up_at' => ['type' => 'DATETIME', 'null' => true],
            'waiting_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'closed_at' => ['type' => 'DATETIME', 'null' => true],
        ] + $audit);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey(['status', 'sla_status']);
        $this->forge->addKey('first_response_due_at');
        $this->forge->addKey('quotation_due_at');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('contact_id', 'customer_contacts', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('sla_policy_id', 'commercial_sla_policies', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('assigned_user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('commercial_requests', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'title' => ['type' => 'VARCHAR', 'constraint' => 190],
            'description' => ['type' => 'TEXT', 'null' => true],
            'task_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'related_type' => ['type' => 'VARCHAR', 'constraint' => 60],
            'related_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'assigned_user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'priority' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'normal'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'due_at' => ['type' => 'DATETIME'],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'is_automatic' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'escalation_level' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
        ] + $audit);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['related_type', 'related_id']);
        $this->forge->addKey(['status', 'due_at']);
        $this->forge->addForeignKey('assigned_user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('tasks', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('tasks', true);
        $this->forge->dropTable('commercial_requests', true);
        $this->forge->dropTable('commercial_sla_policies', true);
    }
}
