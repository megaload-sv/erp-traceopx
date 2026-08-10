<?php

namespace App\Services;

use App\Models\BillingCaseModel;
use RuntimeException;
use Throwable;

class BillingPreparationService
{
    public const DOCUMENT_TYPES = FinancialPolicyService::DOCUMENT_TYPES;

    public function eligibleCases(): array
    {
        $db = db_connect();
        $rows = $db->table('service_cases sc')
            ->select('sc.id, sc.code, sc.billing_status, sc.accepted_quotation_id, c.business_name, q.code AS quotation_code, q.total AS quotation_total, q.fiscal_document_type, wo.id AS work_order_id, wo.code AS work_order_code, wo.status AS work_order_status, bc.id AS billing_case_id, bc.code AS billing_case_code')
            ->join('customers c', 'c.id = sc.customer_id', 'left')
            ->join('quotations q', 'q.id = sc.accepted_quotation_id', 'left')
            ->join('work_orders wo', 'wo.service_case_id = sc.id AND wo.delete_date IS NULL', 'left')
            ->join('billing_cases bc', 'bc.service_case_id = sc.id AND bc.delete_date IS NULL', 'left')
            ->where('sc.accepted_quotation_id IS NOT NULL', null, false)
            ->orderBy('sc.id', 'DESC')
            ->get()->getResultArray();

        $policyService = new FinancialPolicyService();
        $result = [];
        foreach ($rows as $row) {
            $policy = $policyService->evaluateForServiceCase((int) $row['id']);
            $row['financial_policy'] = $policy;
            $needsPreOperationalBilling = $policy['status'] === 'awaiting_advance';
            $postOperationalBilling = ($row['work_order_status'] ?? null) === 'closed';
            if ($row['billing_case_id'] !== null || $needsPreOperationalBilling || $postOperationalBilling) {
                $result[] = $row;
            }
        }

        return $result;
    }

    public function workspace(int $billingCaseId): array
    {
        $db = db_connect();
        $case = $db->table('billing_cases bc')
            ->select('bc.*, sc.code AS service_case_code, wo.code AS work_order_code, c.business_name, q.code AS quotation_code, q.subject AS quotation_subject, pt.description AS payment_term_description, pt.term_type, pt.requires_advance, pt.minimum_advance_percentage')
            ->join('service_cases sc', 'sc.id = bc.service_case_id', 'left')
            ->join('work_orders wo', 'wo.id = bc.work_order_id', 'left')
            ->join('customers c', 'c.id = bc.customer_id', 'left')
            ->join('quotations q', 'q.id = bc.quotation_id', 'left')
            ->join('payment_terms pt', 'pt.id = bc.payment_term_id', 'left')
            ->where('bc.id', $billingCaseId)
            ->where('bc.delete_date', null)
            ->get()->getRowArray();
        if ($case === null) {
            throw new RuntimeException('Preparación de facturación no encontrada.');
        }

        return [
            'billingCase' => $case,
            'schedule' => $db->table('billing_payment_schedule')->where('billing_case_id', $billingCaseId)->orderBy('sequence')->get()->getResultArray(),
            'documentTypes' => self::DOCUMENT_TYPES,
            'financialPolicy' => (new FinancialPolicyService())->evaluateForServiceCase((int) $case['service_case_id']),
        ];
    }

