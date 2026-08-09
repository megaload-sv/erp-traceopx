<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorkOrderCompletionFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('work_orders', [
            'finished_by_user_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'finished_at',
            ],
            'completion_summary' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'finished_by_user_id',
            ],
            'completion_notes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'completion_summary',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('work_orders', [
            'finished_by_user_id',
            'completion_summary',
            'completion_notes',
        ]);
    }
}
