<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Config\Database;

class MhFiscalCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');

        $this->upsert($db, 'mh_taxpayer_types', [
            ['code' => '01', 'name' => 'Gran contribuyente'],
            ['code' => '02', 'name' => 'Mediano contribuyente'],
            ['code' => '03', 'name' => 'Otros contribuyentes'],
        ], ['code'], $now);

        $economicActivities = [];
        foreach (range(1, 4) as $part) {
            $file = APPPATH . "Database/Seeds/data/mh_economic_activities_{$part}.php";
            if (is_file($file)) {
                $economicActivities = array_merge($economicActivities, require $file);
            }
        }

        $this->upsert($db, 'mh_economic_activities', $economicActivities, ['code'], $now);
        $this->upsert($db, 'mh_countries', require APPPATH . 'Database/Seeds/data/mh_countries.php', ['code'], $now);
        $this->upsert($db, 'mh_departments', require APPPATH . 'Database/Seeds/data/mh_departments.php', ['code'], $now);
        $this->upsert($db, 'mh_municipalities', require APPPATH . 'Database/Seeds/data/mh_municipalities.php', ['department_code', 'code'], $now);
        $this->upsert($db, 'mh_districts', require APPPATH . 'Database/Seeds/data/mh_districts.php', ['department_code', 'code'], $now);
    }

    private function upsert($db, string $table, array $rows, array $keys, string $now): void
    {
        foreach ($rows as $row) {
            $query = $db->table($table);
            foreach ($keys as $key) {
                $query->where($key, $row[$key]);
            }

            $existing = $query->get()->getRowArray();
            $payload = $row + ['status' => 1];

            if ($existing === null) {
                $db->table($table)->insert($payload + ['entry_user' => 'system', 'entry_date' => $now]);
                continue;
            }

            $db->table($table)->where('id', $existing['id'])->update($payload + ['modify_user' => 'system', 'modify_date' => $now]);
        }
    }
}
