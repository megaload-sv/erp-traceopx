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
        $missing = [];

        if (empty($plan['scheduled_start_at'])) {
            $missing[] = 'Fecha programada';
        }
        if (empty($plan['estimated_end_at'])) {
            $missing[] = 'Fin estimado';
        }
        if (! empty($plan['scheduled_start_at']) && ! empty($plan['estimated_end_at'])
            && strtotime((string) $plan['estimated_end_at']) <= strtotime((string) $plan['scheduled_start_at'])) {
            $missing[] = 'Rango de programación válido';
        }
        if (trim((string) ($plan['location'] ?? '')) === '') {
            $missing[] = 'Lugar de ejecución';
        }
        if (trim((string) ($plan['scope_notes'] ?? '')) === '') {
            $missing[] = 'Alcance operativo';
        }

        foreach ($resourceWorkspace['missing_required'] as $requirement) {
            $missing[] = $requirement;
        }

        $equipment = $db->table('coordination_plan_equipment cpe')
            ->select('cpe.id, cpe.equipment_id, cpe.assignment_status, equipment.code, equipment.name, equipment.operational_status, equipment.maintenance_status')
            ->join('equipment', 'equipment.id = cpe.equipment_id')
            ->where('cpe.coordination_plan_id', $planId)
            ->where('cpe.delete_date', null)
            ->get()->getResultArray();

        foreach ($equipment as $item) {
            if ($item['operational_status'] !== 'available') {
                $missing[] = $item['code'] . ' · maquinaria no disponible';
            }
            if (! in_array($item['maintenance_status'], ['ok', 'preventive_due'], true)) {
                $missing[] = $item['code'] . ' · mantenimiento no compatible';
            }
        }

        $missing = array_values(array_unique($missing));

        return [
            'plan' => $plan,
            'equipment' => $equipment,
            'resource_workspace' => $resourceWorkspace,
            'missing' => $missing,
            'ready' => $missing === [] && $plan['status'] === 'draft',
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
            // Bloqueo y revalidación de maquinaria para impedir aprobaciones concurrentes.
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
                'description' => 'Los recursos humanos y la maquinaria fueron convertidos en asignaciones formales para la misión.',
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
