<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBillingPaymentsFoundation extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('billing_payments')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'billing_case_id' => ['type' => 'INT', 'unsigned' => true],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'dte_document_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'payment_schedule_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'payment_method' => ['type' => 'VARCHAR', 'constraint' => 120],
            'mh_payment_code' => ['type' => 'CHAR', 'constraint' => 2, 'null' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'electronic_payment_number' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'payment_date' => ['type' => 'DATETIME'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'confirmed'],
            'notes' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'confirmed_by' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'confirmed_at' => ['type' => 'DATETIME', 'null' => true],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
            'void_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'void_date' => ['type' => 'DATETIME', 'null' => true],
            'void_reason' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid', 'uq_billing_payments_uuid');
        $this->forge->addKey(['billing_case_id', 'status'], false, false, 'idx_billing_payments_case_status');
        $this->forge->addKey(['service_case_id', 'status'], false, false, 'idx_billing_payments_service_status');
        $this->forge->addKey('payment_schedule_id');
        $this->forge->addForeignKey('billing_case_id', 'billing_cases', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('service_case_id', 'service_cases', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('dte_document_id', 'dte_documents', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('payment_schedule_id', 'billing_payment_schedule', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('billing_payments', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('billing_payments', true);
    }
}
