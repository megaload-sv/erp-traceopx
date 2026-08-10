<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class DteDocumentService
{
    private const BILLING_TO_DTE = [
        'consumer_final' => 'FCF',
        'fiscal_credit' => 'CCF',
        'export' => 'FEX',
    ];

    public function ensureForBillingCase(int $billingCaseId): array
    {
        $db = db_connect();
        $existing = $db->table('dte_documents')->where('billing_case_id', $billingCaseId)->get()->getRowArray();
        if ($existing !== null) {
            return $existing;
        }

        $source = $db->table('billing_cases bc')
            ->select('bc.*, c.business_name, c.trade_name, c.email, c.phone')
            ->join('customers c', 'c.id = bc.customer_id', 'left')
            ->where('bc.id', $billingCaseId)
            ->where('bc.delete_date', null)
            ->get()->getRowArray();
        if ($source === null) {
            throw new RuntimeException('Preparación de facturación no encontrada.');
        }

        $dteCode = self::BILLING_TO_DTE[(string) $source['document_type']] ?? null;
        if ($dteCode === null) {
            throw new RuntimeException('El tipo de documento de esta preparación aún no está habilitado para generación automática desde cotización.');
        }

        $type = $db->table('dte_document_types')->where('code', $dteCode)->where('status', 1)->get()->getRowArray();
        if ($type === null) {
            throw new RuntimeException('No se encontró la configuración del tipo DTE ' . $dteCode . '.');
        }

        $now = date('Y-m-d H:i:s');
        $total = round((float) $source['invoiceable_amount'], 2);
        $db->transBegin();
        try {
            $db->table('dte_documents')->insert([
                'uuid' => $this->uuidV4(),
                'billing_case_id' => $billingCaseId,
                'service_case_id' => ! empty($source['service_case_id']) ? (int) $source['service_case_id'] : null,
                'quotation_id' => ! empty($source['quotation_id']) ? (int) $source['quotation_id'] : null,
                'customer_id' => (int) $source['customer_id'],
                'document_type_id' => (int) $type['id'],
                'origin_type' => (string) ($source['origin_type'] ?? 'quotation'),
                'status' => 'draft',
                'schema_version' => (int) $type['schema_version'],
                'environment' => '00',
                'generation_model' => 1,
                'operation_type' => 1,
                'currency_code' => (string) ($source['currency_code'] ?: 'USD'),
                'receiver_name_snapshot' => $source['business_name'] ?? null,
                'receiver_trade_name_snapshot' => $source['trade_name'] ?? null,
                'receiver_email' => $source['email'] ?? null,
                'receiver_phone' => $source['phone'] ?? null,
                'subtotal' => $total,
                'taxed_total' => $total,
                'operation_total' => $total,
                'amount_payable' => $total,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);
            $documentId = (int) $db->insertID();

            $this->copyQuotationItems($documentId, ! empty($source['quotation_id']) ? (int) $source['quotation_id'] : null, $now);

            $db->table('service_case_events')->insert([
                'service_case_id' => (int) $source['service_case_id'],
                'event_code' => 'dte.draft_created',
                'title' => 'Documento fiscal preparado',
                'description' => $type['name'] . ' · Se creó el snapshot fiscal independiente de la cotización.',
                'entity_type' => 'dte_document',
                'entity_id' => $documentId,
                'occurred_at' => $now,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);

            $db->transCommit();
            return $db->table('dte_documents')->where('id', $documentId)->get()->getRowArray();
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function workspace(int $billingCaseId): array
    {
        $document = $this->ensureForBillingCase($billingCaseId);
        $db = db_connect();
        $document = $db->table('dte_documents d')
            ->select('d.*, dt.code AS document_code, dt.mh_code, dt.name AS document_name, dt.schema_file')
            ->join('dte_document_types dt', 'dt.id = d.document_type_id')
            ->where('d.id', (int) $document['id'])
            ->get()->getRowArray();

        return [
            'document' => $document,
            'items' => $db->table('dte_document_items')->where('dte_document_id', (int) $document['id'])->orderBy('sequence')->get()->getResultArray(),
        ];
    }

    private function copyQuotationItems(int $documentId, ?int $quotationId, string $now): void
    {
        if ($quotationId === null) {
            return;
        }

        $db = db_connect();
        $items = $db->table('quotation_items qi')
            ->select('qi.*, ci.code AS commercial_item_code, cu.name AS unit_name, cu.symbol AS unit_symbol')
            ->join('commercial_items ci', 'ci.id = qi.commercial_item_id', 'left')
            ->join('commercial_units cu', 'cu.id = qi.unit_id', 'left')
            ->where('qi.quotation_id', $quotationId)
            ->where('qi.delete_date', null)
            ->orderBy('qi.sort_order')
            ->orderBy('qi.id')
            ->get()->getResultArray();

        $sequence = 1;
        foreach ($items as $item) {
            $lineTotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
            $db->table('dte_document_items')->insert([
                'dte_document_id' => $documentId,
                'quotation_item_id' => (int) $item['id'],
                'commercial_item_id' => ! empty($item['commercial_item_id']) ? (int) $item['commercial_item_id'] : null,
                'sequence' => $sequence++,
                'item_type' => 2,
                'code' => $item['commercial_item_code'] ?? null,
                'description' => trim((string) $item['description'] . (! empty($item['long_description']) ? "\n" . $item['long_description'] : '')),
                'quantity' => (float) $item['quantity'],
                'unit_id_snapshot' => ! empty($item['unit_id']) ? (int) $item['unit_id'] : null,
                'unit_name_snapshot' => $item['unit_name'] ?? null,
                'unit_symbol_snapshot' => $item['unit_symbol'] ?? null,
                'unit_price' => (float) $item['unit_price'],
                'discount_amount' => 0,
                'taxed_sale' => $lineTotal,
                'line_total' => $lineTotal,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);
        }
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
