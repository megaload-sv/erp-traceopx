<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLabelToCustomerAddresses extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('customer_addresses', [
            'label' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'address_type',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('customer_addresses', 'label');
    }
}
