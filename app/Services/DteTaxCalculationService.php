<?php

namespace App\Services;

use RuntimeException;

class DteTaxCalculationService
{
    public function recalculate(int $documentId): array
    {
        $db = db_connect();
        $document = $db->table('dte_documents d')
            ->select('d.*, dt.code AS document_code, dt.mh_code')
            ->join('dte_document_types dt', 'dt.id = d.document_type_id')
            ->where('d.id', $documentId)
            ->get()->getRowArray();

        if ($document === null) {
            throw new RuntimeException('Documento DTE no encontrado para cálculo tributario.');
        }

        $code = (string) $document['document_code'];
        if (! in_array($code, ['FCF', 'CCF', 'FEX', 'NC', 'ND'], true)) {
            return $document;
        }

        $ivaRate = $this->ivaRate();
        $ivaLabel = 'Impuesto al Valor Agregado ' . number_format($ivaRate * 100, 2) . '%';
        $items = $db->table('dte_document_items')
            ->where('dte_document_id', $documentId)
            ->orderBy('sequence')
            ->get()->getResultArray();

        $totals = [
            'non_subject' => 0.0,
            'exempt' => 0.0,
            'taxed' => 0.0,
            'discount' => 0.0,
            'iva' => 0.0,
            'non_taxable' => 0.0,
        ];
        $taxSummary = [];
        $now = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $classification = (string) ($item['fiscal_classification'] ?? 'taxed');
            $gross = max(0, round(((float) $item['quantity'] * (float) $item['unit_price']) - (float) $item['discount_amount'], 8));
            $taxCodes = $this->decodeTaxCodes($item['tax_codes_json'] ?? null);

            $nonSubject = $classification === 'non_subject' ? $gross : 0.0;
            $exempt = $classification === 'exempt' ? $gross : 0.0;
            $taxed = $classification === 'taxed' ? $gross : 0.0;
            $ivaItem = 0.0;

            if ($classification === 'taxed') {
                if ($code === 'FEX') {
                    $taxCodes = ['C3'];
                } elseif ($taxCodes === []) {
                    $taxCodes = ['20'];
                }

                if ($code === 'FCF' && in_array('20', $taxCodes, true)) {
                    // En FCF el IVA se reporta por ítem y se extrae del valor gravado informado al consumidor.
                    $ivaItem = round($taxed - ($taxed / (1 + $ivaRate)), 8);
                    $totals['iva'] += $ivaItem;
                } elseif (in_array($code, ['CCF', 'NC', 'ND'], true) && in_array('20', $taxCodes, true)) {
                    // En CCF/NC/ND el IVA se adiciona al valor gravado. Se conserva también por línea
                    // para auditoría/UX, aunque el JSON Builder decidirá si el schema serializa ivaItem.
                    $ivaItem = round($taxed * $ivaRate, 8);
                    $this->addTaxSummary($taxSummary, '20', $ivaLabel, $ivaItem);
                    $totals['iva'] += $ivaItem;
                } elseif ($code === 'FEX' && in_array('C3', $taxCodes, true)) {
                    $ivaItem = 0.0;
                    $this->addTaxSummary($taxSummary, 'C3', 'Impuesto al Valor Agregado (exportaciones) 0%', 0.0);
                }
            } else {
                $taxCodes = [];
            }

            $lineTotal = $gross;
            if (in_array($code, ['CCF', 'NC', 'ND'], true) && $classification === 'taxed' && in_array('20', $taxCodes, true)) {
                $lineTotal = round($gross + $ivaItem, 2);
            }

            $db->table('dte_document_items')->where('id', (int) $item['id'])->update([
                'non_subject_sale' => $nonSubject,
                'exempt_sale' => $exempt,
                'taxed_sale' => $taxed,
                'iva_item' => round($ivaItem, 8),
                'tax_codes_json' => $taxCodes !== [] ? json_encode(array_values(array_unique($taxCodes)), JSON_UNESCAPED_UNICODE) : null,
                'line_total' => $lineTotal,
                'modify_user' => $this->actor(),
                'modify_date' => $now,
            ]);

            $totals['non_subject'] += $nonSubject;
            $totals['exempt'] += $exempt;
            $totals['taxed'] += $taxed;
            $totals['discount'] += (float) $item['discount_amount'];
            $totals['non_taxable'] += (float) ($item['non_taxable_amount'] ?? 0);
        }

        foreach ($taxSummary as &$tax) {
            $tax['valor'] = round((float) $tax['valor'], 2);
        }
        unset($tax);

        $nonSubject = round($totals['non_subject'], 2);
        $exempt = round($totals['exempt'], 2);
        $taxed = round($totals['taxed'], 2);
        $discount = round($totals['discount'], 2);
        $iva = round($totals['iva'], 2);
        $nonTaxable = round($totals['non_taxable'], 2);
        $subtotal = round($nonSubject + $exempt + $taxed, 2);

        // FCF contiene IVA dentro del monto gravado. CCF/NC/ND lo adicionan en resumen. FEX opera con C3 a tasa 0%.
        $operation = match ($code) {
            'CCF', 'NC', 'ND' => round($subtotal + $iva + $nonTaxable - (float) $document['iva_retained'] - (float) $document['income_tax_retained'] + (float) $document['iva_perceived'], 2),
            default => round($subtotal + $nonTaxable - (float) $document['iva_retained'] - (float) $document['income_tax_retained'], 2),
        };
        $payable = max(0, round($operation + (float) ($document['balance_in_favor'] ?? 0), 2));

        $db->table('dte_documents')->where('id', $documentId)->update([
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'taxed_total' => $taxed,
            'exempt_total' => $exempt,
            'non_subject_total' => $nonSubject,
            'iva_total' => $iva,
            'tax_summary_json' => $taxSummary !== [] ? json_encode(array_values($taxSummary), JSON_UNESCAPED_UNICODE) : null,
            'non_taxable_total' => $nonTaxable,
            'operation_total' => $operation,
            'amount_payable' => $payable,
            'modify_user' => $this->actor(),
            'modify_date' => $now,
        ]);

        return $db->table('dte_documents')->where('id', $documentId)->get()->getRowArray();
    }

    private function ivaRate(): float
    {
        $row = db_connect()->table('dte_settings')
            ->where('group_code', 'tax')
            ->where('setting_key', 'iva_rate')
            ->where('status', 1)
            ->get()->getRowArray();

        $percentage = $row !== null ? (float) $row['setting_value'] : 13.0;
        if ($percentage < 0 || $percentage > 100) {
            throw new RuntimeException('La tasa de IVA configurada para DTE no es válida.');
        }

        return $percentage / 100;
    }

    private function decodeTaxCodes(?string $json): array
    {
        if ($json === null || trim($json) === '') return [];
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) return [];
        return array_values(array_filter(array_map(static fn ($v) => strtoupper(trim((string) $v)), $decoded)));
    }

    private function addTaxSummary(array &$summary, string $code, string $description, float $value): void
    {
        if (! isset($summary[$code])) {
            $summary[$code] = ['codigo' => $code, 'descripcion' => $description, 'valor' => 0.0];
        }
        $summary[$code]['valor'] += $value;
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
