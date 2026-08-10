<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDteCoreTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'code' => ['type'=>'VARCHAR','constraint'=>10],
            'mh_code' => ['type'=>'CHAR','constraint'=>2],
            'name' => ['type'=>'VARCHAR','constraint'=>120],
            'schema_version' => ['type'=>'SMALLINT','unsigned'=>true],
            'schema_file' => ['type'=>'VARCHAR','constraint'=>120,'null'=>true],
            'origin_mode' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'both'],
            'status' => ['type'=>'TINYINT','unsigned'=>true,'default'=>1],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addUniqueKey('mh_code');
        $this->forge->createTable('dte_document_types', true);

        $now = date('Y-m-d H:i:s');
        $types = [
            ['code'=>'FCF','mh_code'=>'01','name'=>'Factura Consumidor Final','schema_version'=>1,'schema_file'=>'fe-fc-v1.json','origin_mode'=>'quotation_or_direct'],
            ['code'=>'CCF','mh_code'=>'03','name'=>'Comprobante de Crédito Fiscal','schema_version'=>3,'schema_file'=>'fe-ccf-v3.json','origin_mode'=>'quotation_or_direct'],
            ['code'=>'NC','mh_code'=>'05','name'=>'Nota de Crédito','schema_version'=>3,'schema_file'=>'fe-nc-v3.json','origin_mode'=>'direct_or_related'],
            ['code'=>'ND','mh_code'=>'06','name'=>'Nota de Débito','schema_version'=>3,'schema_file'=>'fe-nd-v3.json','origin_mode'=>'direct_or_related'],
            ['code'=>'FEX','mh_code'=>'11','name'=>'Factura de Exportación','schema_version'=>1,'schema_file'=>'fe-fex-v1.json','origin_mode'=>'quotation_or_direct'],
            ['code'=>'FSE','mh_code'=>'14','name'=>'Factura de Sujeto Excluido','schema_version'=>1,'schema_file'=>'fe-fse-v1.json','origin_mode'=>'direct'],
        ];
        foreach ($types as $type) {
            if ($this->db->table('dte_document_types')->where('code',$type['code'])->countAllResults() === 0) {
                $this->db->table('dte_document_types')->insert($type + ['status'=>1,'entry_date'=>$now]);
            }
        }

        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'uuid' => ['type'=>'CHAR','constraint'=>36],
            'billing_case_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'service_case_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'quotation_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'customer_id' => ['type'=>'INT','unsigned'=>true],
            'document_type_id' => ['type'=>'INT','unsigned'=>true],
            'origin_type' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'quotation'],
            'status' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'draft'],
            'schema_version' => ['type'=>'SMALLINT','unsigned'=>true],
            'environment' => ['type'=>'CHAR','constraint'=>2,'default'=>'00'],
            'generation_model' => ['type'=>'TINYINT','unsigned'=>true,'default'=>1],
            'operation_type' => ['type'=>'TINYINT','unsigned'=>true,'default'=>1],
            'contingency_type' => ['type'=>'TINYINT','unsigned'=>true,'null'=>true],
            'contingency_reason' => ['type'=>'VARCHAR','constraint'=>500,'null'=>true],
            'control_number' => ['type'=>'VARCHAR','constraint'=>31,'null'=>true],
            'generation_code' => ['type'=>'CHAR','constraint'=>36,'null'=>true],
            'issue_date' => ['type'=>'DATE','null'=>true],
            'issue_time' => ['type'=>'TIME','null'=>true],
            'currency_code' => ['type'=>'CHAR','constraint'=>3,'default'=>'USD'],
            'receiver_name_snapshot' => ['type'=>'VARCHAR','constraint'=>250,'null'=>true],
            'receiver_trade_name_snapshot' => ['type'=>'VARCHAR','constraint'=>150,'null'=>true],
            'receiver_document_type' => ['type'=>'VARCHAR','constraint'=>4,'null'=>true],
            'receiver_document_number' => ['type'=>'VARCHAR','constraint'=>25,'null'=>true],
            'receiver_nrc' => ['type'=>'VARCHAR','constraint'=>12,'null'=>true],
            'receiver_activity_code' => ['type'=>'VARCHAR','constraint'=>10,'null'=>true],
            'receiver_activity_description' => ['type'=>'VARCHAR','constraint'=>150,'null'=>true],
            'receiver_email' => ['type'=>'VARCHAR','constraint'=>120,'null'=>true],
            'receiver_phone' => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'receiver_department_code' => ['type'=>'VARCHAR','constraint'=>4,'null'=>true],
            'receiver_municipality_code' => ['type'=>'VARCHAR','constraint'=>4,'null'=>true],
            'receiver_address' => ['type'=>'VARCHAR','constraint'=>300,'null'=>true],
            'subtotal' => ['type'=>'DECIMAL','constraint'=>'14,2','default'=>0],
            'discount_total' => ['type'=>'DECIMAL','constraint'=>'14,2','default'=>0],
            'taxed_total' => ['type'=>'DECIMAL','constraint'=>'14,2','default'=>0],
            'exempt_total' => ['type'=>'DECIMAL','constraint'=>'14,2','default'=>0],
            'non_subject_total' => ['type'=>'DECIMAL','constraint'=>'14,2','default'=>0],
            'iva_total' => ['type'=>'DECIMAL','constraint'=>'14,2','default'=>0],
            'iva_retained' => ['type'=>'DECIMAL','constraint'=>'14,2','default'=>0],
            'iva_perceived' => ['type'=>'DECIMAL','constraint'=>'14,2','default'=>0],
            'income_tax_retained' => ['type'=>'DECIMAL','constraint'=>'14,2','default'=>0],
            'operation_total' => ['type'=>'DECIMAL','constraint'=>'14,2','default'=>0],
            'amount_payable' => ['type'=>'DECIMAL','constraint'=>'14,2','default'=>0],
            'amount_in_words' => ['type'=>'VARCHAR','constraint'=>200,'null'=>true],
            'operation_condition' => ['type'=>'TINYINT','unsigned'=>true,'null'=>true],
            'observations' => ['type'=>'TEXT','null'=>true],
            'entry_user' => ['type'=>'VARCHAR','constraint'=>120,'null'=>true],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
            'modify_user' => ['type'=>'VARCHAR','constraint'=>120,'null'=>true],
            'modify_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('billing_case_id');
        $this->forge->addKey('service_case_id');
        $this->forge->addKey('customer_id');
        $this->forge->addForeignKey('billing_case_id','billing_cases','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('service_case_id','service_cases','id','SET NULL','CASCADE');
        $this->forge->addForeignKey('quotation_id','quotations','id','SET NULL','CASCADE');
        $this->forge->addForeignKey('customer_id','customers','id','RESTRICT','CASCADE');
        $this->forge->addForeignKey('document_type_id','dte_document_types','id','RESTRICT','CASCADE');
        $this->forge->createTable('dte_documents', true);

        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'dte_document_id' => ['type'=>'INT','unsigned'=>true],
            'quotation_item_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'commercial_item_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'sequence' => ['type'=>'SMALLINT','unsigned'=>true],
            'item_type' => ['type'=>'TINYINT','unsigned'=>true,'default'=>2],
            'related_document_number' => ['type'=>'VARCHAR','constraint'=>36,'null'=>true],
            'code' => ['type'=>'VARCHAR','constraint'=>200,'null'=>true],
            'tax_code' => ['type'=>'VARCHAR','constraint'=>2,'null'=>true],
            'description' => ['type'=>'VARCHAR','constraint'=>1000],
            'quantity' => ['type'=>'DECIMAL','constraint'=>'18,8'],
            'unit_id_snapshot' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'unit_name_snapshot' => ['type'=>'VARCHAR','constraint'=>120,'null'=>true],
            'unit_symbol_snapshot' => ['type'=>'VARCHAR','constraint'=>30,'null'=>true],
            'mh_unit_code' => ['type'=>'TINYINT','unsigned'=>true,'null'=>true],
            'unit_price' => ['type'=>'DECIMAL','constraint'=>'18,8','default'=>0],
            'discount_amount' => ['type'=>'DECIMAL','constraint'=>'18,8','default'=>0],
            'non_subject_sale' => ['type'=>'DECIMAL','constraint'=>'18,8','default'=>0],
            'exempt_sale' => ['type'=>'DECIMAL','constraint'=>'18,8','default'=>0],
            'taxed_sale' => ['type'=>'DECIMAL','constraint'=>'18,8','default'=>0],
            'iva_item' => ['type'=>'DECIMAL','constraint'=>'18,8','default'=>0],
            'suggested_sale_price' => ['type'=>'DECIMAL','constraint'=>'18,8','default'=>0],
            'non_taxable_amount' => ['type'=>'DECIMAL','constraint'=>'18,8','default'=>0],
            'tax_codes_json' => ['type'=>'TEXT','null'=>true],
            'line_total' => ['type'=>'DECIMAL','constraint'=>'14,2','default'=>0],
            'entry_user' => ['type'=>'VARCHAR','constraint'=>120,'null'=>true],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
            'modify_user' => ['type'=>'VARCHAR','constraint'=>120,'null'=>true],
            'modify_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['dte_document_id','sequence']);
        $this->forge->addForeignKey('dte_document_id','dte_documents','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('quotation_item_id','quotation_items','id','SET NULL','CASCADE');
        $this->forge->createTable('dte_document_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('dte_document_items', true);
        $this->forge->dropTable('dte_documents', true);
        $this->forge->dropTable('dte_document_types', true);
    }
}
