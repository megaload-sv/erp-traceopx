<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CommercialSlaSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['code' => 'WHATSAPP', 'name' => 'WhatsApp', 'first_response_minutes' => 15, 'follow_up_minutes' => 120, 'quotation_delivery_minutes' => 1440],
            ['code' => 'EMAIL', 'name' => 'Correo electrónico', 'first_response_minutes' => 60, 'follow_up_minutes' => 240, 'quotation_delivery_minutes' => 2880],
            ['code' => 'MANUAL', 'name' => 'Ingreso manual', 'first_response_minutes' => 30, 'follow_up_minutes' => 240, 'quotation_delivery_minutes' => 1440],
        ];

        foreach ($rows as $row) {
            $existing = $this->db->table('commercial_sla_policies')->where('code', $row['code'])->get()->getRowArray();
            $payload = $row + [
                'warning_percentage' => 80,
                'supervisor_escalation_percentage' => 120,
                'manager_escalation_percentage' => 150,
                'status' => 1,
            ];

            if ($existing === null) {
                $this->db->table('commercial_sla_policies')->insert($payload + ['entry_user' => 'system', 'entry_date' => $now]);
                continue;
            }

            $this->db->table('commercial_sla_policies')->where('id', $existing['id'])->update($payload + ['modify_user' => 'system', 'modify_date' => $now]);
        }
    }
}
