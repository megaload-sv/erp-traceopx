<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkOrderAcceptances extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'work_order_id' => ['type' => 'INT', 'unsigned' => true],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'customer_contact_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'result' => ['type' => 'VARCHAR', 'constraint' => 40],
            'receiver_name' => ['type' => 'VARCHAR', 'constraint' => 190],
            'receiver_position' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'receiver_email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'receiver_phone' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'accepted_at' => ['type' => 'DATETIME'],
            'observations' => ['type' => 'TEXT', 'null' => true],
            'signature_original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'signature_stored_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'signature_relative_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'signature_mime_type' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'signature_sha256' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'recorded_by_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['work_order_id', 'accepted_at']);
        $this->forge->addKey(['service_case_id', 'accepted_at']);
        $this->forge->addForeignKey('work_order_id', 'work_orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('service_case_id', 'service_cases', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('work_order_acceptances', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('work_order_acceptances', true);
    }
}
