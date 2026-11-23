<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceCaseProcessEngine extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'code' => ['type' => 'VARCHAR', 'constraint' => 30],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'commercial_request_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'accepted_quotation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'responsible_user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'current_stage' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'commercial_acceptance'],
            'operational_status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'not_started'],
            'billing_status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'pending_definition'],
            'collection_status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'not_applicable'],
            'health_score' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 100],
            'next_action_code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'next_action_label' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'next_action_due_at' => ['type' => 'DATETIME', 'null' => true],
            'opened_at' => ['type' => 'DATETIME'],
            'operationally_closed_at' => ['type' => 'DATETIME', 'null' => true],
            'financially_closed_at' => ['type' => 'DATETIME', 'null' => true],
            'archived_at' => ['type' => 'DATETIME', 'null' => true],
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
        $this->forge->addUniqueKey('accepted_quotation_id');
        $this->forge->addKey(['customer_id', 'current_stage']);
        $this->forge->createTable('service_cases', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'milestone_code' => ['type' => 'VARCHAR', 'constraint' => 80],
            'milestone_label' => ['type' => 'VARCHAR', 'constraint' => 190],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
            'required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sequence' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_by' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'evidence_entity_type' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'evidence_entity_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
            'delete_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'delete_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['service_case_id', 'milestone_code']);
        $this->forge->createTable('service_case_milestones', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'event_code' => ['type' => 'VARCHAR', 'constraint' => 100],
            'title' => ['type' => 'VARCHAR', 'constraint' => 190],
            'description' => ['type' => 'TEXT', 'null' => true],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'entity_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'metadata_json' => ['type' => 'LONGTEXT', 'null' => true],
            'occurred_at' => ['type' => 'DATETIME'],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['service_case_id', 'occurred_at']);
        $this->forge->createTable('service_case_events', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'entity_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'exception_type' => ['type' => 'VARCHAR', 'constraint' => 100],
            'severity' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'medium'],
            'title' => ['type' => 'VARCHAR', 'constraint' => 190],
            'description' => ['type' => 'TEXT', 'null' => true],
            'detected_at' => ['type' => 'DATETIME'],
            'due_at' => ['type' => 'DATETIME', 'null' => true],
            'assigned_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'escalated_to_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'open'],
            'resolved_at' => ['type' => 'DATETIME', 'null' => true],
            'resolution' => ['type' => 'TEXT', 'null' => true],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
            'delete_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'delete_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['service_case_id', 'status', 'severity']);
        $this->forge->createTable('process_exceptions', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'from_stage' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'to_stage' => ['type' => 'VARCHAR', 'constraint' => 50],
            'reason' => ['type' => 'TEXT', 'null' => true],
            'changed_by' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'changed_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['service_case_id', 'changed_at']);
        $this->forge->createTable('service_case_stage_history', true);

        // A previous attempt may have created the tables before adding foreign keys.
        $columns = ['customer_id' => false, 'commercial_request_id' => true, 'responsible_user_id' => true];
        foreach ($this->db->getFieldData('service_cases') as $field) {
            if (array_key_exists($field->name, $columns) && strtolower($field->type) !== 'bigint') {
                $this->forge->modifyColumn('service_cases', [
                    $field->name => ['type' => 'BIGINT', 'unsigned' => true, 'null' => $columns[$field->name]],
                ]);
            }
        }

        $this->addForeignKeyIfMissing('service_cases', 'fk_service_cases_customer', 'ALTER TABLE `service_cases` ADD CONSTRAINT `fk_service_cases_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE');
        $this->addForeignKeyIfMissing('service_cases', 'fk_service_cases_quotation', 'ALTER TABLE `service_cases` ADD CONSTRAINT `fk_service_cases_quotation` FOREIGN KEY (`accepted_quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->addForeignKeyIfMissing('service_case_milestones', 'fk_case_milestones_case', 'ALTER TABLE `service_case_milestones` ADD CONSTRAINT `fk_case_milestones_case` FOREIGN KEY (`service_case_id`) REFERENCES `service_cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->addForeignKeyIfMissing('service_case_events', 'fk_case_events_case', 'ALTER TABLE `service_case_events` ADD CONSTRAINT `fk_case_events_case` FOREIGN KEY (`service_case_id`) REFERENCES `service_cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->addForeignKeyIfMissing('process_exceptions', 'fk_process_exceptions_case', 'ALTER TABLE `process_exceptions` ADD CONSTRAINT `fk_process_exceptions_case` FOREIGN KEY (`service_case_id`) REFERENCES `service_cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->addForeignKeyIfMissing('service_case_stage_history', 'fk_case_stage_history_case', 'ALTER TABLE `service_case_stage_history` ADD CONSTRAINT `fk_case_stage_history_case` FOREIGN KEY (`service_case_id`) REFERENCES `service_cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    private function addForeignKeyIfMissing(string $table, string $name, string $sql): void
    {
        foreach ($this->db->getForeignKeyData($table) as $foreignKey) {
            if ($foreignKey->constraint_name === $name) {
                return;
            }
        }

        $this->db->query($sql);
    }

    public function down(): void
    {
        $this->forge->dropTable('service_case_stage_history', true);
        $this->forge->dropTable('process_exceptions', true);
        $this->forge->dropTable('service_case_events', true);
        $this->forge->dropTable('service_case_milestones', true);
        $this->forge->dropTable('service_cases', true);
    }
}
