<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCommercialProfileToCustomers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('customers', [
            'lifecycle_stage' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'potential',
                'after' => 'customer_type',
            ],
            'relationship_tier' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'standard',
                'after' => 'lifecycle_stage',
            ],
            'assigned_sales_user' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => true,
                'after' => 'relationship_tier',
            ],
            'next_follow_up_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'assigned_sales_user',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('customers', [
            'lifecycle_stage',
            'relationship_tier',
            'assigned_sales_user',
            'next_follow_up_date',
        ]);
    }
}
