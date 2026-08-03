<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Config\Database;

class CustomerFiscalCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $db = Database::connect();

        $taxpayerTypes = [
            ['code' => 'GRAN_CONTRIBUYENTE', 'name' => 'Gran contribuyente'],
            ['code' => 'MEDIANO_CONTRIBUYENTE', 'name' => 'Mediano contribuyente'],
            ['code' => 'OTROS_CONTRIBUYENTES', 'name' => 'Otros contribuyentes'],
        ];

        foreach ($taxpayerTypes as $type) {
            $existing = $db->table('customer_taxpayer_types')->where('code', $type['code'])->get()->getRowArray();
            $payload = $type + ['status' => 1];

            if ($existing === null) {
                $db->table('customer_taxpayer_types')->insert($payload + [
                    'entry_user' => 'system',
                    'entry_date' => $now,
                ]);
                continue;
            }

            $db->table('customer_taxpayer_types')->where('id', $existing['id'])->update($payload + [
                'modify_user' => 'system',
                'modify_date' => $now,
            ]);
        }

        $activitiesFile = APPPATH . 'Database/Seeds/data/economic_activities.php';
        $activities = is_file($activitiesFile) ? require $activitiesFile : [];

        foreach ($activities as $activity) {
            if (! isset($activity['code'], $activity['name'])) {
                continue;
            }

            $existing = $db->table('economic_activities')->where('code', $activity['code'])->get()->getRowArray();
            $payload = [
                'code' => (string) $activity['code'],
                'name' => (string) $activity['name'],
                'source_reference' => $activity['source_reference'] ?? 'Ministerio de Hacienda de El Salvador',
                'source_updated_at' => $activity['source_updated_at'] ?? null,
                'status' => 1,
            ];

            if ($existing === null) {
                $db->table('economic_activities')->insert($payload + [
                    'entry_user' => 'system',
                    'entry_date' => $now,
                ]);
                continue;
            }

            $db->table('economic_activities')->where('id', $existing['id'])->update($payload + [
                'modify_user' => 'system',
                'modify_date' => $now,
            ]);
        }
    }
}
