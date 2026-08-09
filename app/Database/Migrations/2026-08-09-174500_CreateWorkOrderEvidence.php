<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkOrderEvidence extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'work_order_id' => ['type' => 'INT', 'unsigned' => true],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'mission_log_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'evidence_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'document'],
            'stage' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'during'],
            'title' => ['type' => 'VARCHAR', 'constraint' => 190],
            'description' => ['type' => 'TEXT', 'null' => true],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'stored_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'relative_path' => ['type' => 'VARCHAR', 'constraint' => 500],
            'mime_type' => ['type' => 'VARCHAR', 'constraint' => 120],
            'extension' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'size_bytes' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'sha256' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'visibility' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'internal'],
            'occurred_at' => ['type' => 'DATETIME'],
            'uploaded_by_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'delete_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'delete_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['work_order_id', 'occurred_at']);
        $this->forge->addKey(['service_case_id', 'occurred_at']);
        $this->forge->addKey('mission_log_id');
        $this->forge->addKey(['evidence_type', 'stage']);
        $this->forge->createTable('work_order_evidence', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('work_order_evidence', true);
    }
}
