<?php

namespace App\Services;

use RuntimeException;

class DtePaymentSnapshotService
{
    public function previewForBillingCase(int $billingCaseId): array
    {
        $db = db_connect();
        $case = $db->table('billing_cases')->where('id', $billingCaseId)->where('delete_date', null)->get()->getRowArray();
        if ($case === null) {
            throw new RuntimeException('Preparación de facturación no encontrada para construir pagos DTE.');
        }

        $document = $db->table('dte_documents')->where('billing_case_id', $billingCaseId)->get()->getRowArray();
        if ($document === null) {
            throw new RuntimeException('Documento DTE no encontrado para construir pagos.');
        }

        if (! empty($document['payment_snapshot_json'])) {
            $frozen = json_decode((string) $document['payment_snapshot_json'], true);
            if (is_array($frozen)) {
                return $frozen + ['source' => 'frozen'];
            }
        }

        return $this->buildLiveSnapshot($case) + ['source' => 'live'];
    }

    public function freezeForBillingCase(int $billingCaseId): array
    {
        $db = db_connect();
        $case = $db->table('billing_cases')->where('id', $billingCaseId)->where('delete_date', null)->get()->getRowArray();
        $document = $db->table('dte_documents')->where('billing_case_id', $billingCaseId)->get()->getRowArray();
        if ($case === null || $document === null) {
            throw new RuntimeException('No fue posible localizar la preparación DTE para congelar pagos.');
        }
        if (! empty($document['payment_snapshot_json'])) {
            $snapshot = json_decode((string) $document['payment_snapshot_json'], true);
            if (is_array($snapshot)) {
                return $snapshot + ['source' => 'frozen'];
            }
        }

        $snapshot = $this->buildLiveSnapshot($case);
        $encoded = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException('No fue posible serializar el snapshot fiscal de pagos.');
        }
        $db->table('dte_documents')->where('id', (int) $document['id'])->update([
            'payment_snapshot_json' => $encoded,
            'payment_snapshot_at' => date('Y-m-d H:i:s'),
            'modify_user' => $this->actor(),
            'modify_date' => date('Y-m-d H:i:s'),
        ]);

        return $snapshot + ['source' => 'frozen'];
    }

    private function buildLiveSnapshot(array $case): array
    {
        $db = db_connect();
        $payments = $db->table('billing_payments')
            ->where('billing_case_id', (int) $case['id'])
            ->where('status', 'confirmed')
            ->orderBy('payment_date', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $conditionCode = trim((string) ($case['mh_operation_condition_code_snapshot'] ?? ''));
        $condition = $conditionCode !== '' && ctype_digit($conditionCode) ? (int) $conditionCode : null;
        if (! in_array($condition, [1, 2, 3], true)) {
            $condition = $this->inferCondition($case);
        }

        $termCode = trim((string) ($case['mh_credit_term_code_snapshot'] ?? ''));
        $period = ! empty($case['mh_credit_period_snapshot']) ? (int) $case['mh_credit_period_snapshot'] : null;
        $rows = [];
        $electronicNumbers = [];

        foreach ($payments as $payment) {
            $code = strtoupper(trim((string) ($payment['mh_payment_code'] ?? '')));
            if ($code === '') {
                continue;
            }
            $reference = trim((string) ($payment['reference'] ?? ''));
            $rows[] = [
                'codigo' => $code,
                'montoPago' => round((float) $payment['amount'], 2),
                'referencia' => $reference !== '' ? mb_substr($reference, 0, 50) : null,
                'plazo' => $condition === 2 && $termCode !== '' ? str_pad($termCode, 2, '0', STR_PAD_LEFT) : null,
                'periodo' => $condition === 2 ? $period : null,
            ];
            $electronic = trim((string) ($payment['electronic_payment_number'] ?? ''));
            if ($electronic !== '') {
                $electronicNumbers[$electronic] = true;
            }
        }

        $planned = json_decode((string) ($case['planned_payment_methods_json'] ?? ''), true);
        if (! is_array($planned)) {
            $planned = [];
        }

        return [
            'condition_code' => $condition,
            'payments' => $rows !== [] ? $rows : null,
            'electronic_payment_number' => count($electronicNumbers) === 1 ? array_key_first($electronicNumbers) : null,
            'confirmed_total' => round(array_sum(array_column($payments, 'amount')), 2),
            'balance_amount' => round((float) ($case['balance_amount'] ?? 0), 2),
            'credit_term_code' => $condition === 2 ? ($termCode !== '' ? str_pad($termCode, 2, '0', STR_PAD_LEFT) : null) : null,
            'credit_period' => $condition === 2 ? $period : null,
            'planned_methods' => $planned,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function inferCondition(array $case): int
    {
        $name = mb_strtolower((string) ($case['payment_term_name_snapshot'] ?? ''));
        if (str_contains($name, 'crédito') || str_contains($name, 'credito')) {
            return 2;
        }
        if (str_contains($name, 'otro')) {
            return 3;
        }
        return 1;
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
