<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRoleToCustomerContacts extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('contact_role', 'customer_contacts')) {
            $this->forge->addColumn('customer_contacts', [
                'contact_role' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'commercial',
                    'after' => 'position',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('contact_role', 'customer_contacts')) {
            $this->forge->dropColumn('customer_contacts', 'contact_role');
        }
    }
}
