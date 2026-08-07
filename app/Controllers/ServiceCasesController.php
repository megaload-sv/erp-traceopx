<?php

namespace App\Controllers;

use App\Models\ServiceCaseModel;
use App\Services\ProcessEngineService;
use RuntimeException;

class ServiceCasesController extends BaseController
{
    public function index(): string
    {
        $cases = (new ServiceCaseModel())
            ->select('service_cases.*, customers.business_name, quotations.code AS quotation_code, users.name AS responsible_user_name')
            ->join('customers', 'customers.id = service_cases.customer_id', 'left')
            ->join('quotations', 'quotations.id = service_cases.accepted_quotation_id', 'left')
            ->join('users', 'users.id = service_cases.responsible_user_id', 'left')
            ->orderBy('service_cases.opened_at', 'DESC')
            ->findAll();

        return view('service_cases/index', [
            'title' => 'Expedientes de servicio',
            'cases' => $cases,
            'metrics' => [
                'total' => count($cases),
                'critical' => count(array_filter($cases, static fn (array $case): bool => (int) $case['health_score'] < 50)),
                'attention' => count(array_filter($cases, static fn (array $case): bool => (int) $case['health_score'] >= 50 && (int) $case['health_score'] < 80)),
                'healthy' => count(array_filter($cases, static fn (array $case): bool => (int) $case['health_score'] >= 80)),
            ],
        ]);
    }

    public function show(int $id): string
    {
        $case = (new ServiceCaseModel())
            ->select('service_cases.*, customers.business_name, customers.trade_name, quotations.code AS quotation_code, quotations.subject AS quotation_subject, quotations.total AS quotation_total, users.name AS responsible_user_name')
            ->join('customers', 'customers.id = service_cases.customer_id', 'left')
            ->join('quotations', 'quotations.id = service_cases.accepted_quotation_id', 'left')
            ->join('users', 'users.id = service_cases.responsible_user_id', 'left')
            ->find($id);

        if ($case === null) {
            throw new RuntimeException('Expediente de servicio no encontrado.');
        }

        $evaluation = (new ProcessEngineService())->evaluate($id);
        $db = db_connect();

        return view('service_cases/show', [
            'title' => 'Expediente ' . $case['code'],
            'case' => array_merge($case, [
                'health_score' => $evaluation['health_score'],
                'next_action_code' => $evaluation['next_action']['code'],
                'next_action_label' => $evaluation['next_action']['label'],
            ]),
            'milestones' => $evaluation['milestones'],
            'exceptions' => $evaluation['exceptions'],
            'nextAction' => $evaluation['next_action'],
            'events' => $db->table('service_case_events')
                ->where('service_case_id', $id)
                ->orderBy('occurred_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->get()->getResultArray(),
            'stageHistory' => $db->table('service_case_stage_history')
                ->where('service_case_id', $id)
                ->orderBy('changed_at', 'DESC')
                ->get()->getResultArray(),
        ]);
    }
}
