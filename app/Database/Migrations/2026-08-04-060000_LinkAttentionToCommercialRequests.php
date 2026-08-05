<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class LinkAttentionToCommercialRequests extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('customer_conversations', [
            'sla_policy_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'assigned_user_id',
            ],
        ]);

        $this->forge->addColumn('commercial_requests', [
            'source_conversation_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'contact_id',
            ],
        ]);

        $this->db->query('ALTER TABLE customer_conversations ADD CONSTRAINT fk_attention_sla_policy FOREIGN KEY (sla_policy_id) REFERENCES commercial_sla_policies(id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->db->query('ALTER TABLE commercial_requests ADD CONSTRAINT fk_request_source_conversation FOREIGN KEY (source_conversation_id) REFERENCES customer_conversations(id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->db->query('CREATE UNIQUE INDEX uq_commercial_requests_source_conversation ON commercial_requests (source_conversation_id)');
    }

    public function down(): void
    {
        $this->db->query('DROP INDEX uq_commercial_requests_source_conversation ON commercial_requests');
        $this->db->query('ALTER TABLE commercial_requests DROP FOREIGN KEY fk_request_source_conversation');
        $this->db->query('ALTER TABLE customer_conversations DROP FOREIGN KEY fk_attention_sla_policy');
        $this->forge->dropColumn('commercial_requests', 'source_conversation_id');
        $this->forge->dropColumn('customer_conversations', 'sla_policy_id');
    }
}
