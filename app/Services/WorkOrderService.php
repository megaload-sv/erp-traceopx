<?php

namespace App\Services;

use App\Models\WorkOrderModel;
use RuntimeException;
use Throwable;

class WorkOrderService
{
    public function createFromCoordination(int $coordinationPlanId): int
    {
        $db = db_connect();
        $existing = $db->table('work_orders')
            ->where('coordination_plan_id', $coordinationPlanId)
            ->where('delete_date', null)
            ->get()->getRowArray();
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $plan = $db->table('coordination_plans cp')
            ->select('cp.*, service_cases.customer_id, service_cases.code AS service_case_code, quotations.subject AS quotation_subject')
            ->join('service_cases', 'service_cases.id = cp.service_case_id')
            ->join('quotations', 'quotations.id = service_cases.accepted_quotation_id', 'left')
            ->where('cp.id', $coordinationPlanId)
            ->where('cp.delete_date', null)
            ->get()->getRowArray();

        if ($plan === null) {
            throw new RuntimeException('Coordinación no encontrada.');
        }
        if ($plan['status'] !== 'approved') {
            throw new RuntimeException('La Orden de Trabajo solo puede generarse desde una coordinación aprobada.');
        }

        $equipment = $db->table('coordination_plan_equipment cpe')
            ->select('cpe.*, equipment.code, equipment.name')
            ->join('equipment', 'equipment.id = cpe.equipment_id')
            ->where('cpe.coordination_plan_id', $coordinationPlanId)
            ->where('cpe.delete_date', null)
            ->get()->getResultArray();
        if ($equipment === []) {
            throw new RuntimeException('La coordinación aprobada no contiene maquinaria asignada.');
        }

        $team = $db->table('coordination_resource_allocations cra')
            ->select('cra.*, employees.employee_code, employees.name AS employee_name, resource_roles.name AS role_name')
            ->join('employees', 'employees.id = cra.employee_id')
            ->join('resource_roles', 'resource_roles.id = cra.resource_role_id', 'left')
            ->where('cra.coordination_plan_id', $coordinationPlanId)
            ->where('cra.status', 1)
            ->where('cra.delete_date', null)
            ->whereIn('cra.allocation_status', ['assigned','working'])
            ->get()->getResultArray();
        if ($team === []) {
            throw new RuntimeException('La coordinación aprobada no contiene personal asignado.');
        }

        $missionLeaderId = null;
        foreach ($team as $member) {
            if ($member['allocation_type'] === 'mission_leader') {
                $missionLeaderId = (int) $member['employee_id'];
                break;
            }
        }
        if ($missionLeaderId === null) {
            throw new RuntimeException('La coordinación no tiene Responsable de Misión asignado.');
        }

        $db->transBegin();
        try {
            $workOrderId = (new WorkOrderModel())->insert([
                'uuid' => $this->uuidV4(),
                'code' => $this->nextCode(),
                'service_case_id' => (int) $plan['service_case_id'],
                'coordination_plan_id' => $coordinationPlanId,
                'customer_id' => ! empty($plan['customer_id']) ? (int) $plan['customer_id'] : null,
                'mission_leader_employee_id' => $missionLeaderId,
                'subject' => $plan['quotation_subject'] ?: ('Servicio ' . $plan['service_case_code']),
                'scheduled_start_at' => $plan['scheduled_start_at'],
                'estimated_end_at' => $plan['estimated_end_at'],
                'location' => $plan['location'],
                'location_reference' => $plan['location_reference'],
                'priority' => $plan['priority'],
                'scope_snapshot' => $plan['scope_notes'],
                'coordination_notes_snapshot' => $plan['coordination_notes'],
                'status' => 'prepared',
                'created_by_user_id' => session('auth_user_id') ?: null,
                'entry_user' => $this->actor(),
            ], true);

            if ($workOrderId === false) {
                throw new RuntimeException('No fue posible generar la Orden de Trabajo.');
            }

            foreach ($equipment as $item) {
                $db->table('work_order_equipment')->insert([
                    'work_order_id' => (int) $workOrderId,
                    'equipment_id' => (int) $item['equipment_id'],
                    'equipment_code_snapshot' => (string) $item['code'],
                    'equipment_name_snapshot' => (string) $item['name'],
                    'assignment_status' => 'assigned',
                    'entry_user' => $this->actor(),
                    'entry_date' => date('Y-m-d H:i:s'),
                ]);
            }

            foreach ($team as $member) {
                $db->table('work_order_team')->insert([
                    'work_order_id' => (int) $workOrderId,
                    'employee_id' => (int) $member['employee_id'],
                    'equipment_id' => ! empty($member['equipment_id']) ? (int) $member['equipment_id'] : null,
                    'resource_role_id' => ! empty($member['resource_role_id']) ? (int) $member['resource_role_id'] : null,
                    'allocation_type' => (string) $member['allocation_type'],
                    'employee_code_snapshot' => (string) $member['employee_code'],
                    'employee_name_snapshot' => (string) $member['employee_name'],
                    'role_name_snapshot' => $member['allocation_type'] === 'mission_leader' ? 'Responsable de misión' : ($member['role_name'] ?: 'Recurso operativo'),
                    'assignment_status' => 'assigned',
                    'entry_user' => $this->actor(),
                    'entry_date' => date('Y-m-d H:i:s'),
                ]);
            }

            $db->table('service_case_events')->insert([
                'service_case_id' => (int) $plan['service_case_id'],
                'event_code' => 'work_order.created',
                'title' => 'Orden de Trabajo generada',
                'description' => 'La coordinación ' . $plan['code'] . ' generó la Orden de Trabajo.',
                'entity_type' => 'work_order',
                'entity_id' => (int) $workOrderId,
                'occurred_at' => date('Y-m-d H:i:s'),
                'entry_user' => $this->actor(),
                'entry_date' => date('Y-m-d H:i:s'),
            ]);

            (new ActivityService())->record(
                'work_order',
                (int) $workOrderId,
                'work_order.created',
                'Orden de Trabajo creada',
                'Generada desde ' . $plan['code']
            );

            $db->transCommit();
            return (int) $workOrderId;
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function issue(int $workOrderId, ?string $notes = null): void
    {
        $db = db_connect();
        $order = $db->table('work_orders')
            ->where('id', $workOrderId)
            ->where('delete_date', null)
            ->get()->getRowArray();

        if ($order === null) {
            throw new RuntimeException('Orden de Trabajo no encontrada.');
        }
        if ($order['status'] !== 'prepared') {
            throw new RuntimeException('Solo una Orden de Trabajo preparada puede ser emitida.');
        }
        if (empty($order['mission_leader_employee_id'])) {
            throw new RuntimeException('La Orden de Trabajo no tiene Responsable de Misión.');
        }

        $leader = $db->table('employees')
            ->where('id', (int) $order['mission_leader_employee_id'])
            ->where('status', 1)
            ->where('delete_date', null)
            ->get()->getRowArray();
        if ($leader === null) {
            throw new RuntimeException('El Responsable de Misión ya no está disponible en el catálogo de personal.');
        }

        $equipmentCount = $db->table('work_order_equipment')
            ->where('work_order_id', $workOrderId)
            ->countAllResults();
        $teamCount = $db->table('work_order_team')
            ->where('work_order_id', $workOrderId)
            ->countAllResults();
        if ($equipmentCount === 0 || $teamCount === 0) {
            throw new RuntimeException('La Orden de Trabajo no tiene completos sus recursos operativos.');
        }

        $now = date('Y-m-d H:i:s');
        $db->transBegin();
        try {
            $db->table('work_orders')->where('id', $workOrderId)->update([
                'status' => 'issued',
                'issued_at' => $now,
                'issued_by_user_id' => session('auth_user_id') ?: null,
                'issued_to_employee_id' => (int) $leader['id'],
                'issuance_notes' => trim((string) $notes) !== '' ? trim((string) $notes) : null,
                'modify_user' => $this->actor(),
                'modify_date' => $now,
            ]);

            $db->table('service_cases')->where('id', (int) $order['service_case_id'])->update([
                'current_stage' => 'work_order',
                'operational_status' => 'scheduled',
                'next_action_code' => 'work_order.start',
                'next_action_label' => 'Iniciar ejecución de Orden de Trabajo',
                'modify_user' => $this->actor(),
                'modify_date' => $now,
            ]);

            $db->table('service_case_events')->insert([
                'service_case_id' => (int) $order['service_case_id'],
                'event_code' => 'work_order.issued',
                'title' => 'Orden de Trabajo emitida',
                'description' => 'La Orden de Trabajo ' . $order['code'] . ' fue emitida y entregada a ' . $leader['name'] . '.',
                'entity_type' => 'work_order',
                'entity_id' => $workOrderId,
                'occurred_at' => $now,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);

            (new ActivityService())->record(
                'work_order',
                $workOrderId,
                'work_order.issued',
                'Orden de Trabajo emitida',
                'Entregada a ' . $leader['name']
            );

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function nextCode(): string
    {
        $year = date('Y');
        $last = db_connect()->table('work_orders')
            ->like('code', 'OT-' . $year . '-', 'after')
            ->orderBy('id', 'DESC')->get(1)->getRowArray();
        $sequence = $last ? ((int) substr((string) $last['code'], -6)) + 1 : 1;
        return sprintf('OT-%s-%06d', $year, $sequence);
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
        return (string) (session('auth_user_email') ?: 'system');
    }
}
