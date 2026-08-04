<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StabilizeCustomerFoundation extends Migration
{
    public function up(): void
    {
        $this->removeLegacyFiscalCatalogs();

        $columns = [
            'country_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'address_line'],
            'department_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'country_id'],
            'municipality_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'department_id'],
            'district_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'municipality_id'],
            'foreign_state' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'district_id'],
            'foreign_city' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'foreign_state'],
        ];

        foreach ($columns as $name => $definition) {
            if (! $this->db->fieldExists($name, 'customer_addresses')) {
                $this->forge->addColumn('customer_addresses', [$name => $definition]);
            }
        }

        foreach (['municipality', 'department', 'country'] as $legacyColumn) {
            if ($this->db->fieldExists($legacyColumn, 'customer_addresses')) {
                $this->forge->dropColumn('customer_addresses', $legacyColumn);
            }
        }
    }

    public function down(): void
    {
        $this->forge->addColumn('customer_addresses', [
            'municipality' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'address_line'],
            'department' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'municipality'],
            'country' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'department'],
        ]);

        foreach (['foreign_city', 'foreign_state', 'district_id', 'municipality_id', 'department_id', 'country_id'] as $column) {
            if ($this->db->fieldExists($column, 'customer_addresses')) {
                $this->forge->dropColumn('customer_addresses', $column);
            }
        }
    }

    private function removeLegacyFiscalCatalogs(): void
    {
        foreach (['economic_activity_id', 'customer_taxpayer_type_id'] as $column) {
            if (! $this->db->fieldExists($column, 'customers')) {
                continue;
            }

            $foreignKeys = $this->db->query(
                'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE '
                . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? '
                . 'AND REFERENCED_TABLE_NAME IS NOT NULL',
                ['customers', $column]
            )->getResultArray();

            foreach ($foreignKeys as $foreignKey) {
                $name = str_replace('`', '``', (string) $foreignKey['CONSTRAINT_NAME']);
                $this->db->query("ALTER TABLE `customers` DROP FOREIGN KEY `{$name}`");
            }

            $this->forge->dropColumn('customers', $column);
        }

        foreach (['economic_activities', 'customer_taxpayer_types'] as $table) {
            if ($this->db->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }
    }
}
