<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerManagementTables extends Migration
{
    public function up(): void
    {
        $auditFields = [
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
            'code' => ['type' => 'VARCHAR', 'constraint' => 20],
            'customer_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'company'],
            'business_name' => ['type' => 'VARCHAR', 'constraint' => 190],
            'trade_name' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'tax_id' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'registration_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'website' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $auditFields);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('business_name');
        $this->forge->addKey('tax_id');
        $this->forge->createTable('customers', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 190],
            'position' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $auditFields);
        $this->forge->addKey('id', true);
        $this->forge->addKey('customer_id');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('customer_contacts', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'address_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'fiscal'],
            'address_line' => ['type' => 'VARCHAR', 'constraint' => 255],
            'municipality' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'department' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'country' => ['type' => 'VARCHAR', 'constraint' => 120, 'default' => 'El Salvador'],
            'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $auditFields);
        $this->forge->addKey('id', true);
        $this->forge->addKey('customer_id');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('customer_addresses', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 80],
            'entity_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'event_key' => ['type' => 'VARCHAR', 'constraint' => 100],
            'title' => ['type' => 'VARCHAR', 'constraint' => 190],
            'description' => ['type' => 'TEXT', 'null' => true],
            'metadata_json' => ['type' => 'TEXT', 'null' => true],
            'actor_user' => ['type' => 'VARCHAR', 'constraint' => 190],
            'occurred_at' => ['type' => 'DATETIME'],
        ] + $auditFields);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->addKey('event_key');
        $this->forge->addKey('occurred_at');
        $this->forge->createTable('activity_events', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('activity_events', true);
        $this->forge->dropTable('customer_addresses', true);
        $this->forge->dropTable('customer_contacts', true);
        $this->forge->dropTable('customers', true);
    }
}
