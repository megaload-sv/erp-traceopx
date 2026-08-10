<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDtePreIssueSnapshotFields extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('payment_snapshot_json', 'dte_documents')) {
            $this->forge->addColumn('dte_documents', [
                'payment_snapshot_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'tax_summary_json'],
                'payment_snapshot_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'payment_snapshot_json'],
                'preissue_validation_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'payment_snapshot_at'],
                'preissue_validation_issues_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'preissue_validation_status'],
                'preissue_validated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'preissue_validation_issues_json'],
            ]);
        }
    }

    public function down(): void
    {
        foreach (['preissue_validated_at','preissue_validation_issues_json','preissue_validation_status','payment_snapshot_at','payment_snapshot_json'] as $field) {
            if ($this->db->fieldExists($field, 'dte_documents')) {
                $this->forge->dropColumn('dte_documents', $field);
            }
        }
    }
}
