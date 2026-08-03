<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMhFiscalGeographyCatalogs extends Migration
{
    public function up(): void
    {
        $audit = [
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190],
            'entry_date' => ['type' => 'DATETIME'],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
            'delete_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'delete_date' => ['type' => 'DATETIME', 'null' => true],
        ];

        foreach ([
            'mh_taxpayer_types' => 150,
            'mh_economic_activities' => 255,
            'mh_countries' => 150,
            'mh_departments' => 150,
        ] as $table => $nameLength) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'code' => ['type' => 'VARCHAR', 'constraint' => 20],
                'name' => ['type' => 'VARCHAR', 'constraint' => $nameLength],
            ] + $audit);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('code');
            $this->forge->addKey('name');
            $this->forge->createTable($table, true);
        }

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'department_code' => ['type' => 'VARCHAR', 'constraint' => 2],
            'code' => ['type' => 'VARCHAR', 'constraint' => 2],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
        ] + $audit);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['department_code', 'code']);
        $this->forge->addKey('name');
        $this->forge->createTable('mh_municipalities', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'department_code' => ['type' => 'VARCHAR', 'constraint' => 2],
            'municipality_code' => ['type' => 'VARCHAR', 'constraint' => 2, 'null' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 2],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
        ] + $audit);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['department_code', 'code']);
        $this->forge->addKey('name');
        $this->forge->createTable('mh_districts', true);

        $this->forge->addColumn('customers', [
            'mh_taxpayer_type_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'customer_type'],
            'mh_economic_activity_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'mh_taxpayer_type_id'],
            'tax_country_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'mh_economic_activity_id'],
            'tax_department_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'tax_country_id'],
            'tax_municipality_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'tax_department_id'],
            'tax_district_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'tax_municipality_id'],
            'foreign_state' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'tax_district_id'],
            'foreign_city' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'foreign_state'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('customers', ['foreign_city', 'foreign_state', 'tax_district_id', 'tax_municipality_id', 'tax_department_id', 'tax_country_id', 'mh_economic_activity_id', 'mh_taxpayer_type_id']);
        foreach (['mh_districts', 'mh_municipalities', 'mh_departments', 'mh_countries', 'mh_economic_activities', 'mh_taxpayer_types'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
