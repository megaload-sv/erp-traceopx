<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQuotationDtePaymentContext extends Migration
{
    public function up(): void
    {
        $db = db_connect();

        if (! $db->fieldExists('mh_operation_condition_code', 'quotations')) {
            $this->forge->addColumn('quotations', [
                'mh_operation_condition_code' => ['type' => 'CHAR', 'constraint' => 1, 'null' => true, 'after' => 'payment_term_id'],
                'mh_credit_term_code' => ['type' => 'CHAR', 'constraint' => 2, 'null' => true, 'after' => 'mh_operation_condition_code'],
                'mh_credit_period' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'mh_credit_term_code'],
            ]);
        }

        if (! $db->tableExists('quotation_payment_methods')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'quotation_id' => ['type' => 'INT', 'unsigned' => true],
                'mh_payment_code' => ['type' => 'CHAR', 'constraint' => 2],
                'payment_method_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 160],
                'sequence' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 1],
                'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'entry_date' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['quotation_id', 'mh_payment_code'], 'uq_quotation_payment_method');
            $this->forge->addKey('quotation_id');
            $this->forge->addForeignKey('quotation_id', 'quotations', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('quotation_payment_methods', true);
        }

        if ($db->tableExists('billing_cases') && ! $db->fieldExists('mh_operation_condition_code_snapshot', 'billing_cases')) {
            $this->forge->addColumn('billing_cases', [
                'mh_operation_condition_code_snapshot' => ['type' => 'CHAR', 'constraint' => 1, 'null' => true, 'after' => 'payment_term_name_snapshot'],
                'mh_credit_term_code_snapshot' => ['type' => 'CHAR', 'constraint' => 2, 'null' => true, 'after' => 'mh_operation_condition_code_snapshot'],
                'mh_credit_period_snapshot' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'mh_credit_term_code_snapshot'],
                'planned_payment_methods_json' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'mh_credit_period_snapshot'],
            ]);
        }

        $catalogs = [
            'CAT-016' => [
                ['code' => '1', 'name' => 'Contado'],
                ['code' => '2', 'name' => 'Crédito'],
                ['code' => '3', 'name' => 'Otro'],
            ],
            'CAT-017' => [
                ['code' => '01', 'name' => 'Billetes y monedas'],
                ['code' => '02', 'name' => 'Tarjeta Débito'],
                ['code' => '03', 'name' => 'Tarjeta Crédito'],
                ['code' => '04', 'name' => 'Cheque'],
                ['code' => '05', 'name' => 'Transferencia / Depósito Bancario'],
                ['code' => '06', 'name' => 'Vales o Cupones'],
                ['code' => '08', 'name' => 'Dinero electrónico'],
                ['code' => '09', 'name' => 'Monedero electrónico'],
                ['code' => '10', 'name' => 'Certificado o tarjeta de regalo'],
                ['code' => '11', 'name' => 'Bitcoin'],
                ['code' => '12', 'name' => 'Otras Criptomonedas'],
                ['code' => '13', 'name' => 'Cuentas por pagar del receptor'],
                ['code' => '14', 'name' => 'Giro bancario'],
                ['code' => '99', 'name' => 'Otros'],
            ],
            'CAT-018' => [
                ['code' => '01', 'name' => 'Días'],
                ['code' => '02', 'name' => 'Meses'],
                ['code' => '03', 'name' => 'Años'],
            ],
        ];

        if ($db->tableExists('mh_catalog_values')) {
            $now = date('Y-m-d H:i:s');
            foreach ($catalogs as $catalogCode => $rows) {
                foreach ($rows as $index => $row) {
                    $existing = $db->table('mh_catalog_values')
                        ->where('catalog_code', $catalogCode)
                        ->where('parent_code', '')
                        ->where('code', $row['code'])
                        ->get()->getRowArray();
                    $data = [
                        'name' => $row['name'],
                        'display_order' => $index + 1,
                        'status' => 1,
                        'modify_user' => 'migration',
                        'modify_date' => $now,
                    ];
                    if ($existing === null) {
                        $db->table('mh_catalog_values')->insert($data + [
                            'catalog_code' => $catalogCode,
                            'code' => $row['code'],
                            'parent_code' => '',
                            'entry_user' => 'migration',
                            'entry_date' => $now,
                        ]);
                    } else {
                        $db->table('mh_catalog_values')->where('id', (int) $existing['id'])->update($data);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $db = db_connect();
        $this->forge->dropTable('quotation_payment_methods', true);

        foreach (['mh_operation_condition_code', 'mh_credit_term_code', 'mh_credit_period'] as $column) {
            if ($db->fieldExists($column, 'quotations')) {
                $this->forge->dropColumn('quotations', $column);
            }
        }
        foreach (['mh_operation_condition_code_snapshot', 'mh_credit_term_code_snapshot', 'mh_credit_period_snapshot', 'planned_payment_methods_json'] as $column) {
            if ($db->tableExists('billing_cases') && $db->fieldExists($column, 'billing_cases')) {
                $this->forge->dropColumn('billing_cases', $column);
            }
        }
    }
}
