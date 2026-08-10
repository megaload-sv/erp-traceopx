<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMissionLog extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('work_orders', [
            'started_by_user_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'started_at',
            ],
            'start_notes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'started_by_user_id',
            ],
        ]);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'work_order_id' => ['type' => 'INT', 'unsigned' => true],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'log_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'manual'],
            'category' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'operation'],
            'event_code' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 190],
            'description' => ['type' => 'TEXT', 'null' => true],
            'visibility' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'internal'],
            'occurred_at' => ['type' => 'DATETIME'],
            'actor_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'actor_employee_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'metadata_json' => ['type' => 'LONGTEXT', 'null' => true],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['work_order_id', 'occurred_at']);
        $this->forge->addKey(['service_case_id', 'occurred_at']);
        $this->forge->addKey(['category', 'event_code']);
        $this->forge->createTable('mission_logs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('mission_logs', true);
        $this->forge->dropColumn('work_orders', ['started_by_user_id', 'start_notes']);
    }
}
