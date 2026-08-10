<?php

namespace App\Controllers;

use App\Models\CoordinationPlanModel;
use App\Models\EquipmentModel;
use App\Models\ServiceCaseModel;
use App\Services\ActivityService;
use App\Services\CoordinationApprovalService;
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
                'ready' => count(array_filter($plans, static fn(array $r): bool => $r['status'] === 'approved')),
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

        try {
            $equipmentIds = $this->validatedEquipmentIds((array) $this->request->getPost('equipment_ids'));
            $scheduledStart = $this->nullableDateTime('scheduled_start_at');
            $estimatedEnd = $this->nullableDateTime('estimated_end_at');
            $location = $this->nullable('location');
            $scopeNotes = $this->nullable('scope_notes');

            if ($scheduledStart === null || $estimatedEnd === null) {
                throw new RuntimeException('Defina la fecha programada y el fin estimado de la coordinación.');
            }
            if (strtotime($estimatedEnd) <= strtotime($scheduledStart)) {
                throw new RuntimeException('El fin estimado debe ser posterior a la fecha programada.');
            }
            if ($location === null) {
                throw new RuntimeException('Ingrese el lugar de ejecución del servicio.');
            }
            if ($scopeNotes === null) {
                throw new RuntimeException('Ingrese el alcance operativo de la coordinación.');
            }

            $db = db_connect();
            $db->transBegin();

            $id = $model->insert([
                'uuid' => $this->uuidV4(),
                'code' => $this->nextCode(),
                'service_case_id' => $serviceCaseId,
                'requested_start_at' => $this->nullableDateTime('requested_start_at'),
                'scheduled_start_at' => $scheduledStart,
                'estimated_end_at' => $estimatedEnd,
                'location' => $location,
                'location_reference' => $this->nullable('location_reference'),
                'priority' => (string) ($this->request->getPost('priority') ?: 'normal'),
                'status' => 'draft',
                'scope_notes' => $scopeNotes,
                'coordination_notes' => $this->nullable('coordination_notes'),
                'prepared_by_user_id' => session('auth_user_id') ?: null,
                'prepared_at' => date('Y-m-d H:i:s'),
            ], true);

            if ($id === false) {
                throw new RuntimeException('No fue posible crear el plan operativo.');
            }

            foreach ($equipmentIds as $equipmentId) {
                $this->insertPlannedEquipment($db, (int) $id, $equipmentId);
            }

            $db->table('service_case_events')->insert([
                'service_case_id' => $serviceCaseId,
                'event_code' => 'coordination.plan_created',
                'title' => 'Plan de coordinación creado',
                'description' => 'Se inició la planificación operativa del servicio con ' . count($equipmentIds) . ' equipo(s) previsto(s).',
                'occurred_at' => date('Y-m-d H:i:s'),
                'entry_user' => $this->actor(),
                'entry_date' => date('Y-m-d H:i:s'),
            ]);
            (new ActivityService())->record('coordination_plan', (int) $id, 'coordination.plan_created', 'Plan operativo creado', 'Expediente #' . $serviceCaseId);

            $db->transCommit();
            return redirect()->to(route_to('coordination.show', $id))->with('success', 'Plan de coordinación creado correctamente.');
        } catch (Throwable $e) {
            if (isset($db)) {
                $db->transRollback();
            }
            log_message('error', 'Error creando coordinación: {message}', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function addEquipment(int $id): RedirectResponse
    {
        $plan = (new CoordinationPlanModel())->find($id);
        if ($plan === null || $plan['delete_date'] !== null) {
            return redirect()->to(route_to('coordination.index'))->with('error', 'Plan de coordinación no encontrado.');
        }
        if ($plan['status'] !== 'draft') {
            return redirect()->to(route_to('coordination.show', $id))->with('error', 'La maquinaria solo puede modificarse mientras la coordinación está en preparación.');
        }

        try {
            $equipmentIds = $this->validatedEquipmentIds((array) $this->request->getPost('equipment_ids'));
            $db = db_connect();
            $db->transBegin();
            $added = 0;

            foreach ($equipmentIds as $equipmentId) {
                $exists = $db->table('coordination_plan_equipment')
                    ->where('coordination_plan_id', $id)
                    ->where('equipment_id', $equipmentId)
                    ->where('delete_date', null)
                    ->get()->getRowArray();
                if ($exists !== null) {
                    continue;
                }
                $this->insertPlannedEquipment($db, $id, $equipmentId);
                $added++;
            }

            if ($added === 0) {
                throw new RuntimeException('Los equipos seleccionados ya pertenecen a esta coordinación.');
            }

            $db->table('service_case_events')->insert([
                'service_case_id' => (int) $plan['service_case_id'],
                'event_code' => 'coordination.equipment_added',
                'title' => 'Maquinaria agregada a coordinación',
                'description' => 'Se agregaron ' . $added . ' equipo(s) al plan operativo.',
                'occurred_at' => date('Y-m-d H:i:s'),
                'entry_user' => $this->actor(),
                'entry_date' => date('Y-m-d H:i:s'),
            ]);

            $db->transCommit();
            return redirect()->to(route_to('coordination.show', $id))->with('success', 'Maquinaria agregada correctamente. TraceOPX recalculó los requisitos humanos.');
        } catch (Throwable $e) {
            if (isset($db)) {
                $db->transRollback();
            }
            return redirect()->to(route_to('coordination.show', $id))->with('error', $e->getMessage());
        }
    }

    public function approve(int $id): RedirectResponse
    {
        try {
            (new CoordinationApprovalService())->approve($id);
            return redirect()->to(route_to('coordination.show', $id))
                ->with('success', 'Coordinación aprobada. Los recursos quedaron asignados formalmente a la misión.');
        } catch (Throwable $e) {
            log_message('error', 'Error aprobando coordinación {id}: {message}', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            return redirect()->to(route_to('coordination.show', $id))->with('error', $e->getMessage());
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
        $approvalChecklist = (new CoordinationApprovalService())->checklist($id);
        $assignedEquipmentIds = array_map('intval', array_column($equipment, 'equipment_id'));
        $availableToAdd = array_values(array_filter(
            $this->availableEquipment(),
            static fn(array $item): bool => ! in_array((int) $item['id'], $assignedEquipmentIds, true)
        ));

        $workOrder = null;
        if ($db->tableExists('work_orders')) {
            $workOrder = $db->table('work_orders')
                ->where('coordination_plan_id', $id)
                ->where('delete_date', null)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
        }

        return view('coordination/show', [
            'title' => 'Coordinación ' . $plan['code'],
            'plan' => $plan,
            'equipment' => $equipment,
            'equipmentAvailableToAdd' => $availableToAdd,
            'requirements' => $resourceWorkspace['requirements'],
            'resourceWorkspace' => $resourceWorkspace,
            'approvalChecklist' => $approvalChecklist,
            'workOrder' => $workOrder,
        ]);
    }

    private function validatedEquipmentIds(array $values): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $values), static fn(int $id): bool => $id > 0)));
        if ($ids === []) {
            throw new RuntimeException('Seleccione al menos una maquinaria o equipo para crear la coordinación.');
        }

        foreach ($ids as $equipmentId) {
            $equipment = (new EquipmentModel())->find($equipmentId);
            if ($equipment === null || (int) $equipment['status'] !== 1 || $equipment['delete_date'] !== null) {
                throw new RuntimeException('Uno de los equipos seleccionados no existe o está inactivo.');
            }
            if ($equipment['operational_status'] !== 'available') {
                throw new RuntimeException($equipment['code'] . ' · ' . $equipment['name'] . ' no está disponible para planificación.');
            }
            if (! in_array($equipment['maintenance_status'], ['ok', 'preventive_due'], true)) {
                throw new RuntimeException($equipment['code'] . ' · ' . $equipment['name'] . ' no puede planificarse por su estado de mantenimiento.');
            }
        }

        return $ids;
    }

    private function insertPlannedEquipment($db, int $planId, int $equipmentId): void
    {
        $db->table('coordination_plan_equipment')->insert([
            'coordination_plan_id' => $planId,
            'equipment_id' => $equipmentId,
            'assignment_status' => 'planned',
            'entry_user' => $this->actor(),
            'entry_date' => date('Y-m-d H:i:s'),
        ]);
    }

    private function availableEquipment(): array
    {
        return (new EquipmentModel())
            ->where('status', 1)
            ->where('delete_date', null)
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
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new RuntimeException('Una de las fechas ingresadas no es válida.');
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: 'system');
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
