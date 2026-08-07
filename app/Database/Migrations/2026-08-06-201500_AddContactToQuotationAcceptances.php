<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContactToQuotationAcceptances extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('quotation_acceptances') || $this->db->fieldExists('customer_contact_id', 'quotation_acceptances')) {
            return;
        }

        $this->forge->addColumn('quotation_acceptances', [
            'customer_contact_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'quotation_id',
            ],
        ]);

        $this->db->query(
            'ALTER TABLE `quotation_acceptances`
             ADD INDEX `idx_quotation_acceptance_contact` (`customer_contact_id`),
             ADD CONSTRAINT `fk_quotation_acceptance_contact`
             FOREIGN KEY (`customer_contact_id`) REFERENCES `customer_contacts` (`id`)
             ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    public function down(): void
    {
        if (! $this->db->tableExists('quotation_acceptances') || ! $this->db->fieldExists('customer_contact_id', 'quotation_acceptances')) {
            return;
        }

        $this->db->query('ALTER TABLE `quotation_acceptances` DROP FOREIGN KEY `fk_quotation_acceptance_contact`');
        $this->forge->dropColumn('quotation_acceptances', 'customer_contact_id');
    }
}
