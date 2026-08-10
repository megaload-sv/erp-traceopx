<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class WorkOrderIncidentService
{
    private const TYPES = [
        'equipment_failure' => 'Falla de maquinaria',
        'personnel_absence' => 'Ausencia / indisponibilidad de personal',
        'client_request' => 'Solicitud o cambio del cliente',
        'unsafe_condition' => 'Condición insegura',
        'operational_deviation' => 'Desviación operativa',
        'other' => 'Otra incidencia',
    ];

    private const SEVERITIES = [
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
        'critical' => 'Crítica',
    ];

    public function types(): array
    {
        return self::TYPES;
    }

    public function severities(): array
    {
        return self::SEVERITIES;
    }

    public function create(int $workOrderId, array $data): int
    {
        $db = db_connect();
        $order = $this->findRunningOrder($workOrderId);

        $type = trim((string) ($data['incident_type'] ?? ''));
        $severity = trim((string) ($data['severity'] ?? 'medium'));
        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $occurredAt = $this->normalizeDateTime($data['occurred_at'] ?? null) ?? date('Y-m-d H:i:s');
        $equipmentId = $this->nullableInt($data['equipment_id'] ?? null);
        $teamId = $this->nullableInt($data['work_order_team_id'] ?? null);

        if (! array_key_exists($type, self::TYPES)) {
            throw new RuntimeException('El tipo de incidencia seleccionado no es válido.');
        }
        if (! array_key_exists($severity, self::SEVERITIES)) {
            throw new RuntimeException('La severidad seleccionada no es válida.');
        }
        if ($title === '' || $description === '') {
            throw new RuntimeException('Ingrese título y descripción de la incidencia.');
        }
        if (strtotime($occurredAt) < strtotime((string) $order['started_at'])) {
            throw new RuntimeException('La incidencia no puede ser anterior al inicio real de la OT.');
        }
        if (strtotime($occurredAt) > time() + 300) {
            throw new RuntimeException('La fecha de la incidencia no puede estar en el futuro.');
        }

        if ($equipmentId !== null) {
            $exists = $db->table('work_order_equipment')->where('work_order_id', $workOrderId)->where('equipment_id', $equipmentId)->countAllResults() > 0;
            if (! $exists) {
                throw new RuntimeException('La maquinaria seleccionada no pertenece a esta OT.');
            }
        }
        if ($teamId !== null) {
            $exists = $db->table('work_order_team')->where('work_order_id', $workOrderId)->where('id', $teamId)->countAllResults() > 0;
            if (! $exists) {
                throw new RuntimeException('El colaborador seleccionado no pertenece a esta OT.');
            }
        }

        $requiresChange = in_array($type, ['equipment_failure', 'personnel_absence'], true);
        $now = date('Y-m-d H:i:s');

        $db->transBegin();
        try {
            $db->table('work_order_incidents')->insert([
                'work_order_id' => $workOrderId,
                'service_case_id' => (int) $order['service_case_id'],
                'incident_type' => $type,
                'severity' => $severity,
                'title' => $title,
                'description' => $description,
                'equipment_id' => $equipmentId,
                'work_order_team_id' => $teamId,
                'status' => 'open',
                'requires_resource_change' => $requiresChange ? 1 : 0,
                'occurred_at' => $occurredAt,
                'reported_by' => $this->actor(),
                'entry_date' => $now,
            ]);
            $incidentId = (int) $db->insertID();
            if ($incidentId <= 0) {
                throw new RuntimeException('No fue posible registrar la incidencia.');
            }

            $db->table('mission_logs')->insert([
                'work_order_id' => $workOrderId,
                'service_case_id' => (int) $order['service_case_id'],
                'log_type' => 'incident',
                'category' => 'incident',
                'event_code' => 'incident.created',
                'title' => $title,
                'description' => $description,
                'visibility' => 'internal',
                'occurred_at' => $occurredAt,
                'actor_user_id' => session('auth_user_id') ?: null,
                'metadata_json' => json_encode(['incident_id' => $incidentId, 'severity' => $severity, 'type' => $type], JSON_UNESCAPED_UNICODE),
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);

            $db->table('service_case_events')->insert([
                'service_case_id' => (int) $order['service_case_id'],
                'event_code' => 'incident.created',
                'title' => 'Incidencia operativa: ' . $title,
                'description' => self::SEVERITIES[$severity] . ' · ' . $description,
                'entity_type' => 'work_order_incident',
                'entity_id' => $incidentId,
                'occurred_at' => $occurredAt,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);

            (new ActivityService())->record('work_order', $workOrderId, 'incident.created', 'Incidencia operativa registrada', $title);
            $db->transCommit();
            (new ProcessEngineService())->evaluate((int) $order['service_case_id']);

            return $incidentId;
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function candidatesForPersonnelChange(int $workOrderId, int $incidentId): array
    {
        $db = db_connect();
        $order = $this->findRunningOrder($workOrderId);
        $incident = $this->findIncident($workOrderId, $incidentId);
        if (empty($incident['work_order_team_id'])) {
            return [];
        }

        $team = $db->table('work_order_team')->where('id', (int) $incident['work_order_team_id'])->where('work_order_id', $workOrderId)->get()->getRowArray();
        if ($team === null || empty($team['resource_role_id'])) {
            return [];
        }

        $role = $db->table('resource_roles')->where('id', (int) $team['resource_role_id'])->get()->getRowArray();
        if ($role === null) {
            return [];
        }

        $categoryCode = null;
        if (! empty($team['equipment_id'])) {
            $equipment = $db->table('equipment e')->select('ec.code AS category_code')->join('equipment_categories ec', 'ec.id = e.category_id', 'left')->where('e.id', (int) $team['equipment_id'])->get()->getRowArray();
            $categoryCode = $equipment['category_code'] ?? null;
        }
        $skillCode = $this->skillCodeFor((string) $role['code'], $categoryCode);
        if ($skillCode === null) {
            return [];
        }

        $rows = $db->table('employees e')
            ->select('e.id, e.employee_code, e.name, e.availability_status, esa.proficiency_level, esa.certification_number, esa.valid_until, es.requires_certification')
            ->join('employee_skill_assignments esa', 'esa.employee_id = e.id AND esa.status = 1 AND esa.delete_date IS NULL')
            ->join('employee_skills es', 'es.id = esa.skill_id AND es.status = 1 AND es.delete_date IS NULL')
            ->where('es.code', $skillCode)
            ->where('e.status', 1)
            ->where('e.delete_date', null)
            ->where('e.employment_status', 'active')
            ->whereIn('e.availability_status', ['available','reserved'])
            ->where('e.id !=', (int) $team['employee_id'])
            ->orderBy('e.name')
            ->get()->getResultArray();

        $today = date('Y-m-d');
        return array_values(array_filter($rows, function (array $row) use ($db, $order, $today): bool {
            if ((int) $row['requires_certification'] === 1) {
                if (empty($row['certification_number'])) return false;
                if (! empty($row['valid_until']) && $row['valid_until'] < $today) return false;
            }
            return $db->table('coordination_resource_allocations')
                ->where('employee_id', (int) $row['id'])
                ->where('coordination_plan_id !=', (int) $order['coordination_plan_id'])
                ->where('status', 1)
                ->where('delete_date', null)
                ->whereIn('allocation_status', ['reserved','assigned','working'])
                ->where('starts_at <', $order['estimated_end_at'])
                ->where('ends_at >', $order['scheduled_start_at'])
                ->countAllResults() === 0;
        }));
    }

    public function proposePersonnelChange(int $workOrderId, int $incidentId, int $incomingEmployeeId, string $reason): int
    {
        $db = db_connect();
        $order = $this->findRunningOrder($workOrderId);
        $incident = $this->findIncident($workOrderId, $incidentId);
        if ($incident['status'] !== 'open' || empty($incident['work_order_team_id'])) {
            throw new RuntimeException('La incidencia no permite proponer una sustitución de personal.');
        }

        $team = $db->table('work_order_team')->where('id', (int) $incident['work_order_team_id'])->where('work_order_id', $workOrderId)->get()->getRowArray();
        if ($team === null) {
            throw new RuntimeException('Asignación de personal no encontrada.');
        }

        $candidateIds = array_map('intval', array_column($this->candidatesForPersonnelChange($workOrderId, $incidentId), 'id'));
        if (! in_array($incomingEmployeeId, $candidateIds, true)) {
            throw new RuntimeException('El sustituto seleccionado no está disponible o no cumple con la habilidad/certificación requerida.');
        }
        if (trim($reason) === '') {
            throw new RuntimeException('Ingrese el motivo de la sustitución propuesta.');
        }

        $pending = $db->table('work_order_resource_changes')->where('incident_id', $incidentId)->whereIn('status', ['proposed','approved'])->countAllResults();
        if ($pending > 0) {
            throw new RuntimeException('Esta incidencia ya tiene una sustitución pendiente o aprobada.');
        }

        $now = date('Y-m-d H:i:s');
        $db->table('work_order_resource_changes')->insert([
            'incident_id' => $incidentId,
            'work_order_id' => $workOrderId,
            'change_type' => 'personnel_substitution',
            'work_order_team_id' => (int) $team['id'],
            'outgoing_employee_id' => (int) $team['employee_id'],
            'incoming_employee_id' => $incomingEmployeeId,
            'equipment_id' => ! empty($team['equipment_id']) ? (int) $team['equipment_id'] : null,
            'resource_role_id' => ! empty($team['resource_role_id']) ? (int) $team['resource_role_id'] : null,
            'status' => 'proposed',
            'reason' => trim($reason),
            'proposed_by' => $this->actor(),
            'proposed_at' => $now,
            'entry_date' => $now,
        ]);
        $changeId = (int) $db->insertID();

        (new ActivityService())->record('work_order', $workOrderId, 'resource_change.proposed', 'Sustitución de personal propuesta', trim($reason));
        return $changeId;
    }

    public function approvePersonnelChange(int $workOrderId, int $changeId): void
    {
        $db = db_connect();
        $order = $this->findRunningOrder($workOrderId);
        $change = $db->table('work_order_resource_changes')->where('id', $changeId)->where('work_order_id', $workOrderId)->get()->getRowArray();
        if ($change === null || $change['status'] !== 'proposed' || $change['change_type'] !== 'personnel_substitution') {
            throw new RuntimeException('La sustitución seleccionada no está disponible para aprobación.');
        }

        $incident = $this->findIncident($workOrderId, (int) $change['incident_id']);
        $candidateIds = array_map('intval', array_column($this->candidatesForPersonnelChange($workOrderId, (int) $incident['id']), 'id'));
        if (! in_array((int) $change['incoming_employee_id'], $candidateIds, true)) {
            throw new RuntimeException('El sustituto propuesto ya no está disponible o dejó de cumplir los requisitos.');
        }

        $team = $db->query('SELECT * FROM work_order_team WHERE id = ? AND work_order_id = ? FOR UPDATE', [(int) $change['work_order_team_id'], $workOrderId])->getRowArray();
        $incoming = $db->table('employees')->where('id', (int) $change['incoming_employee_id'])->where('status', 1)->where('delete_date', null)->get()->getRowArray();
        if ($team === null || $incoming === null) {
            throw new RuntimeException('No fue posible localizar los recursos de la sustitución.');
        }

        $now = date('Y-m-d H:i:s');
        $db->transBegin();
        try {
            $oldEmployeeId = (int) $team['employee_id'];
            $db->table('work_order_team')->where('id', (int) $team['id'])->update([
                'employee_id' => (int) $incoming['id'],
                'employee_code_snapshot' => (string) $incoming['employee_code'],
                'employee_name_snapshot' => (string) $incoming['name'],
                'assignment_status' => 'working',
            ]);

            $allocation = $db->table('coordination_resource_allocations')
                ->where('coordination_plan_id', (int) $order['coordination_plan_id'])
                ->where('employee_id', $oldEmployeeId)
                ->where('allocation_type', (string) $team['allocation_type'])
                ->where('status', 1)
                ->where('delete_date', null)
                ->whereIn('allocation_status', ['assigned','working'])
                ->get()->getRowArray();

            if ($allocation !== null) {
                $db->table('coordination_resource_allocations')->where('id', (int) $allocation['id'])->update([
                    'allocation_status' => 'released',
                    'released_at' => $now,
                    'modify_user' => $this->actor(),
                    'modify_date' => $now,
                ]);
                $db->table('coordination_resource_allocations')->insert([
                    'coordination_plan_id' => (int) $order['coordination_plan_id'],
                    'equipment_id' => $allocation['equipment_id'],
                    'resource_role_id' => $allocation['resource_role_id'],
                    'employee_id' => (int) $incoming['id'],
                    'allocation_type' => $allocation['allocation_type'],
                    'allocation_status' => 'working',
                    'starts_at' => $now,
                    'ends_at' => $allocation['ends_at'],
                    'assigned_by_user_id' => session('auth_user_id') ?: null,
                    'assigned_at' => $now,
                    'status' => 1,
                    'entry_user' => $this->actor(),
                    'entry_date' => $now,
                ]);
            }

            $db->table('employees')->where('id', (int) $incoming['id'])->update([
                'availability_status' => 'working',
                'modify_user' => $this->actor(),
                'modify_date' => $now,
            ]);
            $remaining = $db->table('coordination_resource_allocations')
                ->where('employee_id', $oldEmployeeId)
                ->where('status', 1)
                ->where('delete_date', null)
                ->whereIn('allocation_status', ['reserved','assigned','working'])
                ->countAllResults();
            if ($remaining === 0) {
                $db->table('employees')->where('id', $oldEmployeeId)->update([
                    'availability_status' => 'available',
                    'modify_user' => $this->actor(),
                    'modify_date' => $now,
                ]);
            }

            $db->table('work_order_resource_changes')->where('id', $changeId)->update([
                'status' => 'executed',
                'approved_by' => $this->actor(),
                'approved_at' => $now,
                'executed_at' => $now,
                'modify_date' => $now,
            ]);
            $db->table('work_order_incidents')->where('id', (int) $incident['id'])->update([
                'status' => 'resolved',
                'resolved_at' => $now,
                'resolution_notes' => 'Sustitución de personal aprobada y ejecutada.',
                'modify_user' => $this->actor(),
                'modify_date' => $now,
            ]);

            $description = $team['employee_name_snapshot'] . ' fue sustituido por ' . $incoming['name'] . '. Motivo: ' . $change['reason'];
            $db->table('mission_logs')->insert([
                'work_order_id' => $workOrderId,
                'service_case_id' => (int) $order['service_case_id'],
                'log_type' => 'resource_change',
                'category' => 'personnel',
                'event_code' => 'resource_change.executed',
                'title' => 'Sustitución de personal ejecutada',
                'description' => $description,
                'visibility' => 'internal',
                'occurred_at' => $now,
                'actor_user_id' => session('auth_user_id') ?: null,
                'metadata_json' => json_encode(['incident_id' => (int) $incident['id'], 'change_id' => $changeId], JSON_UNESCAPED_UNICODE),
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);
            $db->table('service_case_events')->insert([
                'service_case_id' => (int) $order['service_case_id'],
                'event_code' => 'resource_change.executed',
                'title' => 'Sustitución de personal aprobada',
                'description' => $description,
                'entity_type' => 'work_order_resource_change',
                'entity_id' => $changeId,
                'occurred_at' => $now,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);

            (new ActivityService())->record('work_order', $workOrderId, 'resource_change.executed', 'Sustitución de personal ejecutada', $description);
            $db->transCommit();
            (new ProcessEngineService())->evaluate((int) $order['service_case_id']);
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function findRunningOrder(int $workOrderId): array
    {
        $order = db_connect()->table('work_orders')->where('id', $workOrderId)->where('delete_date', null)->get()->getRowArray();
        if ($order === null) throw new RuntimeException('Orden de Trabajo no encontrada.');
        if (! in_array($order['status'], ['in_progress','working'], true)) throw new RuntimeException('Las incidencias operativas solo se gestionan mientras la OT está en ejecución.');
        return $order;
    }

    private function findIncident(int $workOrderId, int $incidentId): array
    {
        $incident = db_connect()->table('work_order_incidents')->where('id', $incidentId)->where('work_order_id', $workOrderId)->get()->getRowArray();
        if ($incident === null) throw new RuntimeException('Incidencia no encontrada.');
        return $incident;
    }

    private function normalizeDateTime(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        $timestamp = strtotime($value);
        if ($timestamp === false) throw new RuntimeException('La fecha y hora de la incidencia no son válidas.');
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = trim((string) $value);
        return $value === '' ? null : (int) $value;
    }

    private function skillCodeFor(string $roleCode, ?string $categoryCode): ?string
    {
        return match ($roleCode) {
            'DRIVER' => 'DRIVER',
            'HELPER' => 'HELPER',
            'RIGGER' => 'RIGGER',
            'OPERATOR' => match ($categoryCode) {
                'CRANES' => 'CRANE_OPERATOR',
                'FORKLIFTS' => 'FORKLIFT_OPERATOR',
                'TELEHANDLERS' => 'TELEHANDLER_OPERATOR',
                'MANLIFTS' => 'MANLIFT_OPERATOR',
                default => 'OPERATOR',
            },
            default => $roleCode,
        };
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
