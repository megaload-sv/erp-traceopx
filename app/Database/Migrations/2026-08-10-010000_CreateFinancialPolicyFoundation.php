<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinancialPolicyFoundation extends Migration
{
    public function up()
    {
        $db = db_connect();

        if (! $db->fieldExists('fiscal_document_type', 'quotations')) {
            $this->forge->addColumn('quotations', [
                'fiscal_document_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                    'after' => 'payment_term_id',
                ],
            ]);
        }

        if (! $db->fieldExists('financial_policy_status', 'service_cases')) {
            $this->forge->addColumn('service_cases', [
                'financial_policy_status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                    'after' => 'billing_status',
                ],
            ]);
        }

        if ($db->tableExists('billing_cases') && ! $db->fieldExists('origin_type', 'billing_cases')) {
            $this->forge->addColumn('billing_cases', [
                'origin_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'quotation',
                    'after' => 'code',
                ],
            ]);
        }

        if ($db->tableExists('billing_cases')) {
            $this->forge->modifyColumn('billing_cases', [
                'work_order_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'quotation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            ]);
        }

        if (! $db->tableExists('service_case_financial_policies')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'service_case_id' => ['type' => 'INT', 'unsigned' => true],
                'quotation_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'origin_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'quotation'],
                'fiscal_document_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'payment_term_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'payment_term_code_snapshot' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                'payment_term_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
                'requires_advance' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'advance_percentage' => ['type' => 'DECIMAL', 'constraint' => '7,4', 'default' => 0],
                'required_before_coordination_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'confirmed_paid_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'coordination_release_rule' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'no_financial_gate'],
                'status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'pending_definition'],
                'evaluated_at' => ['type' => 'DATETIME', 'null' => true],
                'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'entry_date' => ['type' => 'DATETIME', 'null' => true],
                'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'modify_date' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('service_case_id');
            $this->forge->addKey('quotation_id');
            $this->forge->addForeignKey('service_case_id', 'service_cases', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('quotation_id', 'quotations', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('service_case_financial_policies', true);
        }
    }

    public function down()
    {
        $db = db_connect();
        $this->forge->dropTable('service_case_financial_policies', true);
        if ($db->fieldExists('financial_policy_status', 'service_cases')) {
            $this->forge->dropColumn('service_cases', 'financial_policy_status');
        }
        if ($db->fieldExists('fiscal_document_type', 'quotations')) {
            $this->forge->dropColumn('quotations', 'fiscal_document_type');
        }
        if ($db->tableExists('billing_cases') && $db->fieldExists('origin_type', 'billing_cases')) {
            $this->forge->dropColumn('billing_cases', 'origin_type');
        }
    }
}
