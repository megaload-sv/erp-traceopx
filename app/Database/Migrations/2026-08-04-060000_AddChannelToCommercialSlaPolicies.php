<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddChannelToCommercialSlaPolicies extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('channel', 'commercial_sla_policies')) {
            $this->forge->addColumn('commercial_sla_policies', [
                'channel' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => true,
                    'after' => 'name',
                ],
            ]);
        }

        $channelByCode = [
            'WHATSAPP' => 'whatsapp',
            'EMAIL' => 'email',
            'MANUAL' => 'manual',
        ];

        foreach ($channelByCode as $code => $channel) {
            $this->db->table('commercial_sla_policies')
                ->where('code', $code)
                ->update(['channel' => $channel]);
        }

        $this->db->query(
            'CREATE INDEX idx_commercial_sla_policies_channel_status '
            . 'ON commercial_sla_policies (channel, status)'
        );
    }

    public function down(): void
    {
        if ($this->db->fieldExists('channel', 'commercial_sla_policies')) {
            $this->db->query('DROP INDEX idx_commercial_sla_policies_channel_status ON commercial_sla_policies');
            $this->forge->dropColumn('commercial_sla_policies', 'channel');
        }
    }
}
