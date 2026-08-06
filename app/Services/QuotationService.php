<?php

namespace App\Services;

use App\Models\QuotationItemModel;
use App\Models\QuotationModel;
use RuntimeException;

class QuotationService
{
    public function createDraft(array $data): int
    {
        $model = new QuotationModel();
        $quotationDate = $data['quotation_date'] ?? date('Y-m-d');
        $validityDays = max(1, (int) ($data['validity_days'] ?? 30));
        $id = $model->insert([
            'uuid' => $this->uuidV4(),
            'code' => $model->nextCode(),
            'commercial_request_id' => $data['commercial_request_id'] ?? null,
            'customer_id' => (int) $data['customer_id'],
            'assigned_user_id' => $data['assigned_user_id'] ?? null,
            'payment_term_id' => $data['payment_term_id'] ?? null,
            'origin_type' => $data['origin_type'] ?? 'direct',
            'subject' => trim((string) $data['subject']),
            'quotation_date' => $quotationDate,
            'validity_days' => $validityDays,
            'valid_until' => date('Y-m-d', strtotime($quotationDate . " +{$validityDays} days")),
            'status' => 'draft',
            'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
            'agent_name_snapshot' => $data['agent_name_snapshot'] ?? null,
            'agent_email_snapshot' => $data['agent_email_snapshot'] ?? null,
            'agent_phone_snapshot' => $data['agent_phone_snapshot'] ?? null,
        ], true);

        if ($id === false) {
            throw new RuntimeException('No fue posible crear el borrador de cotización.');
        }

        (new ActivityService())->record('quotation', (int) $id, 'quotation.created', 'Cotización creada', 'Se creó la cotización en estado borrador.');

        return (int) $id;
    }

    public function recalculateTotals(int $quotationId): array
    {
        $items = (new QuotationItemModel())->where('quotation_id', $quotationId)->findAll();
        $subtotal = array_sum(array_map(static fn (array $item): float => (float) $item['line_total'], $items));
        $quotation = (new QuotationModel())->find($quotationId);

        if ($quotation === null) {
            throw new RuntimeException('Cotización no encontrada.');
        }

        $total = $subtotal - (float) $quotation['discount'] + (float) $quotation['adjustment'] + (float) $quotation['tax_amount'];
        (new QuotationModel())->update($quotationId, ['subtotal' => $subtotal, 'total' => $total]);

        return ['subtotal' => $subtotal, 'total' => $total];
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
