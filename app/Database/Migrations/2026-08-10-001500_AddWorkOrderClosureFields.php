<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorkOrderClosureFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('work_orders', [
            'closed_by_user_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'closed_at',
            ],
            'closure_notes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'closed_by_user_id',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('work_orders', ['closed_by_user_id', 'closure_notes']);
    }
}
