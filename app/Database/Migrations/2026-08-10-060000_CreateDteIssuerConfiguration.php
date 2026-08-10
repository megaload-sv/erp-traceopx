<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDteIssuerConfiguration extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'legal_name' => ['type' => 'VARCHAR', 'constraint' => 250],
            'trade_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'nit' => ['type' => 'VARCHAR', 'constraint' => 20],
            'nrc' => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
            'activity_code' => ['type' => 'VARCHAR', 'constraint' => 10],
            'activity_description' => ['type' => 'VARCHAR', 'constraint' => 200],
            'establishment_type_code' => ['type' => 'VARCHAR', 'constraint' => 4, 'null' => true],
            'department_code' => ['type' => 'VARCHAR', 'constraint' => 4],
            'municipality_code' => ['type' => 'VARCHAR', 'constraint' => 4],
            'address_complement' => ['type' => 'VARCHAR', 'constraint' => 300],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30],
            'email' => ['type' => 'VARCHAR', 'constraint' => 120],
            'mh_establishment_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'mh_establishment_code_alt' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'status' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nit', 'uq_dte_issuer_nit');
        $this->forge->createTable('dte_issuer_profiles', true);

        $now = date('Y-m-d H:i:s');
        $settings = [
            [
                'group_code' => 'emission',
                'setting_key' => 'environment',
                'setting_value' => '00',
                'value_type' => 'string',
                'description' => 'Ambiente DTE predeterminado: 00 pruebas, 01 producción.',
            ],
            [
                'group_code' => 'emission',
                'setting_key' => 'default_establishment_id',
                'setting_value' => null,
                'value_type' => 'integer',
                'description' => 'Sucursal DTE predeterminada para nuevos documentos.',
            ],
            [
                'group_code' => 'emission',
                'setting_key' => 'default_point_of_sale_id',
                'setting_value' => null,
                'value_type' => 'integer',
                'description' => 'Punto de venta DTE predeterminado para nuevos documentos.',
            ],
        ];

        foreach ($settings as $setting) {
            $existing = $this->db->table('dte_settings')
                ->where('group_code', $setting['group_code'])
                ->where('setting_key', $setting['setting_key'])
                ->get()->getRowArray();

            if ($existing === null) {
                $this->db->table('dte_settings')->insert($setting + [
                    'status' => 1,
                    'entry_user' => 'migration',
                    'entry_date' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('dte_settings')) {
            $this->db->table('dte_settings')
                ->where('group_code', 'emission')
                ->whereIn('setting_key', ['environment', 'default_establishment_id', 'default_point_of_sale_id'])
                ->delete();
        }

        $this->forge->dropTable('dte_issuer_profiles', true);
    }
}
