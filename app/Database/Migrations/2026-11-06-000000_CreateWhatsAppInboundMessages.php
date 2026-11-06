<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWhatsAppInboundMessages extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'message_id' => ['type' => 'VARCHAR', 'constraint' => 255],
            'payload' => ['type' => 'TEXT'],
            'received_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('message_id', true);
        $this->forge->createTable('whatsapp_inbound_messages');
    }

    public function down(): void
    {
        $this->forge->dropTable('whatsapp_inbound_messages', true);
    }
}
