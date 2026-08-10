<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class CoordinationApprovalService
{
    public function checklist(int $planId): array
    {
        $db = db_connect();
        $plan = $db->table('coordination_plans')
            ->where('id', $planId)
            ->where('delete_date', null)
            ->get()->getRowArray();

        if ($plan === null) {
            throw new RuntimeException('Plan de coordinación no encontrado.');
        }

        $resourceWorkspace = (new ResourceAllocationService())->workspace($planId);
        $equipment = $db->table('coordination_plan_equipment cpe')
            ->select('cpe.id, cpe.equipment_id, cpe.assignment_status, equipment.code, equipment.name, equipment.operational_status, equipment.maintenance_status')
            ->join('equipment', 'equipment.id = cpe.equipment_id')
            ->where('cpe.coordination_plan_id', $planId)
            ->where('cpe.delete_date', null)
            ->get()->getResultArray();

        $financialGate = (new FinancialPolicyService())->coordinationGate((int) $plan['service_case_id']);

        $scheduled = ! empty($plan['scheduled_start_at']);
        $estimated = ! empty($plan['estimated_end_at']);
        $validRange = $scheduled && $estimated
            && strtotime((string) $plan['estimated_end_at']) > strtotime((string) $plan['scheduled_start_at']);
        $hasLocation = trim((string) ($plan['location'] ?? '')) !== '';
        $hasScope = trim((string) ($plan['scope_notes'] ?? '')) !== '';
        $hasEquipment = $equipment !== [];
        $resourcesReady = (bool) $resourceWorkspace['ready_for_approval'];
        $approved = $plan['status'] === 'approved';

        $equipmentOperational = $hasEquipment;
        $maintenanceCompatible = $hasEquipment;
        foreach ($equipment as $item) {
            $validOperationalStates = $approved ? ['reserved', 'assigned', 'in_operation'] : ['available'];
            if (! in_array($item['operational_status'], $validOperationalStates, true)) {
                $equipmentOperational = false;
            }
            if (! in_array($item['maintenance_status'], ['ok', 'preventive_due'], true)) {
                $maintenanceCompatible = false;
            }
        }

        $checks = [
            ['key' => 'financial_gate', 'label' => 'Política financiera habilita coordinación', 'complete' => $approved || $financialGate['allowed']],
            ['key' => 'schedule', 'label' => 'Programación definida', 'complete' => $scheduled && $estimated && $validRange],
            ['key' => 'location', 'label' => 'Lugar de ejecución', 'complete' => $hasLocation],
            ['key' => 'scope', 'label' => 'Alcance operativo', 'complete' => $hasScope],
            ['key' => 'equipment', 'label' => 'Maquinaria prevista', 'complete' => $hasEquipment],
            ['key' => 'equipment_availability', 'label' => $approved ? 'Maquinaria reservada' : 'Maquinaria disponible', 'complete' => $equipmentOperational],
            ['key' => 'maintenance', 'label' => 'Mantenimiento compatible', 'complete' => $maintenanceCompatible],
            ['key' => 'resources', 'label' => 'Personal obligatorio cubierto', 'complete' => $resourcesReady],
            ['key' => 'leader', 'label' => 'Responsable de misión', 'complete' => ! empty($resourceWorkspace['mission_leader'])],
        ];

        $missing = [];
        foreach ($checks as $check) {
            if (! $check['complete']) {
                $missing[] = $check['key'] === 'financial_gate' && $financialGate['reason']
                    ? $financialGate['reason']
                    : $check['label'];
            }
        }
        foreach ($resourceWorkspace['missing_required'] as $requirement) {
            $missing[] = $requirement;
        }
        $missing = array_values(array_unique($missing));

        $completedChecks = count(array_filter($checks, static fn(array $check): bool => $check['complete']));
        $completionPercent = $checks === [] ? 0 : (int) round(($completedChecks / count($checks)) * 100);

        return [
            'plan' => $plan,
            'equipment' => $equipment,
            'resource_workspace' => $resourceWorkspace,
            'financial_gate' => $financialGate,
            'checks' => $checks,
            'missing' => $missing,
            'ready' => $missing === [] && $plan['status'] === 'draft',
            'approved' => $approved,
            'completion_percent' => $completionPercent,
        ];
    }

    public function approve(int $planId): void
    {
        $db = db_connect();
        $check = $this->checklist($planId);

        if ($check['plan']['status'] !== 'draft') {
            throw new RuntimeException('Esta coordinación ya no se encuentra en preparación.');
        }
        if ($check['missing'] !== []) {
            throw new RuntimeException('La coordinación todavía tiene requisitos pendientes: ' . implode(', ', $check['missing']) . '.');
        }

        $db->transBegin();
        try {
            foreach ($check['equipment'] as $item) {
                $locked = $db->query(
                    'SELECT id, code, name, operational_status, maintenance_status FROM equipment WHERE id = ? FOR UPDATE',
                    [(int) $item['equipment_id']]
                )->getRowArray();

                if ($locked === null
                    || $locked['operational_status'] !== 'available'
                    || ! in_array($locked['maintenance_status'], ['ok', 'preventive_due'], true)) {
                    throw new RuntimeException(($item['code'] ?? 'Equipo') . ' dejó de estar disponible antes de aprobar la coordinación.');
                }

                $db->table('equipment')->where('id', (int) $item['equipment_id'])->update([
                    'operational_status' => 'reserved',
                    'modify_user' => $this->actor(),
                    'modify_date' => date('Y-m-d H:i:s'),
                ]);

                $db->table('coordination_plan_equipment')->where('id', (int) $item['id'])->update([
                    'assignment_status' => 'reserved',
                    'modify_user' => $this->actor(),
                    'modify_date' => date('Y-m-d H:i:s'),
                ]);
            }

            $allocations = $db->table('coordination_resource_allocations')
                ->where('coordination_plan_id', $planId)
                ->where('status', 1)
                ->where('delete_date', null)
                ->where('allocation_status', 'reserved')
                ->get()->getResultArray();

            foreach ($allocations as $allocation) {
                $db->table('coordination_resource_allocations')->where('id', (int) $allocation['id'])->update([
                    'allocation_status' => 'assigned',
                    'modify_user' => $this->actor(),
                    'modify_date' => date('Y-m-d H:i:s'),
                ]);

                $db->table('employees')->where('id', (int) $allocation['employee_id'])->update([
                    'availability_status' => 'assigned',
                    'modify_user' => $this->actor(),
                    'modify_date' => date('Y-m-d H:i:s'),
                ]);
            }

            $db->table('coordination_plans')->where('id', $planId)->update([
                'status' => 'approved',
                'approved_by_user_id' => session('auth_user_id') ?: null,
                'approved_at' => date('Y-m-d H:i:s'),
                'modify_user' => $this->actor(),
                'modify_date' => date('Y-m-d H:i:s'),
            ]);

            $db->table('service_case_events')->insert([
                'service_case_id' => (int) $check['plan']['service_case_id'],
                'event_code' => 'coordination.approved',
                'title' => 'Coordinación operativa aprobada',
                'description' => 'Los recursos humanos y la maquinaria fueron convertidos en asignaciones formales para la misión. La compuerta financiera se encontraba liberada.',
                'occurred_at' => date('Y-m-d H:i:s'),
                'entry_user' => $this->actor(),
                'entry_date' => date('Y-m-d H:i:s'),
            ]);

            (new ActivityService())->record(
                'coordination_plan',
                $planId,
                'coordination.approved',
                'Coordinación aprobada',
                'Recursos formalmente asignados y planificación bloqueada.'
            );

            $db->transCommit();
            (new ProcessEngineService())->evaluate((int) $check['plan']['service_case_id']);
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: 'system');
    }
}
