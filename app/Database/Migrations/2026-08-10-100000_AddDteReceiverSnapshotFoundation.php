<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDteReceiverSnapshotFoundation extends Migration
{
    public function up(): void
    {
        $columns = [];
        foreach ([
            'receiver_country_code' => ['type'=>'VARCHAR','constraint'=>4,'null'=>true,'after'=>'receiver_phone'],
            'receiver_country_name' => ['type'=>'VARCHAR','constraint'=>150,'null'=>true,'after'=>'receiver_country_code'],
            'receiver_person_type' => ['type'=>'TINYINT','unsigned'=>true,'null'=>true,'after'=>'receiver_country_name'],
            'receiver_source_address_id' => ['type'=>'BIGINT','unsigned'=>true,'null'=>true,'after'=>'receiver_address'],
            'receiver_snapshot_at' => ['type'=>'DATETIME','null'=>true,'after'=>'receiver_source_address_id'],
            'receiver_validation_status' => ['type'=>'VARCHAR','constraint'=>20,'default'=>'pending','after'=>'receiver_snapshot_at'],
            'receiver_validation_issues_json' => ['type'=>'TEXT','null'=>true,'after'=>'receiver_validation_status'],
        ] as $name => $definition) {
            if (! $this->db->fieldExists($name, 'dte_documents')) {
                $columns[$name] = $definition;
            }
        }

        if ($columns !== []) {
            $this->forge->addColumn('dte_documents', $columns);
        }
    }

    public function down(): void
    {
        foreach ([
            'receiver_validation_issues_json','receiver_validation_status','receiver_snapshot_at',
            'receiver_source_address_id','receiver_person_type','receiver_country_name','receiver_country_code',
        ] as $column) {
            if ($this->db->fieldExists($column, 'dte_documents')) {
                $this->forge->dropColumn('dte_documents', $column);
            }
        }
    }
}
