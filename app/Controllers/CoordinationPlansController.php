<?php

namespace App\Controllers;

use App\Models\CoordinationPlanModel;
use App\Models\EquipmentModel;
use App\Models\ServiceCaseModel;
use App\Services\ActivityService;
use App\Services\ResourceAllocationService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

class CoordinationPlansController extends BaseController
{
    public function index(): string
    {
        $plans = (new CoordinationPlanModel())->workspaceList();

        return view('coordination/index', [
            'title' => 'Coordinación operativa',
            'plans' => $plans,
            'metrics' => [
                'total' => count($plans),
                'draft' => count(array_filter($plans, static fn(array $r): bool => $r['status'] === 'draft')),
                'ready' => count(array_filter($plans, static fn(array $r): bool => $r['status'] === 'ready')),
            ],
        ]);
    }

    public function create(int $serviceCaseId): string|RedirectResponse
    {
        $case = (new ServiceCaseModel())
            ->select('service_cases.*, customers.business_name, quotations.subject AS quotation_subject')
            ->join('customers', 'customers.id = service_cases.customer_id', 'left')
            ->join('quotations', 'quotations.id = service_cases.accepted_quotation_id', 'left')
            ->find($serviceCaseId);

        if ($case === null) {
            throw new RuntimeException('Expediente de servicio no encontrado.');
        }

        $existing = (new CoordinationPlanModel())
            ->where('service_case_id', $serviceCaseId)
            ->where('delete_date', null)
            ->first();
        if ($existing !== null) {
            return redirect()->to(route_to('coordination.show', $existing['id']));
        }

        return view('coordination/form', [
            'title' => 'Preparar coordinación',
            'case' => $case,
            'equipment' => $this->availableEquipment(),
        ]);
    }

    public function store(int $serviceCaseId): RedirectResponse
    {
        $case = (new ServiceCaseModel())->find($serviceCaseId);
        if ($case === null) {
            return redirect()->to(route_to('service_cases.index'))->with('error', 'Expediente no encontrado.');
        }

        $model = new CoordinationPlanModel();
        if ($model->where('service_case_id', $serviceCaseId)->where('delete_date', null)->first() !== null) {
            return redirect()->to(route_to('service_cases.show', $serviceCaseId))->with('error', 'El expediente ya tiene un plan de coordinación.');
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $id = $model->insert([
                'uuid' => $this->uuidV4(),
                'code' => $this->nextCode(),
                'service_case_id' => $serviceCaseId,
                'requested_start_at' => $this->nullableDateTime('requested_start_at'),
                'scheduled_start_at' => $this->nullableDateTime('scheduled_start_at'),
                'estimated_end_at' => $this->nullableDateTime('estimated_end_at'),
                'location' => $this->nullable('location'),
                'location_reference' => $this->nullable('location_reference'),
                'priority' => (string) ($this->request->getPost('priority') ?: 'normal'),
                'status' => 'draft',
                'scope_notes' => $this->nullable('scope_notes'),
                'coordination_notes' => $this->nullable('coordination_notes'),
                'prepared_by_user_id' => session('auth_user_id') ?: null,
                'prepared_at' => date('Y-m-d H:i:s'),
            ], true);

            if ($id === false) {
                throw new RuntimeException('No fue posible crear el plan operativo.');
            }

            foreach ((array) $this->request->getPost('equipment_ids') as $equipmentId) {
                $equipmentId = (int) $equipmentId;
                if ($equipmentId <= 0) continue;
                $equipment = (new EquipmentModel())->find($equipmentId);
                if ($equipment === null || $equipment['operational_status'] !== 'available' || ! in_array($equipment['maintenance_status'], ['ok','preventive_due'], true)) {
                    throw new RuntimeException('Uno de los equipos seleccionados ya no está disponible para planificación.');
                }
                $db->table('coordination_plan_equipment')->insert([
                    'coordination_plan_id' => $id,
                    'equipment_id' => $equipmentId,
                    'assignment_status' => 'planned',
                    'status' => 1,
                    'entry_user' => (string) (session('auth_user_email') ?: 'system'),
                    'entry_date' => date('Y-m-d H:i:s'),
                ]);
            }

            $db->table('service_case_events')->insert([
                'service_case_id' => $serviceCaseId,
                'event_code' => 'coordination.plan_created',
                'title' => 'Plan de coordinación creado',
                'description' => 'Se inició la planificación operativa del servicio.',
                'occurred_at' => date('Y-m-d H:i:s'),
                'entry_user' => (string) (session('auth_user_email') ?: 'system'),
                'entry_date' => date('Y-m-d H:i:s'),
            ]);
            (new ActivityService())->record('coordination_plan', (int) $id, 'coordination.plan_created', 'Plan operativo creado', 'Expediente #' . $serviceCaseId);
            $db->transCommit();

            return redirect()->to(route_to('coordination.show', $id))->with('success', 'Plan de coordinación creado correctamente.');
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error creando coordinación: {message}', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(int $id): string
    {
        $plan = (new CoordinationPlanModel())->detail($id);
        if ($plan === null) throw new RuntimeException('Plan de coordinación no encontrado.');

        $db = db_connect();
        $equipment = $db->table('coordination_plan_equipment cpe')
            ->select('cpe.*, equipment.code, equipment.name, equipment.operational_status, equipment.maintenance_status')
            ->join('equipment', 'equipment.id = cpe.equipment_id')
            ->where('cpe.coordination_plan_id', $id)
            ->where('cpe.delete_date', null)
            ->get()->getResultArray();

        $resourceWorkspace = (new ResourceAllocationService())->workspace($id);

        return view('coordination/show', [
            'title' => 'Coordinación ' . $plan['code'],
            'plan' => $plan,
            'equipment' => $equipment,
            'requirements' => $resourceWorkspace['requirements'],
            'resourceWorkspace' => $resourceWorkspace,
        ]);
    }

    private function availableEquipment(): array
    {
        return (new EquipmentModel())
            ->where('operational_status', 'available')
            ->whereIn('maintenance_status', ['ok','preventive_due'])
            ->orderBy('code')->findAll();
    }

    private function nextCode(): string
    {
        $year = date('Y');
        $db = db_connect();
        $last = $db->table('coordination_plans')->like('code', 'COORD-' . $year . '-', 'after')->orderBy('id', 'DESC')->get(1)->getRowArray();
        $sequence = $last ? ((int) substr((string) $last['code'], -6)) + 1 : 1;
        return sprintf('COORD-%s-%06d', $year, $sequence);
    }

    private function nullable(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : $value;
    }

    private function nullableDateTime(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : date('Y-m-d H:i:s', strtotime($value));
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
