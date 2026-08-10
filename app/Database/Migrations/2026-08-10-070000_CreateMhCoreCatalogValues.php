<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class CreateMhCoreCatalogValues extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'catalog_code' => ['type' => 'VARCHAR', 'constraint' => 10],
            'code' => ['type' => 'VARCHAR', 'constraint' => 20],
            'name' => ['type' => 'VARCHAR', 'constraint' => 300],
            'parent_code' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
            'display_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['catalog_code', 'parent_code', 'code'], 'uq_mh_catalog_values_scope');
        $this->forge->addKey(['catalog_code', 'status'], false, false, 'idx_mh_catalog_values_catalog_status');
        $this->forge->createTable('mh_catalog_values', true);

        $corePath = APPPATH . 'Database/Catalogs/dte_core_catalogs.json';
        if (! is_file($corePath)) {
            throw new RuntimeException('No se encontró la fuente de catálogos DTE base.');
        }

        $core = json_decode((string) file_get_contents($corePath), true, 512, JSON_THROW_ON_ERROR);
        $cat019 = require APPPATH . 'Database/Catalogs/cat019.php';
        $core['CAT-019'] = $cat019;

        $now = date('Y-m-d H:i:s');
        foreach ($core as $catalogCode => $rows) {
            $batch = [];
            $order = 1;
            foreach ($rows as $row) {
                $code = trim((string) ($row['code'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));
                if ($code === '' || $name === '') {
                    continue;
                }

                $batch[] = [
                    'catalog_code' => (string) $catalogCode,
                    'code' => $code,
                    'name' => $name,
                    'parent_code' => trim((string) ($row['parent_code'] ?? '')),
                    'display_order' => $order++,
                    'status' => 1,
                    'entry_user' => 'migration',
                    'entry_date' => $now,
                ];

                if (count($batch) >= 250) {
                    $this->upsertBatch($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $this->upsertBatch($batch);
            }
        }
    }

    private function upsertBatch(array $rows): void
    {
        foreach ($rows as $row) {
            $existing = $this->db->table('mh_catalog_values')
                ->where('catalog_code', $row['catalog_code'])
                ->where('parent_code', $row['parent_code'])
                ->where('code', $row['code'])
                ->get()->getRowArray();

            if ($existing === null) {
                $this->db->table('mh_catalog_values')->insert($row);
                continue;
            }

            $this->db->table('mh_catalog_values')->where('id', (int) $existing['id'])->update([
                'name' => $row['name'],
                'display_order' => $row['display_order'],
                'status' => 1,
                'modify_user' => 'migration',
                'modify_date' => $row['entry_date'],
            ]);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('mh_catalog_values', true);
    }
}
