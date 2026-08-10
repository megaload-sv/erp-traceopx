<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBillingPaymentEvidence extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('billing_payment_evidence')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'billing_payment_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'billing_case_id' => ['type' => 'INT', 'unsigned' => true],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'evidence_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'payment_receipt'],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'stored_name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'relative_path' => ['type' => 'VARCHAR', 'constraint' => 255],
            'mime_type' => ['type' => 'VARCHAR', 'constraint' => 120],
            'extension' => ['type' => 'VARCHAR', 'constraint' => 12],
            'size_bytes' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'sha256' => ['type' => 'CHAR', 'constraint' => 64],
            'uploaded_by_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'delete_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'delete_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('billing_payment_id');
        $this->forge->addKey('billing_case_id');
        $this->forge->addForeignKey('billing_payment_id', 'billing_payments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('billing_case_id', 'billing_cases', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('service_case_id', 'service_cases', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('billing_payment_evidence', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('billing_payment_evidence', true);
    }
}
