<?php

namespace App\Services;

use App\Models\ServiceCaseModel;
use RuntimeException;

class ProcessEngineService
{
    public function evaluate(int $serviceCaseId): array
    {
        $case = (new ServiceCaseModel())->find($serviceCaseId);
        if ($case === null) {
            throw new RuntimeException('Expediente de servicio no encontrado.');
        }

        $db = db_connect();
        $milestones = $db->table('service_case_milestones')
            ->where('service_case_id', $serviceCaseId)
            ->where('delete_date', null)
            ->orderBy('sequence')
            ->get()
            ->getResultArray();

        $openExceptions = $db->table('process_exceptions')
            ->where('service_case_id', $serviceCaseId)
            ->where('status', 'open')
            ->where('delete_date', null)
            ->get()
            ->getResultArray();

        $coordination = null;
        if ($db->tableExists('coordination_plans')) {
            $coordination = $db->table('coordination_plans')
                ->where('service_case_id', $serviceCaseId)
                ->where('delete_date', null)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
        }

        $workOrder = null;
        if ($db->tableExists('work_orders')) {
            $workOrder = $db->table('work_orders')
                ->where('service_case_id', $serviceCaseId)
                ->where('delete_date', null)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
        }

        if ($coordination !== null && $coordination['status'] === 'approved') {
            $this->completeMilestone(
                $serviceCaseId,
                'coordination_authorized',
                'coordination_plan',
                (int) $coordination['id'],
                'Coordinación operativa aprobada.'
            );
        }

        if ($workOrder !== null && in_array($workOrder['status'], ['completed', 'finished', 'closed'], true)) {
            $this->completeMilestone(
                $serviceCaseId,
                'work_order_completed',
                'work_order',
                (int) $workOrder['id'],
                'Orden de Trabajo ejecutada.'
            );
        }

        $milestones = $db->table('service_case_milestones')
            ->where('service_case_id', $serviceCaseId)
            ->where('delete_date', null)
            ->orderBy('sequence')
            ->get()
            ->getResultArray();

        $next = null;
        foreach ($milestones as $milestone) {
            if ((int) $milestone['required'] === 1 && $milestone['status'] !== 'completed') {
                $next = $milestone;
                break;
            }
        }

        $penalty = 0;
        foreach ($openExceptions as $exception) {
            $penalty += match ($exception['severity']) {
                'critical' => 35,
                'high' => 20,
                'medium' => 10,
                default => 5,
            };
        }
        $healthScore = max(0, 100 - $penalty);

        $currentStage = (string) $case['current_stage'];
        $operationalStatus = (string) $case['operational_status'];
        $nextActionCode = $next['milestone_code'] ?? 'case_review';
        $nextActionLabel = $next['milestone_label'] ?? 'Revisar expediente';

        if ($coordination !== null) {
            $currentStage = 'coordination';
            $operationalStatus = $coordination['status'] === 'approved' ? 'coordinated' : 'planning';
            $nextActionCode = $coordination['status'] === 'approved' ? 'work_order.generate' : 'coordination.complete';
            $nextActionLabel = $coordination['status'] === 'approved' ? 'Generar Orden de Trabajo' : 'Completar coordinación operativa';
        }

        if ($workOrder !== null) {
            $currentStage = 'execution';
            [$nextActionCode, $nextActionLabel, $operationalStatus] = match ($workOrder['status']) {
                'prepared' => ['work_order.issue', 'Emitir Orden de Trabajo', 'work_order_prepared'],
                'issued' => ['work_order.start', 'Iniciar ejecución de Orden de Trabajo', 'scheduled'],
                'in_progress', 'working' => ['work_order.log', 'Registrar avance operativo', 'in_progress'],
                'completed', 'finished' => ['customer_acceptance_signed', 'Registrar aceptación de finalización', 'completed_pending_acceptance'],
                'closed' => ['operational_closure_approved', 'Aprobar cierre operativo', 'completed'],
                default => ['work_order.review', 'Revisar Orden de Trabajo', (string) $workOrder['status']],
            };
        }

        $now = date('Y-m-d H:i:s');
        if ($currentStage !== (string) $case['current_stage']) {
            $db->table('service_case_stage_history')->insert([
                'service_case_id' => $serviceCaseId,
                'from_stage' => $case['current_stage'],
                'to_stage' => $currentStage,
                'reason' => 'Avance sincronizado automáticamente por Process Engine.',
                'changed_by' => $this->actor(),
                'changed_at' => $now,
            ]);
        }

        (new ServiceCaseModel())->update($serviceCaseId, [
            'current_stage' => $currentStage,
            'operational_status' => $operationalStatus,
            'health_score' => $healthScore,
            'next_action_code' => $nextActionCode,
            'next_action_label' => $nextActionLabel,
        ]);

        return [
            'case' => array_merge($case, [
                'current_stage' => $currentStage,
                'operational_status' => $operationalStatus,
            ]),
            'milestones' => $milestones,
            'exceptions' => $openExceptions,
            'health_score' => $healthScore,
            'next_action' => [
                'code' => $nextActionCode,
                'label' => $nextActionLabel,
                'blocked' => $openExceptions !== [],
                'blocking_reasons' => array_column($openExceptions, 'title'),
            ],
            'coordination' => $coordination,
            'work_order' => $workOrder,
        ];
    }

    private function completeMilestone(int $serviceCaseId, string $code, string $entityType, int $entityId, string $notes): void
    {
        $db = db_connect();
        $milestone = $db->table('service_case_milestones')
            ->where('service_case_id', $serviceCaseId)
            ->where('milestone_code', $code)
            ->where('delete_date', null)
            ->get()
            ->getRowArray();

        if ($milestone === null || $milestone['status'] === 'completed') {
            return;
        }

        $db->table('service_case_milestones')->where('id', (int) $milestone['id'])->update([
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'completed_by' => $this->actor(),
            'evidence_entity_type' => $entityType,
            'evidence_entity_id' => $entityId,
            'notes' => $notes,
            'modify_user' => $this->actor(),
            'modify_date' => date('Y-m-d H:i:s'),
        ]);
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: 'system');
    }
}
