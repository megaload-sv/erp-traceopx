<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuotationAcceptance extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'quotation_id' => ['type'=>'INT','unsigned'=>true],
            'accepted_at' => ['type'=>'DATETIME'],
            'accepted_by_name' => ['type'=>'VARCHAR','constraint'=>190],
            'acceptance_type' => ['type'=>'VARCHAR','constraint'=>40],
            'fiscal_document_type' => ['type'=>'VARCHAR','constraint'=>40,'default'=>'pending'],
            'evidence_path' => ['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'evidence_original_name' => ['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'notes' => ['type'=>'TEXT','null'=>true],
            'authorized_by_user_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'entry_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
            'modify_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'modify_date' => ['type'=>'DATETIME','null'=>true],
            'delete_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'delete_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('quotation_id');
        $this->forge->createTable('quotation_acceptances');

        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'service_case_id' => ['type'=>'INT','unsigned'=>true],
            'payment_term_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'fiscal_document_type' => ['type'=>'VARCHAR','constraint'=>40,'default'=>'pending'],
            'requires_advance' => ['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'advance_percentage' => ['type'=>'DECIMAL','constraint'=>'5,2','default'=>0],
            'coordination_blocked_until_advance' => ['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'rule_notes' => ['type'=>'TEXT','null'=>true],
            'entry_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
            'modify_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'modify_date' => ['type'=>'DATETIME','null'=>true],
            'delete_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'delete_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('service_case_id');
        $this->forge->createTable('service_case_billing_profiles');

        $this->db->query('ALTER TABLE `quotation_acceptances` ADD CONSTRAINT `fk_quotation_acceptance_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE `service_case_billing_profiles` ADD CONSTRAINT `fk_case_billing_profile_case` FOREIGN KEY (`service_case_id`) REFERENCES `service_cases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->forge->dropTable('service_case_billing_profiles', true);
        $this->forge->dropTable('quotation_acceptances', true);
    }
}
