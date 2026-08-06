<?php

namespace App\Services;

use App\Models\ServiceCaseModel;
use RuntimeException;

class ServiceCaseService
{
    private const MILESTONES = [
        ['quotation_accepted', 'Cotización aceptada'],
        ['billing_plan_defined', 'Plan de facturación definido'],
        ['advance_requirement_resolved', 'Anticipo resuelto o no requerido'],
        ['coordination_authorized', 'Coordinación autorizada'],
        ['work_order_completed', 'Orden de trabajo ejecutada'],
        ['customer_acceptance_signed', 'Aceptación de finalización firmada'],
        ['operational_closure_approved', 'Cierre operativo aprobado'],
        ['billing_completed', 'Facturación completada'],
        ['collection_completed', 'Cobro completado'],
        ['case_archived', 'Expediente archivado'],
    ];

    public function createFromAcceptedQuotation(int $quotationId): int
    {
        $db = db_connect();
        $quotation = $db->table('quotations')->where('id', $quotationId)->where('delete_date', null)->get()->getRowArray();

        if ($quotation === null) {
            throw new RuntimeException('Cotización no encontrada.');
        }
        if ($quotation['status'] !== 'accepted') {
            throw new RuntimeException('Solo una cotización aceptada puede originar un expediente de servicio.');
        }

        $existing = (new ServiceCaseModel())->where('accepted_quotation_id', $quotationId)->first();
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $db->transStart();
        $model = new ServiceCaseModel();
        $caseId = $model->insert([
            'uuid' => $this->uuidV4(),
            'code' => $this->nextCode(),
            'customer_id' => (int) $quotation['customer_id'],
            'commercial_request_id' => $quotation['commercial_request_id'] ?: null,
            'accepted_quotation_id' => $quotationId,
            'responsible_user_id' => $quotation['assigned_user_id'] ?: null,
            'current_stage' => 'commercial_acceptance',
            'operational_status' => 'not_started',
            'billing_status' => 'pending_definition',
            'collection_status' => 'not_applicable',
            'health_score' => 100,
            'opened_at' => date('Y-m-d H:i:s'),
        ], true);

        if ($caseId === false) {
            throw new RuntimeException('No fue posible crear el expediente de servicio.');
        }

        foreach (self::MILESTONES as $sequence => [$code, $label]) {
            $db->table('service_case_milestones')->insert([
                'service_case_id' => $caseId,
                'milestone_code' => $code,
                'milestone_label' => $label,
                'status' => $code === 'quotation_accepted' ? 'completed' : 'pending',
                'required' => 1,
                'sequence' => ($sequence + 1) * 10,
                'completed_at' => $code === 'quotation_accepted' ? date('Y-m-d H:i:s') : null,
                'completed_by' => $code === 'quotation_accepted' ? $this->actor() : null,
                'evidence_entity_type' => $code === 'quotation_accepted' ? 'quotation' : null,
                'evidence_entity_id' => $code === 'quotation_accepted' ? $quotationId : null,
                'entry_user' => $this->actor(),
                'entry_date' => date('Y-m-d H:i:s'),
            ]);
        }

        $db->table('service_case_events')->insert([
            'service_case_id' => $caseId,
            'event_code' => 'service_case.created',
            'title' => 'Expediente de servicio creado',
            'description' => 'El expediente fue creado automáticamente desde la cotización aceptada ' . $quotation['code'] . '.',
            'entity_type' => 'quotation',
            'entity_id' => $quotationId,
            'occurred_at' => date('Y-m-d H:i:s'),
            'entry_user' => $this->actor(),
            'entry_date' => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();
        if (! $db->transStatus()) {
            throw new RuntimeException('No fue posible completar la creación del expediente.');
        }

        (new ProcessEngineService())->evaluate((int) $caseId);
        return (int) $caseId;
    }

    private function nextCode(): string
    {
        $year = date('Y');
        $last = db_connect()->table('service_cases')->like('code', 'CAS-' . $year . '-', 'after')->orderBy('id', 'DESC')->get()->getRowArray();
        $sequence = $last ? ((int) substr((string) $last['code'], -6)) + 1 : 1;
        return sprintf('CAS-%s-%06d', $year, $sequence);
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: 'system');
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
