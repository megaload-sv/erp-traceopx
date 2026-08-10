<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFinalIssuePreparationToDteDocuments extends Migration
{
    public function up()
    {
        $fields = [
            'final_payload_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'preissue_validated_at'],
            'final_payload_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'final_payload_json'],
            'final_validation_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'final_payload_hash'],
            'final_validation_issues_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'final_validation_status'],
            'final_prepared_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'final_validation_issues_json'],
            'final_prepared_by' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'final_prepared_at'],
            'fiscal_locked_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'final_prepared_by'],
        ];

        foreach ($fields as $name => $definition) {
            if (! $this->db->fieldExists($name, 'dte_documents')) {
                $this->forge->addColumn('dte_documents', [$name => $definition]);
            }
        }
    }

    public function down()
    {
        foreach ([
            'fiscal_locked_at',
            'final_prepared_by',
            'final_prepared_at',
            'final_validation_issues_json',
            'final_validation_status',
            'final_payload_hash',
            'final_payload_json',
        ] as $field) {
            if ($this->db->fieldExists($field, 'dte_documents')) {
                $this->forge->dropColumn('dte_documents', $field);
            }
        }
    }
}