    public function createFromServiceCase(int $serviceCaseId, string $requestedDocumentType = '', string $notes = ''): int
    {
        $db = db_connect();
        $existing = $db->table('billing_cases')->where('service_case_id', $serviceCaseId)->where('delete_date', null)->get()->getRowArray();
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $policyService = new FinancialPolicyService();
        $policy = $policyService->evaluateForServiceCase($serviceCaseId);
        $inheritedDocumentType = trim((string) ($policy['fiscal_document_type'] ?? ''));
        $documentType = $inheritedDocumentType !== '' ? $inheritedDocumentType : trim($requestedDocumentType);
        if (! isset(self::DOCUMENT_TYPES[$documentType])) {
            throw new RuntimeException('La cotización no define tipo de documento fiscal. Seleccione uno para continuar.');
        }

        $source = $db->table('service_cases sc')
            ->select('sc.*, q.total AS quotation_total, q.payment_term_id, pt.code AS payment_term_code, pt.name AS payment_term_name, pt.requires_advance, pt.minimum_advance_percentage, wo.id AS work_order_id, wo.code AS work_order_code, wo.status AS work_order_status')
            ->join('quotations q', 'q.id = sc.accepted_quotation_id', 'inner')
            ->join('payment_terms pt', 'pt.id = q.payment_term_id', 'left')
            ->join('work_orders wo', 'wo.service_case_id = sc.id AND wo.delete_date IS NULL', 'left')
            ->where('sc.id', $serviceCaseId)
            ->orderBy('wo.id', 'DESC')
            ->get(1)->getRowArray();

        if ($source === null) {
            throw new RuntimeException('No fue posible reconstruir la información comercial del Expediente.');
        }

        $preOperationalBilling = $policy['status'] === 'awaiting_advance';
        $postOperationalBilling = ($source['work_order_status'] ?? null) === 'closed';
        if (! $preOperationalBilling && ! $postOperationalBilling) {
            throw new RuntimeException('La política financiera todavía no requiere preparar facturación en este punto del proceso.');
        }

        $total = round((float) $source['quotation_total'], 2);
        $now = date('Y-m-d H:i:s');
        $model = new BillingCaseModel();
        $db->transBegin();
        try {
            $billingCaseId = $model->insert([
                'uuid' => $this->uuidV4(),
                'code' => $model->nextCode(),
                'origin_type' => 'quotation',
                'service_case_id' => $serviceCaseId,
                'work_order_id' => ! empty($source['work_order_id']) ? (int) $source['work_order_id'] : null,
                'quotation_id' => (int) $source['accepted_quotation_id'],
                'customer_id' => (int) $source['customer_id'],
                'payment_term_id' => ! empty($source['payment_term_id']) ? (int) $source['payment_term_id'] : null,
                'document_type' => $documentType,
                'status' => 'draft',
                'currency_code' => 'USD',
                'quotation_total_snapshot' => $total,
                'invoiceable_amount' => $total,
                'paid_amount' => 0,
                'balance_amount' => $total,
                'payment_term_code_snapshot' => $source['payment_term_code'] ?? null,
                'payment_term_name_snapshot' => $source['payment_term_name'] ?? null,
                'notes' => trim($notes) !== '' ? trim($notes) : null,
                'prepared_by_user_id' => session('auth_user_id') ?: null,
                'prepared_at' => $now,
                'entry_user' => $this->actor(),
            ], true);
            if ($billingCaseId === false) {
                throw new RuntimeException('No fue posible crear la preparación de facturación.');
            }

            $this->createSchedule((int) $billingCaseId, $total, (bool) ($source['requires_advance'] ?? false), (float) ($source['minimum_advance_percentage'] ?? 0));

            $db->table('service_case_financial_policies')->where('service_case_id', $serviceCaseId)->update([
                'fiscal_document_type' => $documentType,
                'modify_user' => $this->actor(),
                'modify_date' => $now,
            ]);
            $db->table('service_cases')->where('id', $serviceCaseId)->update([
                'billing_status' => 'preparation',
                'next_action_code' => $preOperationalBilling ? 'billing.advance_prepare' : 'billing.review',
                'next_action_label' => $preOperationalBilling ? 'Preparar facturación de anticipo' : 'Revisar preparación de facturación',
                'modify_user' => $this->actor(),
                'modify_date' => $now,
            ]);
            $db->table('service_case_events')->insert([
                'service_case_id' => $serviceCaseId,
                'event_code' => 'billing.preparation_created',
                'title' => 'Preparación de facturación creada',
                'description' => self::DOCUMENT_TYPES[$documentType] . ' · Total base $' . number_format($total, 2) . ($preOperationalBilling ? ' · Preparación anticipada por política financiera.' : ''),
                'entity_type' => 'billing_case',
                'entity_id' => (int) $billingCaseId,
                'occurred_at' => $now,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);
            $db->transCommit();
            return (int) $billingCaseId;
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function createSchedule(int $billingCaseId, float $total, bool $requiresAdvance, float $advancePercentage): void
    {
        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        if ($requiresAdvance && $advancePercentage > 0 && $advancePercentage < 100) {
            $advance = round($total * ($advancePercentage / 100), 2);
            $rows = [
                ['sequence' => 1, 'concept' => 'Anticipo comercial', 'installment_type' => 'advance', 'percentage' => $advancePercentage, 'amount' => $advance, 'trigger_event' => 'commercial_acceptance'],
                ['sequence' => 2, 'concept' => 'Saldo final', 'installment_type' => 'balance', 'percentage' => 100 - $advancePercentage, 'amount' => round($total - $advance, 2), 'trigger_event' => 'work_order_completed'],
            ];
        } elseif ($requiresAdvance && $advancePercentage >= 100) {
            $rows = [['sequence' => 1, 'concept' => 'Pago anticipado', 'installment_type' => 'advance', 'percentage' => 100, 'amount' => $total, 'trigger_event' => 'commercial_acceptance']];
        } else {
            $rows = [['sequence' => 1, 'concept' => 'Pago según condición comercial', 'installment_type' => 'scheduled', 'percentage' => 100, 'amount' => $total, 'trigger_event' => 'invoice_issue']];
        }
        foreach ($rows as $row) {
            $db->table('billing_payment_schedule')->insert($row + ['billing_case_id' => $billingCaseId, 'status' => 'pending', 'entry_user' => $this->actor(), 'entry_date' => $now]);
        }
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
