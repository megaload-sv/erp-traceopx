<?php

namespace App\Services;

use RuntimeException;

class FinancialPolicyService
{
    public const DOCUMENT_TYPES = [
        'consumer_final' => 'Factura Consumidor Final',
        'fiscal_credit' => 'Comprobante de Crédito Fiscal',
        'export' => 'Factura de Exportación',
    ];

    private const LEGACY_DOCUMENT_MAP = [
        'consumer_invoice' => 'consumer_final',
        'tax_credit_invoice' => 'fiscal_credit',
        'export_invoice' => 'export',
        'pending' => null,
    ];

    public function evaluateForServiceCase(int $serviceCaseId): array
    {
        $db = db_connect();
        $source = $db->table('service_cases sc')
            ->select('sc.id, sc.accepted_quotation_id, q.total, q.payment_term_id, q.fiscal_document_type AS quotation_fiscal_document_type, pt.code AS payment_term_code, pt.name AS payment_term_name, pt.requires_advance AS term_requires_advance, pt.minimum_advance_percentage, pt.coordination_release_rule AS term_release_rule, bp.fiscal_document_type AS profile_fiscal_document_type, bp.requires_advance AS profile_requires_advance, bp.advance_percentage AS profile_advance_percentage, bp.coordination_blocked_until_advance')
            ->join('quotations q', 'q.id = sc.accepted_quotation_id', 'left')
            ->join('payment_terms pt', 'pt.id = q.payment_term_id', 'left')
            ->join('service_case_billing_profiles bp', 'bp.service_case_id = sc.id', 'left')
            ->where('sc.id', $serviceCaseId)
            ->get()->getRowArray();

        if ($source === null) {
            throw new RuntimeException('Expediente de servicio no encontrado.');
        }

        $existing = $db->table('service_case_financial_policies')
            ->where('service_case_id', $serviceCaseId)
            ->get()->getRowArray();

        $total = round((float) ($source['total'] ?? 0), 2);
        $requiresAdvance = isset($source['profile_requires_advance'])
            ? (int) $source['profile_requires_advance'] === 1
            : (int) ($source['term_requires_advance'] ?? 0) === 1;

        $advancePercentage = isset($source['profile_advance_percentage'])
            ? (float) $source['profile_advance_percentage']
            : (float) ($source['minimum_advance_percentage'] ?? 0);
        $advancePercentage = max(0, min(100, $advancePercentage));

        $requiredAmount = $requiresAdvance ? round($total * ($advancePercentage / 100), 2) : 0.0;
        $confirmedPaid = (float) ($existing['confirmed_paid_amount'] ?? 0);

        $rawDocumentType = $source['quotation_fiscal_document_type']
            ?: ($source['profile_fiscal_document_type'] ?? null)
            ?: ($existing['fiscal_document_type'] ?? null);
        $documentType = $this->normalizeDocumentType($rawDocumentType);
        $paymentDefined = ! empty($source['payment_term_id']);

        if (! $paymentDefined) {
            $status = 'pending_definition';
            $releaseRule = 'pending_definition';
        } elseif ($requiresAdvance && $requiredAmount > 0 && $confirmedPaid < $requiredAmount) {
            $status = 'awaiting_advance';
            $releaseRule = 'payment_required_before_coordination';
        } else {
            $status = 'clear_for_coordination';
            $releaseRule = 'no_financial_gate';
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'quotation_id' => ! empty($source['accepted_quotation_id']) ? (int) $source['accepted_quotation_id'] : null,
            'origin_type' => ! empty($source['accepted_quotation_id']) ? 'quotation' : 'direct',
            'fiscal_document_type' => $documentType,
            'payment_term_id' => ! empty($source['payment_term_id']) ? (int) $source['payment_term_id'] : null,
            'payment_term_code_snapshot' => $source['payment_term_code'] ?? null,
            'payment_term_name_snapshot' => $source['payment_term_name'] ?? null,
            'requires_advance' => $requiresAdvance ? 1 : 0,
            'advance_percentage' => $advancePercentage,
            'required_before_coordination_amount' => $requiredAmount,
            'confirmed_paid_amount' => $confirmedPaid,
            'coordination_release_rule' => $releaseRule,
            'status' => $status,
            'evaluated_at' => $now,
            'modify_user' => $this->actor(),
            'modify_date' => $now,
        ];

        if ($existing === null) {
            $payload['service_case_id'] = $serviceCaseId;
            $payload['entry_user'] = $this->actor();
            $payload['entry_date'] = $now;
            $db->table('service_case_financial_policies')->insert($payload);
        } else {
            $db->table('service_case_financial_policies')->where('id', (int) $existing['id'])->update($payload);
        }

        $db->table('service_cases')->where('id', $serviceCaseId)->update([
            'financial_policy_status' => $status,
            'modify_user' => $this->actor(),
            'modify_date' => $now,
        ]);

        return $db->table('service_case_financial_policies')
            ->where('service_case_id', $serviceCaseId)
            ->get()->getRowArray() ?? $payload;
    }

    public function coordinationGate(int $serviceCaseId): array
    {
        $policy = $this->evaluateForServiceCase($serviceCaseId);
        $allowed = $policy['status'] === 'clear_for_coordination';

        $reason = null;
        if ($policy['status'] === 'pending_definition') {
            $reason = 'Defina la condición de pago antes de aprobar la coordinación.';
        } elseif ($policy['status'] === 'awaiting_advance') {
            $reason = sprintf(
                'La condición comercial exige un anticipo de %.2f%% ($%s) antes de liberar la coordinación. Pago confirmado: $%s.',
                (float) $policy['advance_percentage'],
                number_format((float) $policy['required_before_coordination_amount'], 2),
                number_format((float) $policy['confirmed_paid_amount'], 2)
            );
        }

        return ['allowed' => $allowed, 'reason' => $reason, 'policy' => $policy];
    }

    public function documentTypeForServiceCase(int $serviceCaseId): ?string
    {
        $policy = $this->evaluateForServiceCase($serviceCaseId);
        $type = trim((string) ($policy['fiscal_document_type'] ?? ''));
        return $type !== '' ? $type : null;
    }

    private function normalizeDocumentType(?string $type): ?string
    {
        $type = trim((string) $type);
        if ($type === '') {
            return null;
        }
        if (array_key_exists($type, self::LEGACY_DOCUMENT_MAP)) {
            return self::LEGACY_DOCUMENT_MAP[$type];
        }
        return isset(self::DOCUMENT_TYPES[$type]) ? $type : null;
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
