<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBillingPreparationTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'code' => ['type' => 'VARCHAR', 'constraint' => 30],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'work_order_id' => ['type' => 'INT', 'unsigned' => true],
            'quotation_id' => ['type' => 'INT', 'unsigned' => true],
            'customer_id' => ['type' => 'INT', 'unsigned' => true],
            'payment_term_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'document_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'],
            'currency_code' => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'USD'],
            'quotation_total_snapshot' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'invoiceable_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'paid_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'balance_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'payment_term_code_snapshot' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'payment_term_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'prepared_by_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'prepared_at' => ['type' => 'DATETIME', 'null' => true],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
            'delete_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'delete_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('code');
        $this->forge->addUniqueKey('service_case_id');
        $this->forge->addKey('work_order_id');
        $this->forge->addKey('customer_id');
        $this->forge->addForeignKey('service_case_id', 'service_cases', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('work_order_id', 'work_orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('quotation_id', 'quotations', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('billing_cases', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'billing_case_id' => ['type' => 'INT', 'unsigned' => true],
            'sequence' => ['type' => 'SMALLINT', 'unsigned' => true],
            'concept' => ['type' => 'VARCHAR', 'constraint' => 120],
            'installment_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'scheduled'],
            'percentage' => ['type' => 'DECIMAL', 'constraint' => '7,4', 'default' => 0],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'trigger_event' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'due_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['billing_case_id', 'sequence']);
        $this->forge->addForeignKey('billing_case_id', 'billing_cases', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('billing_payment_schedule', true);
    }

    public function down()
    {
        $this->forge->dropTable('billing_payment_schedule', true);
        $this->forge->dropTable('billing_cases', true);
    }
}
