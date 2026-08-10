<?php

namespace App\Services;

class ServiceCaseOperationalSummaryService
{
    public function build(int $serviceCaseId, ?array $workOrder, ?array $checklist = null): array
    {
        $empty = [
            'active' => false,
            'status_label' => 'Sin ejecución activa',
            'started_at' => null,
            'elapsed_label' => '—',
            'mission_leader' => '—',
            'equipment_count' => 0,
            'personnel_count' => 0,
            'mission_log_count' => 0,
            'evidence_count' => 0,
            'open_incidents' => 0,
            'critical_incidents' => 0,
            'high_incidents' => 0,
            'incident_risk' => 'Sin incidencias abiertas',
            'checklist_percent' => null,
            'checklist_required_pending' => 0,
            'checklist_required_failed' => 0,
        ];

        if ($workOrder === null) {
            return $empty;
        }

        $db = db_connect();
        $workOrderId = (int) $workOrder['id'];

        $equipmentCount = $db->tableExists('work_order_equipment')
            ? $db->table('work_order_equipment')->where('work_order_id', $workOrderId)->countAllResults()
            : 0;

        $personnelCount = 0;
        if ($db->tableExists('work_order_team')) {
            $rows = $db->table('work_order_team')
                ->select('employee_id')
                ->where('work_order_id', $workOrderId)
                ->get()->getResultArray();
            $personnelCount = count(array_unique(array_map('intval', array_column($rows, 'employee_id'))));
        }

        $missionLogCount = $db->tableExists('mission_logs')
            ? $db->table('mission_logs')->where('work_order_id', $workOrderId)->countAllResults()
            : 0;

        $evidenceCount = $db->tableExists('work_order_evidence')
            ? $db->table('work_order_evidence')
                ->where('service_case_id', $serviceCaseId)
                ->where('delete_date', null)
                ->countAllResults()
            : 0;

        $incidents = $db->tableExists('work_order_incidents')
            ? $db->table('work_order_incidents')
                ->select('severity')
                ->where('service_case_id', $serviceCaseId)
                ->where('status', 'open')
                ->get()->getResultArray()
            : [];

        $critical = count(array_filter($incidents, static fn(array $row): bool => $row['severity'] === 'critical'));
        $high = count(array_filter($incidents, static fn(array $row): bool => $row['severity'] === 'high'));
        $medium = count(array_filter($incidents, static fn(array $row): bool => $row['severity'] === 'medium'));
        $low = count(array_filter($incidents, static fn(array $row): bool => $row['severity'] === 'low'));

        $incidentRisk = match (true) {
            $critical > 0 => 'Crítico',
            $high > 0 => 'Alto',
            $medium > 0 => 'Medio',
            $low > 0 => 'Bajo',
            default => 'Sin incidencias abiertas',
        };

        $checklistSummary = $checklist['summary'] ?? null;
        $startedAt = $workOrder['started_at'] ?? null;

        return [
            'active' => true,
            'status_label' => $this->statusLabel((string) $workOrder['status']),
            'started_at' => $startedAt,
            'elapsed_label' => $this->elapsedLabel($startedAt, $workOrder['finished_at'] ?? null),
            'mission_leader' => $workOrder['mission_leader_name'] ?? '—',
            'equipment_count' => $equipmentCount,
            'personnel_count' => $personnelCount,
            'mission_log_count' => $missionLogCount,
            'evidence_count' => $evidenceCount,
            'open_incidents' => count($incidents),
            'critical_incidents' => $critical,
            'high_incidents' => $high,
            'incident_risk' => $incidentRisk,
            'checklist_percent' => $checklistSummary !== null ? (int) $checklistSummary['percent'] : null,
            'checklist_required_pending' => $checklistSummary !== null ? (int) $checklistSummary['required_pending'] : 0,
            'checklist_required_failed' => $checklistSummary !== null ? (int) $checklistSummary['required_failed'] : 0,
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'prepared' => 'Preparada',
            'issued' => 'Emitida',
            'in_progress', 'working' => 'En ejecución',
            'completed', 'finished' => 'Trabajo operativo finalizado',
            'closed' => 'Cerrada',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function elapsedLabel(?string $startedAt, ?string $finishedAt): string
    {
        if (empty($startedAt)) {
            return '—';
        }

        $start = strtotime($startedAt);
        $end = ! empty($finishedAt) ? strtotime($finishedAt) : time();
        if ($start === false || $end === false || $end < $start) {
            return '—';
        }

        $minutes = (int) floor(($end - $start) / 60);
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $mins = $minutes % 60;

        if ($days > 0) {
            return $days . ' d ' . $hours . ' h ' . $mins . ' min';
        }
        if ($hours > 0) {
            return $hours . ' h ' . $mins . ' min';
        }

        return $mins . ' min';
    }
}
