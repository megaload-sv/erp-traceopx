<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class BillingPaymentService
{
    public function workspace(int $billingCaseId): array
    {
        $db = db_connect();
        $case = $this->billingCase($billingCaseId);
        $payments = $db->table('billing_payments bp')
            ->select('bp.*, bps.concept AS schedule_concept, bps.sequence AS schedule_sequence')
            ->join('billing_payment_schedule bps', 'bps.id = bp.payment_schedule_id', 'left')
            ->where('bp.billing_case_id', $billingCaseId)
            ->orderBy('bp.payment_date', 'DESC')
            ->orderBy('bp.id', 'DESC')
            ->get()->getResultArray();

        $schedule = $db->table('billing_payment_schedule')
            ->where('billing_case_id', $billingCaseId)
            ->orderBy('sequence')
            ->get()->getResultArray();

        foreach ($schedule as &$row) {
            $appliedRow = $db->table('billing_payments')
                ->selectSum('amount', 'total')
                ->where('billing_case_id', $billingCaseId)
                ->where('payment_schedule_id', (int) $row['id'])
                ->where('status', 'confirmed')
                ->get()->getRowArray();
            $applied = round((float) ($appliedRow['total'] ?? 0), 2);
            $expected = round((float) $row['amount'], 2);
            $row['applied_amount'] = $applied;
            $row['remaining_amount'] = max(0, round($expected - $applied, 2));
        }
        unset($row);

        $target = $this->targetAmount($billingCaseId, $case);
        $paid = $this->confirmedTotal($billingCaseId);
        $readiness = $this->paymentReadiness($case, $schedule, $target);

        return [
            'billingCase' => $case,
            'payments' => $payments,
            'schedule' => $schedule,
            'target_amount' => $target,
            'paid_amount' => $paid,
            'balance_amount' => max(0, round($target - $paid, 2)),
            'payment_readiness' => $readiness,
        ];
    }

    public function registerConfirmed(int $billingCaseId, array $input): int
    {
        $db = db_connect();
        $amount = round((float) ($input['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new RuntimeException('El monto del pago debe ser mayor que cero.');
        }

        $method = trim((string) ($input['payment_method'] ?? ''));
        if ($method === '') {
            throw new RuntimeException('Indique la forma de pago utilizada.');
        }

        $mhCode = strtoupper(trim((string) ($input['mh_payment_code'] ?? '')));
        if ($mhCode !== '' && ! preg_match('/^(0[1-9]|1[0-4]|99)$/', $mhCode)) {
            throw new RuntimeException('El código MH de forma de pago debe estar entre 01-14 o ser 99.');
        }

        $reference = trim((string) ($input['reference'] ?? ''));
        if (mb_strlen($reference) > 50) {
            throw new RuntimeException('La referencia del pago no puede exceder 50 caracteres.');
        }

        $electronicNumber = trim((string) ($input['electronic_payment_number'] ?? ''));
        if (mb_strlen($electronicNumber) > 100) {
            throw new RuntimeException('El número de pago electrónico no puede exceder 100 caracteres.');
        }

        $paymentDateRaw = trim((string) ($input['payment_date'] ?? ''));
        $paymentTimestamp = $paymentDateRaw !== '' ? strtotime($paymentDateRaw) : time();
        if ($paymentTimestamp === false) {
            throw new RuntimeException('La fecha del pago no es válida.');
        }
        $paymentDate = date('Y-m-d H:i:s', $paymentTimestamp);

        $scheduleId = ! empty($input['payment_schedule_id']) ? (int) $input['payment_schedule_id'] : null;
        $actor = $this->actor();
        $now = date('Y-m-d H:i:s');

        $db->transBegin();
        try {
            $case = $db->query('SELECT * FROM billing_cases WHERE id = ? AND delete_date IS NULL FOR UPDATE', [$billingCaseId])->getRowArray();
            if ($case === null) {
                throw new RuntimeException('Preparación de facturación no encontrada.');
            }

            $scheduleRows = $db->table('billing_payment_schedule')
                ->where('billing_case_id', $billingCaseId)
                ->orderBy('sequence')
                ->get()->getResultArray();
            $target = $this->targetAmount($billingCaseId, $case);
            $readiness = $this->paymentReadiness($case, $scheduleRows, $target);
            if (! $readiness['allowed']) {
                throw new RuntimeException('No es posible registrar pagos todavía: ' . implode(' ', $readiness['issues']));
            }

            if ($scheduleId !== null) {
                $schedule = $db->table('billing_payment_schedule')
                    ->where('id', $scheduleId)
                    ->where('billing_case_id', $billingCaseId)
                    ->get()->getRowArray();
                if ($schedule === null) {
                    throw new RuntimeException('La cuota del calendario financiero no pertenece a esta preparación.');
                }
            }

            $paidBefore = $this->confirmedTotal($billingCaseId);
            $balanceBefore = max(0, round($target - $paidBefore, 2));
            if ($amount > $balanceBefore + 0.009) {
                throw new RuntimeException('El pago excede el saldo pendiente de $' . number_format($balanceBefore, 2) . '.');
            }

            $document = $db->table('dte_documents')->where('billing_case_id', $billingCaseId)->get()->getRowArray();
            $uuid = strtoupper($this->uuidV4());
            $db->table('billing_payments')->insert([
                'uuid' => $uuid,
                'billing_case_id' => $billingCaseId,
                'service_case_id' => (int) $case['service_case_id'],
                'dte_document_id' => $document !== null ? (int) $document['id'] : null,
                'payment_schedule_id' => $scheduleId,
                'payment_method' => $method,
                'mh_payment_code' => $mhCode !== '' ? $mhCode : null,
                'amount' => $amount,
                'reference' => $reference !== '' ? $reference : null,
                'electronic_payment_number' => $electronicNumber !== '' ? $electronicNumber : null,
                'payment_date' => $paymentDate,
                'status' => 'confirmed',
                'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
                'confirmed_by' => $actor,
                'confirmed_at' => $now,
                'entry_user' => $actor,
                'entry_date' => $now,
            ]);
            $paymentId = (int) $db->insertID();

            $paid = round($paidBefore + $amount, 2);
            $balance = max(0, round($target - $paid, 2));
            $db->table('billing_cases')->where('id', $billingCaseId)->update([
                'invoiceable_amount' => $target,
                'paid_amount' => $paid,
                'balance_amount' => $balance,
                'modify_user' => $actor,
                'modify_date' => $now,
            ]);

            $db->table('service_case_financial_policies')->where('service_case_id', (int) $case['service_case_id'])->update([
                'confirmed_paid_amount' => $paid,
                'modify_user' => $actor,
                'modify_date' => $now,
            ]);

            $this->refreshScheduleStatuses($billingCaseId, $now, $actor);

            $collectionStatus = $balance <= 0.009 ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
            $db->table('service_cases')->where('id', (int) $case['service_case_id'])->update([
                'collection_status' => $collectionStatus,
                'modify_user' => $actor,
                'modify_date' => $now,
            ]);

            $db->table('service_case_events')->insert([
                'service_case_id' => (int) $case['service_case_id'],
                'event_code' => 'payment.confirmed',
                'title' => 'Pago confirmado',
                'description' => $method . ' · $' . number_format($amount, 2)
                    . ($reference !== '' ? ' · Ref. ' . $reference : '')
                    . ' · Saldo $' . number_format($balance, 2),
                'entity_type' => 'billing_payment',
                'entity_id' => $paymentId,
                'occurred_at' => $now,
                'entry_user' => $actor,
                'entry_date' => $now,
            ]);

            $db->transCommit();

            (new FinancialPolicyService())->evaluateForServiceCase((int) $case['service_case_id']);
            return $paymentId;
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function confirmedTotalForServiceCase(int $serviceCaseId): float
    {
        if (! db_connect()->tableExists('billing_payments')) {
            return 0.0;
        }

        $row = db_connect()->table('billing_payments')
            ->selectSum('amount', 'total')
            ->where('service_case_id', $serviceCaseId)
            ->where('status', 'confirmed')
            ->get()->getRowArray();

        return round((float) ($row['total'] ?? 0), 2);
    }

    private function confirmedTotal(int $billingCaseId): float
    {
        $row = db_connect()->table('billing_payments')
            ->selectSum('amount', 'total')
            ->where('billing_case_id', $billingCaseId)
            ->where('status', 'confirmed')
            ->get()->getRowArray();
        return round((float) ($row['total'] ?? 0), 2);
    }

    private function targetAmount(int $billingCaseId, array $case): float
    {
        $document = db_connect()->table('dte_documents')
            ->select('amount_payable')
            ->where('billing_case_id', $billingCaseId)
            ->get()->getRowArray();

        $dteAmount = round((float) ($document['amount_payable'] ?? 0), 2);
        return $dteAmount > 0 ? $dteAmount : round((float) $case['invoiceable_amount'], 2);
    }

    private function paymentReadiness(array $case, array $schedule, float $target): array
    {
        $issues = [];

        if ($target <= 0) {
            $issues[] = 'Debe existir un monto total por cobrar mayor que cero.';
        }
        if (empty($case['payment_term_id']) && trim((string) ($case['payment_term_name_snapshot'] ?? '')) === '') {
            $issues[] = 'Debe definirse la condición comercial de pago.';
        }
        if ($schedule === []) {
            $issues[] = 'Debe existir el calendario financiero esperado.';
        }
        if (trim((string) ($case['currency_code'] ?? '')) === '') {
            $issues[] = 'Debe definirse la moneda de la operación.';
        }

        return [
            'allowed' => $issues === [],
            'issues' => $issues,
        ];
    }

    private function refreshScheduleStatuses(int $billingCaseId, string $now, string $actor): void
    {
        $db = db_connect();
        $schedules = $db->table('billing_payment_schedule')->where('billing_case_id', $billingCaseId)->get()->getResultArray();
        foreach ($schedules as $schedule) {
            $row = $db->table('billing_payments')
                ->selectSum('amount', 'total')
                ->where('billing_case_id', $billingCaseId)
                ->where('payment_schedule_id', (int) $schedule['id'])
                ->where('status', 'confirmed')
                ->get()->getRowArray();
            $applied = round((float) ($row['total'] ?? 0), 2);
            $expected = round((float) $schedule['amount'], 2);
            $status = $applied <= 0 ? 'pending' : ($applied + 0.009 >= $expected ? 'paid' : 'partial');
            $db->table('billing_payment_schedule')->where('id', (int) $schedule['id'])->update([
                'status' => $status,
                'modify_user' => $actor,
                'modify_date' => $now,
            ]);
        }
    }

    private function billingCase(int $billingCaseId): array
    {
        $row = db_connect()->table('billing_cases bc')
            ->select('bc.*, sc.code AS service_case_code, c.business_name, q.code AS quotation_code, d.id AS dte_document_id, d.amount_payable AS dte_amount_payable')
            ->join('service_cases sc', 'sc.id = bc.service_case_id')
            ->join('customers c', 'c.id = bc.customer_id', 'left')
            ->join('quotations q', 'q.id = bc.quotation_id', 'left')
            ->join('dte_documents d', 'd.billing_case_id = bc.id', 'left')
            ->where('bc.id', $billingCaseId)
            ->where('bc.delete_date', null)
            ->get()->getRowArray();
        if ($row === null) {
            throw new RuntimeException('Preparación de facturación no encontrada.');
        }
        return $row;
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
