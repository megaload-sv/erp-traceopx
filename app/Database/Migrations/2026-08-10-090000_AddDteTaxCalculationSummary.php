<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDteTaxCalculationSummary extends Migration
{
    public function up(): void
    {
        $columns = [];
        if (! $this->db->fieldExists('tax_summary_json', 'dte_documents')) {
            $columns['tax_summary_json'] = ['type' => 'TEXT', 'null' => true, 'after' => 'iva_total'];
        }
        if (! $this->db->fieldExists('discount_percentage', 'dte_documents')) {
            $columns['discount_percentage'] = ['type' => 'DECIMAL', 'constraint' => '7,2', 'default' => 0, 'after' => 'discount_total'];
        }
        if (! $this->db->fieldExists('non_taxable_total', 'dte_documents')) {
            $columns['non_taxable_total'] = ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0, 'after' => 'income_tax_retained'];
        }
        if (! $this->db->fieldExists('balance_in_favor', 'dte_documents')) {
            $columns['balance_in_favor'] = ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0, 'after' => 'non_taxable_total'];
        }
        if ($columns !== []) {
            $this->forge->addColumn('dte_documents', $columns);
        }
    }

    public function down(): void
    {
        foreach (['tax_summary_json', 'discount_percentage', 'non_taxable_total', 'balance_in_favor'] as $column) {
            if ($this->db->fieldExists($column, 'dte_documents')) {
                $this->forge->dropColumn('dte_documents', $column);
            }
        }
    }
}
