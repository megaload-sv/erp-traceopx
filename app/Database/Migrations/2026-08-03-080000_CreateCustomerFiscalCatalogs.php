<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerFiscalCatalogs extends Migration
{
    public function up(): void
    {
        $auditFields = [
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 190],
            'entry_date' => ['type' => 'DATETIME'],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
            'delete_user' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'delete_date' => ['type' => 'DATETIME', 'null' => true],
        ];

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 30],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $auditFields);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('customer_taxpayer_types', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 20],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'source_reference' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'source_updated_at' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $auditFields);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('name');
        $this->forge->createTable('economic_activities', true);

        $this->forge->addColumn('customers', [
            'customer_taxpayer_type_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'customer_type',
            ],
            'economic_activity_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
                'after' => 'customer_taxpayer_type_id',
            ],
        ]);

        $this->db->query('ALTER TABLE customers ADD CONSTRAINT fk_customers_taxpayer_type FOREIGN KEY (customer_taxpayer_type_id) REFERENCES customer_taxpayer_types(id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->db->query('ALTER TABLE customers ADD CONSTRAINT fk_customers_economic_activity FOREIGN KEY (economic_activity_id) REFERENCES economic_activities(id) ON UPDATE CASCADE ON DELETE SET NULL');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE customers DROP FOREIGN KEY fk_customers_economic_activity');
        $this->db->query('ALTER TABLE customers DROP FOREIGN KEY fk_customers_taxpayer_type');
        $this->forge->dropColumn('customers', ['economic_activity_id', 'customer_taxpayer_type_id']);
        $this->forge->dropTable('economic_activities', true);
        $this->forge->dropTable('customer_taxpayer_types', true);
    }
}
