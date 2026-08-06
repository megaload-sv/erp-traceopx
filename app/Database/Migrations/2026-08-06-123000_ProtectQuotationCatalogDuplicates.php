<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ProtectQuotationCatalogDuplicates extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('quotation_items')) {
            return;
        }

        $duplicates = $this->db->query(
            'SELECT quotation_id, commercial_item_id, COUNT(*) AS duplicate_count
             FROM quotation_items
             WHERE commercial_item_id IS NOT NULL
               AND delete_date IS NULL
             GROUP BY quotation_id, commercial_item_id
             HAVING COUNT(*) > 1'
        )->getResultArray();

        foreach ($duplicates as $duplicate) {
            $rows = $this->db->table('quotation_items')
                ->where('quotation_id', (int) $duplicate['quotation_id'])
                ->where('commercial_item_id', (int) $duplicate['commercial_item_id'])
                ->where('delete_date', null)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            if (count($rows) < 2) {
                continue;
            }

            $keeper = array_shift($rows);
            $totalQuantity = (float) $keeper['quantity'];

            foreach ($rows as $row) {
                $totalQuantity += (float) $row['quantity'];
                $this->db->table('quotation_items')->where('id', (int) $row['id'])->delete();
            }

            $this->db->table('quotation_items')->where('id', (int) $keeper['id'])->update([
                'quantity' => $totalQuantity,
                'line_total' => round($totalQuantity * (float) $keeper['unit_price'], 2),
                'modify_user' => 'migration',
                'modify_date' => date('Y-m-d H:i:s'),
            ]);
        }

        $indexExists = false;
        foreach ($this->db->getIndexData('quotation_items') as $name => $index) {
            if ($name === 'uq_quotation_catalog_item') {
                $indexExists = true;
                break;
            }
        }

        if (! $indexExists) {
            $this->db->query(
                'CREATE UNIQUE INDEX `uq_quotation_catalog_item`
                 ON `quotation_items` (`quotation_id`, `commercial_item_id`)'
            );
        }

        $quotationIds = $this->db->table('quotations')->select('id')->get()->getResultArray();
        foreach ($quotationIds as $quotation) {
            $quotationId = (int) $quotation['id'];
            $subtotalRow = $this->db->table('quotation_items')
                ->selectSum('line_total', 'subtotal')
                ->where('quotation_id', $quotationId)
                ->where('delete_date', null)
                ->get()
                ->getRowArray();

            $current = $this->db->table('quotations')->where('id', $quotationId)->get()->getRowArray();
            if ($current === null) {
                continue;
            }

            $subtotal = (float) ($subtotalRow['subtotal'] ?? 0);
            $total = $subtotal
                - (float) $current['discount']
                + (float) $current['adjustment']
                + (float) $current['tax_amount'];

            $this->db->table('quotations')->where('id', $quotationId)->update([
                'subtotal' => $subtotal,
                'total' => $total,
                'modify_user' => 'migration',
                'modify_date' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('quotation_items')) {
            return;
        }

        foreach ($this->db->getIndexData('quotation_items') as $name => $index) {
            if ($name === 'uq_quotation_catalog_item') {
                $this->db->query('DROP INDEX `uq_quotation_catalog_item` ON `quotation_items`');
                break;
            }
        }
    }
}
