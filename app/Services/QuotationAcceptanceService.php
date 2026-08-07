<?php

namespace App\Services;

use App\Models\QuotationModel;
use RuntimeException;
use Throwable;

class QuotationAcceptanceService
{
    public function accept(int $quotationId, array $data): int
    {
        $db = db_connect();
        $quotation = $db->table('quotations')->where('id', $quotationId)->where('delete_date', null)->get()->getRowArray();

        if ($quotation === null) {
            throw new RuntimeException('Cotización no encontrada.');
        }

        (new QuotationWorkflowService())->assertCanTransition((string) $quotation['status'], 'accepted');

        if ($db->table('quotation_items')->where('quotation_id', $quotationId)->where('delete_date', null)->countAllResults() === 0) {
            throw new RuntimeException('La cotización debe tener al menos un concepto antes de ser aceptada.');
        }
        if (empty($quotation['customer_id']) || empty($quotation['assigned_user_id']) || empty($quotation['payment_term_id'])) {
            throw new RuntimeException('Complete cliente, agente comercial y forma de pago antes de aceptar la cotización.');
        }

        $acceptedByName = trim((string) ($data['accepted_by_name'] ?? ''));
        $contactId = ! empty($data['customer_contact_id']) ? (int) $data['customer_contact_id'] : null;

        if ($acceptedByName === '') {
            throw new RuntimeException('Ingrese o seleccione la persona que aceptó la cotización.');
        }

        if ($contactId !== null) {
            $contact = $db->table('customer_contacts')
                ->where('id', $contactId)
                ->where('customer_id', $quotation['customer_id'])
                ->where('status', 1)
                ->where('delete_date', null)
                ->get()
                ->getRowArray();

            if ($contact === null) {
                throw new RuntimeException('El contacto seleccionado no pertenece al cliente de la cotización.');
            }
        }

        $existingAcceptance = $db->table('quotation_acceptances')->where('quotation_id', $quotationId)->where('delete_date', null)->get()->getRowArray();
        if ($existingAcceptance !== null) {
            $existingCase = $db->table('service_cases')->where('accepted_quotation_id', $quotationId)->where('delete_date', null)->get()->getRowArray();
            if ($existingCase !== null) {
                return (int) $existingCase['id'];
            }
        }

        $paymentTerm = $db->table('payment_terms')->where('id', $quotation['payment_term_id'])->get()->getRowArray();
        $billingRules = (new ServiceCaseBillingRuleService())->derive($paymentTerm);
        $actor = (string) (session('auth_user_email') ?: 'system');
        $now = date('Y-m-d H:i:s');

        $db->transBegin();
        try {
            $db->table('quotation_acceptances')->insert([
                'quotation_id' => $quotationId,
                'customer_contact_id' => $contactId,
                'accepted_at' => (string) ($data['accepted_at'] ?: $now),
                'accepted_by_name' => $acceptedByName,
                'acceptance_type' => (string) $data['acceptance_type'],
                'fiscal_document_type' => (string) $data['fiscal_document_type'],
                'evidence_path' => $data['evidence_path'] ?? null,
                'evidence_original_name' => $data['evidence_original_name'] ?? null,
                'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
                'authorized_by_user_id' => session('auth_user_id') ?: null,
                'entry_user' => $actor,
                'entry_date' => $now,
            ]);

            (new QuotationModel())->update($quotationId, ['status' => 'accepted']);
            $caseId = (new ServiceCaseService())->createFromAcceptedQuotation($quotationId);

            $db->table('service_case_billing_profiles')->insert([
                'service_case_id' => $caseId,
                'payment_term_id' => $quotation['payment_term_id'],
                'fiscal_document_type' => (string) $data['fiscal_document_type'],
                ...$billingRules,
                'entry_user' => $actor,
                'entry_date' => $now,
            ]);

            $db->table('service_case_events')->insert([
                'service_case_id' => $caseId,
                'event_code' => 'quotation.accepted',
                'title' => 'Cotización aceptada',
                'description' => 'Aceptada por ' . $acceptedByName . ' mediante ' . (string) $data['acceptance_type'] . '.',
                'entity_type' => 'quotation',
                'entity_id' => $quotationId,
                'occurred_at' => $now,
                'entry_user' => $actor,
                'entry_date' => $now,
            ]);

            if (! $db->transCommit()) {
                throw new RuntimeException('No fue posible confirmar la aceptación de la cotización.');
            }

            (new ProcessEngineService())->evaluate($caseId);
            (new ActivityService())->record('quotation', $quotationId, 'quotation.accepted', 'Cotización aceptada', 'Se creó el expediente de servicio asociado.');
            return $caseId;
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
