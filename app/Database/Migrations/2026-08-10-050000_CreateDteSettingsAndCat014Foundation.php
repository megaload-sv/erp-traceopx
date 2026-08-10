<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDteSettingsAndCat014Foundation extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'group_code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'setting_key' => ['type' => 'VARCHAR', 'constraint' => 120],
            'setting_value' => ['type' => 'TEXT', 'null' => true],
            'value_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'string'],
            'description' => ['type' => 'VARCHAR', 'constraint' => 250, 'null' => true],
            'status' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['group_code', 'setting_key'], 'uq_dte_settings_group_key');
        $this->forge->createTable('dte_settings', true);

        $now = date('Y-m-d H:i:s');
        $settings = [
            [
                'group_code' => 'control_number',
                'setting_key' => 'generation_strategy',
                'setting_value' => 'pending_definition',
                'value_type' => 'string',
                'description' => 'Estrategia para construir numeroControl. La regla definitiva se configurará cuando sea aprobada.',
            ],
            [
                'group_code' => 'identification',
                'setting_key' => 'generation_code_strategy',
                'setting_value' => 'uuid_v4_uppercase',
                'value_type' => 'string',
                'description' => 'codigoGeneracion utiliza UUID v4 y se persiste en mayúsculas.',
            ],
        ];
        foreach ($settings as $setting) {
            if ($this->db->table('dte_settings')
                ->where('group_code', $setting['group_code'])
                ->where('setting_key', $setting['setting_key'])
                ->countAllResults() === 0) {
                $this->db->table('dte_settings')->insert($setting + [
                    'status' => 1,
                    'entry_user' => 'migration',
                    'entry_date' => $now,
                ]);
            }
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'catalog_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'CAT-014'],
            'code' => ['type' => 'SMALLINT', 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'description' => ['type' => 'VARCHAR', 'constraint' => 250, 'null' => true],
            'status' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['catalog_code', 'code'], 'uq_mh_unit_measurements_catalog_code');
        $this->forge->createTable('mh_unit_measurements', true);

        $this->forge->addColumn('commercial_units', [
            'mh_unit_measure_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'display_order',
            ],
        ]);
        $this->db->query(
            'ALTER TABLE `commercial_units` ADD CONSTRAINT `fk_commercial_units_mh_unit_measure` '
            . 'FOREIGN KEY (`mh_unit_measure_id`) REFERENCES `mh_unit_measurements` (`id`) '
            . 'ON DELETE SET NULL ON UPDATE CASCADE'
        );
        $this->db->query('CREATE INDEX `idx_commercial_units_mh_unit_measure` ON `commercial_units` (`mh_unit_measure_id`)');

        $this->forge->addColumn('dte_document_items', [
            'mh_unit_name_snapshot' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'mh_unit_code',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->fieldExists('mh_unit_name_snapshot', 'dte_document_items')) {
            $this->forge->dropColumn('dte_document_items', 'mh_unit_name_snapshot');
        }

        if ($this->db->fieldExists('mh_unit_measure_id', 'commercial_units')) {
            try {
                $this->db->query('ALTER TABLE `commercial_units` DROP FOREIGN KEY `fk_commercial_units_mh_unit_measure`');
            } catch (\Throwable) {
            }
            try {
                $this->db->query('DROP INDEX `idx_commercial_units_mh_unit_measure` ON `commercial_units`');
            } catch (\Throwable) {
            }
            $this->forge->dropColumn('commercial_units', 'mh_unit_measure_id');
        }

        $this->forge->dropTable('mh_unit_measurements', true);
        $this->forge->dropTable('dte_settings', true);
    }
}
