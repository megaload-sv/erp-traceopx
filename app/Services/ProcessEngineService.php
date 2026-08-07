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
        $nextActionCode = $next['milestone_code'] ?? 'case_review';
        $nextActionLabel = $next['milestone_label'] ?? 'Revisar expediente';

        (new ServiceCaseModel())->update($serviceCaseId, [
            'health_score' => $healthScore,
            'next_action_code' => $nextActionCode,
            'next_action_label' => $nextActionLabel,
        ]);

        return [
            'case' => $case,
            'milestones' => $milestones,
            'exceptions' => $openExceptions,
            'health_score' => $healthScore,
            'next_action' => [
                'code' => $nextActionCode,
                'label' => $nextActionLabel,
                'blocked' => $openExceptions !== [],
                'blocking_reasons' => array_column($openExceptions, 'title'),
            ],
        ];
    }
}
