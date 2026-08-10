<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDteControlNumberFoundation extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 20],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'mh_code' => ['type' => 'CHAR', 'constraint' => 4],
            'internal_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'is_default' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'status' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_dte_establishments_code');
        $this->forge->addUniqueKey('mh_code', 'uq_dte_establishments_mh_code');
        $this->forge->createTable('dte_establishments', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'establishment_id' => ['type' => 'INT', 'unsigned' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 20],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'mh_code' => ['type' => 'CHAR', 'constraint' => 4],
            'internal_code' => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
            'is_default' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'status' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['establishment_id', 'code'], 'uq_dte_pos_establishment_code');
        $this->forge->addUniqueKey(['establishment_id', 'mh_code'], 'uq_dte_pos_establishment_mh_code');
        $this->forge->addForeignKey('establishment_id', 'dte_establishments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('dte_points_of_sale', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'document_type_id' => ['type' => 'INT', 'unsigned' => true],
            'establishment_id' => ['type' => 'INT', 'unsigned' => true],
            'point_of_sale_id' => ['type' => 'INT', 'unsigned' => true],
            'year' => ['type' => 'SMALLINT', 'unsigned' => true],
            'current_value' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'last_issued_at' => ['type' => 'DATETIME', 'null' => true],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(
            ['document_type_id', 'establishment_id', 'point_of_sale_id', 'year'],
            'uq_dte_control_sequences_scope_year'
        );
        $this->forge->addForeignKey('document_type_id', 'dte_document_types', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('establishment_id', 'dte_establishments', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('point_of_sale_id', 'dte_points_of_sale', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('dte_control_sequences', true);

        $this->forge->addColumn('dte_documents', [
            'establishment_id' => [
                'type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'document_type_id',
            ],
            'point_of_sale_id' => [
                'type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'establishment_id',
            ],
            'control_year' => [
                'type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'after' => 'control_number',
            ],
            'control_correlative' => [
                'type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'control_year',
            ],
        ]);

        $this->db->query(
            'ALTER TABLE `dte_documents` ADD CONSTRAINT `fk_dte_documents_establishment` '
            . 'FOREIGN KEY (`establishment_id`) REFERENCES `dte_establishments` (`id`) '
            . 'ON DELETE RESTRICT ON UPDATE CASCADE'
        );
        $this->db->query(
            'ALTER TABLE `dte_documents` ADD CONSTRAINT `fk_dte_documents_point_of_sale` '
            . 'FOREIGN KEY (`point_of_sale_id`) REFERENCES `dte_points_of_sale` (`id`) '
            . 'ON DELETE RESTRICT ON UPDATE CASCADE'
        );
        $this->db->query('CREATE UNIQUE INDEX `uq_dte_documents_control_number` ON `dte_documents` (`control_number`)');

        $now = date('Y-m-d H:i:s');
        if ($this->db->tableExists('dte_settings')) {
            $existing = $this->db->table('dte_settings')
                ->where('group_code', 'control_number')
                ->where('setting_key', 'generation_strategy')
                ->get()->getRowArray();

            $data = [
                'setting_value' => 'annual_document_establishment_pos_sequence',
                'value_type' => 'string',
                'description' => 'Formato DTE-{tipoDTE}-{codEstableMH}{codPuntoVentaMH}-{correlativo15}. Secuencia independiente por año, tipo DTE, sucursal y punto de venta.',
                'status' => 1,
                'modify_user' => 'migration',
                'modify_date' => $now,
            ];

            if ($existing === null) {
                $this->db->table('dte_settings')->insert($data + [
                    'group_code' => 'control_number',
                    'setting_key' => 'generation_strategy',
                    'entry_user' => 'migration',
                    'entry_date' => $now,
                ]);
            } else {
                $this->db->table('dte_settings')->where('id', (int) $existing['id'])->update($data);
            }
        }
    }

    public function down(): void
    {
        try {
            $this->db->query('DROP INDEX `uq_dte_documents_control_number` ON `dte_documents`');
        } catch (\Throwable) {
        }
        try {
            $this->db->query('ALTER TABLE `dte_documents` DROP FOREIGN KEY `fk_dte_documents_point_of_sale`');
        } catch (\Throwable) {
        }
        try {
            $this->db->query('ALTER TABLE `dte_documents` DROP FOREIGN KEY `fk_dte_documents_establishment`');
        } catch (\Throwable) {
        }

        foreach (['control_correlative', 'control_year', 'point_of_sale_id', 'establishment_id'] as $field) {
            if ($this->db->fieldExists($field, 'dte_documents')) {
                $this->forge->dropColumn('dte_documents', $field);
            }
        }

        $this->forge->dropTable('dte_control_sequences', true);
        $this->forge->dropTable('dte_points_of_sale', true);
        $this->forge->dropTable('dte_establishments', true);
    }
}
