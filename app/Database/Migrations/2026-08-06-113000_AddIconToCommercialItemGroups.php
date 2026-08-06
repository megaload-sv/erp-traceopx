<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIconToCommercialItemGroups extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('commercial_item_groups')) {
            return;
        }

        if (! $this->db->fieldExists('icon', 'commercial_item_groups')) {
            $this->forge->addColumn('commercial_item_groups', [
                'icon' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => true,
                    'after' => 'description',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('commercial_item_groups')
            && $this->db->fieldExists('icon', 'commercial_item_groups')) {
            $this->forge->dropColumn('commercial_item_groups', 'icon');
        }
    }
}
