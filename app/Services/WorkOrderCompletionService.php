<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class WorkOrderCompletionService
{
    public function readiness(int $workOrderId): array
    {
        $db = db_connect();
        $order = $db->table('work_orders')
            ->where('id', $workOrderId)
            ->where('delete_date', null)
            ->get()->getRowArray();

        if ($order === null) {
            throw new RuntimeException('Orden de Trabajo no encontrada.');
        }

        $blocking = [];
        $checklistReady = true;
        $checklistPercent = 100;

        if ($db->tableExists('work_order_checklists')) {
            $checklist = (new WorkOrderChecklistService())->ensureForWorkOrder($workOrderId);
            if ($checklist !== null) {
                $summary = $checklist['summary'] ?? [];
                $checklistReady = (bool) ($summary['ready'] ?? false);
                $checklistPercent = (int) ($summary['percent'] ?? 0);
                if (! $checklistReady) {
                    $blocking[] = 'El checklist operativo tiene verificaciones obligatorias pendientes o no conformes.';
                }
            }
        }

        $criticalIncidents = 0;
        if ($db->tableExists('work_order_incidents')) {
            $criticalIncidents = $db->table('work_order_incidents')
                ->where('work_order_id', $workOrderId)
                ->where('status', 'open')
                ->where('severity', 'critical')
                ->countAllResults();
            if ($criticalIncidents > 0) {
                $blocking[] = 'Existen incidencias críticas abiertas que deben resolverse antes de finalizar.';
            }
        }

        return [
            'ready' => $order['status'] === 'in_progress' && $blocking === [],
            'blocking_reasons' => $blocking,
            'checklist_ready' => $checklistReady,
            'checklist_percent' => $checklistPercent,
            'critical_incidents' => $criticalIncidents,
        ];
    }

    public function finish(int $workOrderId, string $finishedAt, string $summary, ?string $notes = null): void
    {
        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $cleanSummary = trim($summary);
        $cleanNotes = trim((string) $notes);
        $timestamp = strtotime($finishedAt);

        if ($timestamp === false) {
            throw new RuntimeException('La fecha y hora de finalización no son válidas.');
        }
        $finishedAtSql = date('Y-m-d H:i:s', $timestamp);
        if ($cleanSummary === '') {
            throw new RuntimeException('Ingrese un resumen del trabajo realizado.');
        }
        if ($timestamp > time() + 300) {
            throw new RuntimeException('La fecha de finalización no puede estar en el futuro.');
        }

        $readiness = $this->readiness($workOrderId);
        if (! $readiness['ready']) {
            throw new RuntimeException(implode(' ', $readiness['blocking_reasons']));
        }

        $db->transBegin();
        try {
            $order = $db->query(
                'SELECT * FROM work_orders WHERE id = ? AND delete_date IS NULL FOR UPDATE',
                [$workOrderId]
            )->getRowArray();

            if ($order === null) {
                throw new RuntimeException('Orden de Trabajo no encontrada.');
            }
            if ($order['status'] !== 'in_progress') {
                throw new RuntimeException('Solo una Orden de Trabajo en ejecución puede finalizarse operativamente.');
            }
            if (empty($order['started_at']) || $timestamp < strtotime((string) $order['started_at'])) {
                throw new RuntimeException('La finalización no puede ser anterior al inicio real del servicio.');
            }

            if ($db->tableExists('work_order_incidents')) {
                $criticalIncidents = $db->table('work_order_incidents')
                    ->where('work_order_id', $workOrderId)
                    ->where('status', 'open')
                    ->where('severity', 'critical')
                    ->countAllResults();
                if ($criticalIncidents > 0) {
                    throw new RuntimeException('Existen incidencias críticas abiertas que deben resolverse antes de finalizar.');
                }
            }

            $teamRows = $db->table('work_order_team')->where('work_order_id', $workOrderId)->get()->getResultArray();
            $equipmentRows = $db->table('work_order_equipment')->where('work_order_id', $workOrderId)->get()->getResultArray();

            foreach ($teamRows as $teamRow) {
                $db->table('work_order_team')->where('id', (int) $teamRow['id'])->update([
                    'assignment_status' => 'completed',
                ]);
            }

            $db->table('coordination_resource_allocations')
                ->where('coordination_plan_id', (int) $order['coordination_plan_id'])
                ->where('status', 1)
                ->where('delete_date', null)
                ->whereIn('allocation_status', ['assigned', 'working'])
                ->update([
                    'allocation_status' => 'released',
                    'released_at' => $finishedAtSql,
                    'modify_user' => $this->actor(),
                    'modify_date' => $now,
                ]);

            $employeeIds = array_values(array_unique(array_map('intval', array_column($teamRows, 'employee_id'))));
            foreach ($employeeIds as $employeeId) {
                $remaining = $db->table('coordination_resource_allocations')
                    ->where('employee_id', $employeeId)
                    ->where('status', 1)
                    ->where('delete_date', null)
                    ->whereIn('allocation_status', ['reserved', 'assigned', 'working'])
                    ->countAllResults();
                if ($remaining === 0) {
                    $db->table('employees')->where('id', $employeeId)->update([
                        'availability_status' => 'available',
                        'modify_user' => $this->actor(),
                        'modify_date' => $now,
                    ]);
                }
            }

            foreach ($equipmentRows as $equipmentRow) {
                $equipment = $db->table('equipment')->where('id', (int) $equipmentRow['equipment_id'])->get()->getRowArray();
                if ($equipment === null) {
                    continue;
                }

                $nextOperationalStatus = in_array($equipment['maintenance_status'], ['ok', 'preventive_due'], true)
                    ? 'available'
                    : 'out_of_service';

                $db->table('equipment')->where('id', (int) $equipment['id'])->update([
                    'operational_status' => $nextOperationalStatus,
                    'modify_user' => $this->actor(),
                    'modify_date' => $now,
                ]);
                $db->table('work_order_equipment')->where('id', (int) $equipmentRow['id'])->update([
                    'assignment_status' => 'completed',
                ]);
                $db->table('coordination_plan_equipment')
                    ->where('coordination_plan_id', (int) $order['coordination_plan_id'])
                    ->where('equipment_id', (int) $equipment['id'])
                    ->where('delete_date', null)
                    ->update([
                        'assignment_status' => 'completed',
                        'modify_user' => $this->actor(),
                        'modify_date' => $now,
                    ]);
            }

            $db->table('work_orders')->where('id', $workOrderId)->update([
                'status' => 'finished',
                'finished_at' => $finishedAtSql,
                'finished_by_user_id' => session('auth_user_id') ?: null,
                'completion_summary' => $cleanSummary,
                'completion_notes' => $cleanNotes !== '' ? $cleanNotes : null,
                'modify_user' => $this->actor(),
                'modify_date' => $now,
            ]);

            $db->table('mission_logs')->insert([
                'work_order_id' => $workOrderId,
                'service_case_id' => (int) $order['service_case_id'],
                'log_type' => 'completion',
                'category' => 'operation',
                'event_code' => 'work_order.finished',
                'title' => 'Trabajo operativo finalizado',
                'description' => $cleanSummary . ($cleanNotes !== '' ? ' Observaciones: ' . $cleanNotes : ''),
                'visibility' => 'internal',
                'occurred_at' => $finishedAtSql,
                'actor_user_id' => session('auth_user_id') ?: null,
                'metadata_json' => json_encode(['checklist_percent' => $readiness['checklist_percent']], JSON_UNESCAPED_UNICODE),
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);

            $db->table('service_case_events')->insert([
                'service_case_id' => (int) $order['service_case_id'],
                'event_code' => 'work_order.finished',
                'title' => 'Trabajo operativo finalizado',
                'description' => 'La Orden de Trabajo ' . $order['code'] . ' concluyó su ejecución operativa. ' . $cleanSummary,
                'entity_type' => 'work_order',
                'entity_id' => $workOrderId,
                'occurred_at' => $finishedAtSql,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);

            (new ActivityService())->record(
                'work_order',
                $workOrderId,
                'work_order.finished',
                'Trabajo operativo finalizado',
                $cleanSummary
            );

            $db->transCommit();
            (new ProcessEngineService())->evaluate((int) $order['service_case_id']);
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
