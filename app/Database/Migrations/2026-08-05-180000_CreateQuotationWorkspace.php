<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuotationWorkspace extends Migration
{
    public function up(): void
    {
        $this->createCommercialUnits();
        $this->createPaymentTerms();
        $this->createCommercialItems();
        $this->createQuotations();
        $this->createQuotationRecipients();
        $this->createQuotationItems();
    }

    public function down(): void
    {
        $this->forge->dropTable('quotation_items', true);
        $this->forge->dropTable('quotation_recipients', true);
        $this->forge->dropTable('quotations', true);
        $this->forge->dropTable('commercial_items', true);
        $this->forge->dropTable('payment_terms', true);
        $this->forge->dropTable('commercial_units', true);
    }

    private function auditFields(): array
    {
        return [
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
            'delete_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'delete_date' => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    private function createCommercialUnits(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 30],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'symbol' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ], $this->auditFields()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('commercial_units', true);
    }

    private function createPaymentTerms(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'name' => ['type' => 'VARCHAR', 'constraint' => 190],
            'description' => ['type' => 'TEXT', 'null' => true],
            'term_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'simple'],
            'requires_advance' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'minimum_advance_percentage' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'coordination_release_rule' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'no_block'],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ], $this->auditFields()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('payment_terms', true);
    }

    private function createCommercialItems(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'item_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'service'],
            'name' => ['type' => 'VARCHAR', 'constraint' => 190],
            'long_description' => ['type' => 'TEXT', 'null' => true],
            'default_unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'suggested_price' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'allows_price_override' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ], $this->auditFields()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->addForeignKey('default_unit_id', 'commercial_units', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('commercial_items', true);
    }

    private function createQuotations(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'commercial_request_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'customer_id' => ['type' => 'INT', 'unsigned' => true],
            'assigned_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'payment_term_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'origin_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'direct'],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 190],
            'quotation_date' => ['type' => 'DATE'],
            'valid_until' => ['type' => 'DATE'],
            'validity_days' => ['type' => 'INT', 'unsigned' => true, 'default' => 30],
            'status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'draft'],
            'customer_notes' => ['type' => 'TEXT', 'null' => true],
            'internal_notes' => ['type' => 'TEXT', 'null' => true],
            'terms_and_conditions' => ['type' => 'LONGTEXT', 'null' => true],
            'show_tax_breakdown' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'subtotal' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'discount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'adjustment' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'tax_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'agent_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'agent_email_snapshot' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'agent_phone_snapshot' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
        ], $this->auditFields()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey(['customer_id', 'status']);
        $this->forge->addForeignKey('commercial_request_id', 'commercial_requests', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('assigned_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('payment_term_id', 'payment_terms', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('quotations', true);
    }

    private function createQuotationRecipients(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'quotation_id' => ['type' => 'INT', 'unsigned' => true],
            'customer_contact_id' => ['type' => 'INT', 'unsigned' => true],
            'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ], $this->auditFields()));
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['quotation_id', 'customer_contact_id']);
        $this->forge->addForeignKey('quotation_id', 'quotations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('customer_contact_id', 'customer_contacts', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('quotation_recipients', true);
    }

    private function createQuotationItems(): void
    {
        $this->forge->addField(array_merge([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'quotation_id' => ['type' => 'INT', 'unsigned' => true],
            'commercial_item_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'source_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'manual'],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255],
            'long_description' => ['type' => 'TEXT', 'null' => true],
            'unit_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '14,3', 'default' => 1],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'line_total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'sort_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ], $this->auditFields()));
        $this->forge->addKey('id', true);
        $this->forge->addKey(['quotation_id', 'sort_order']);
        $this->forge->addForeignKey('quotation_id', 'quotations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('commercial_item_id', 'commercial_items', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'commercial_units', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('quotation_items', true);
    }
}
