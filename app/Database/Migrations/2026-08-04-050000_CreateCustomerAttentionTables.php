<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerAttentionTables extends Migration
{
    public function up(): void
    {
        $audit = [
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190],
            'entry_date' => ['type' => 'DATETIME'],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
            'delete_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'delete_date' => ['type' => 'DATETIME', 'null' => true],
        ];

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'code' => ['type' => 'VARCHAR', 'constraint' => 24],
            'primary_channel' => ['type' => 'VARCHAR', 'constraint' => 30],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'contact_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'assigned_user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 190],
            'summary' => ['type' => 'TEXT', 'null' => true],
            'attention_status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'new'],
            'priority' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'normal'],
            'started_at' => ['type' => 'DATETIME'],
            'first_response_due_at' => ['type' => 'DATETIME', 'null' => true],
            'first_responded_at' => ['type' => 'DATETIME', 'null' => true],
            'next_follow_up_at' => ['type' => 'DATETIME', 'null' => true],
            'qualified_at' => ['type' => 'DATETIME', 'null' => true],
            'closed_at' => ['type' => 'DATETIME', 'null' => true],
            'commercial_request_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ] + $audit);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey(['attention_status', 'assigned_user_id']);
        $this->forge->addKey('first_response_due_at');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('contact_id', 'customer_contacts', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('assigned_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('commercial_request_id', 'commercial_requests', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('customer_conversations', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'conversation_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'channel' => ['type' => 'VARCHAR', 'constraint' => 30],
            'direction' => ['type' => 'VARCHAR', 'constraint' => 20],
            'interaction_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'message'],
            'body' => ['type' => 'TEXT'],
            'actor_user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'occurred_at' => ['type' => 'DATETIME'],
        ] + $audit);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['conversation_id', 'occurred_at']);
        $this->forge->addForeignKey('conversation_id', 'customer_conversations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('actor_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('customer_conversation_interactions', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('customer_conversation_interactions', true);
        $this->forge->dropTable('customer_conversations', true);
    }
}
