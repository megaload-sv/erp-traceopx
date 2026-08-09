<?php

namespace App\Services;

use RuntimeException;

class ResourceAllocationService
{
    public function workspace(int $coordinationPlanId): array
    {
        $db = db_connect();
        $plan = $db->table('coordination_plans')->where('id', $coordinationPlanId)->where('delete_date', null)->get()->getRowArray();
        if ($plan === null) {
            throw new RuntimeException('Plan de coordinación no encontrado.');
        }

        $requirements = $db->table('coordination_plan_equipment cpe')
            ->select('equipment.id AS equipment_id, equipment.code AS equipment_code, equipment.name AS equipment_name, equipment_categories.code AS category_code, resource_roles.id AS role_id, resource_roles.code AS role_code, resource_roles.name AS role_name, err.requirement_type, err.min_quantity, err.max_quantity')
            ->join('equipment', 'equipment.id = cpe.equipment_id')
            ->join('equipment_categories', 'equipment_categories.id = equipment.category_id', 'left')
            ->join('equipment_role_requirements err', 'err.equipment_id = equipment.id AND err.status = 1')
            ->join('resource_roles', 'resource_roles.id = err.resource_role_id')
            ->where('cpe.coordination_plan_id', $coordinationPlanId)
            ->where('cpe.delete_date', null)
            ->orderBy('equipment.code')->orderBy('resource_roles.name')
            ->get()->getResultArray();

        $allocations = $db->table('coordination_resource_allocations cra')
            ->select('cra.*, employees.employee_code, employees.name AS employee_name, resource_roles.name AS role_name, equipment.code AS equipment_code')
            ->join('employees', 'employees.id = cra.employee_id')
            ->join('resource_roles', 'resource_roles.id = cra.resource_role_id', 'left')
            ->join('equipment', 'equipment.id = cra.equipment_id', 'left')
            ->where('cra.coordination_plan_id', $coordinationPlanId)
            ->where('cra.status', 1)
            ->where('cra.delete_date', null)
            ->whereIn('cra.allocation_status', ['reserved','assigned','working'])
            ->orderBy('cra.id')
            ->get()->getResultArray();

        $byKey = [];
        $missionLeader = null;
        foreach ($allocations as $allocation) {
            if ($allocation['allocation_type'] === 'mission_leader') {
                $missionLeader = $allocation;
                continue;
            }
            $key = (int) $allocation['equipment_id'] . ':' . (int) $allocation['resource_role_id'];
            $byKey[$key][] = $allocation;
        }

        foreach ($requirements as &$requirement) {
            $key = (int) $requirement['equipment_id'] . ':' . (int) $requirement['role_id'];
            $requirement['allocations'] = $byKey[$key] ?? [];
            $requirement['assigned_count'] = count($requirement['allocations']);
            $requirement['skill_code'] = $this->skillCodeFor($requirement['role_code'], $requirement['category_code']);
            $requirement['candidates'] = $this->candidatesFor(
                $coordinationPlanId,
                $requirement['skill_code'],
                $plan['scheduled_start_at'],
                $plan['estimated_end_at']
            );
        }
        unset($requirement);

        $missionLeaderCandidates = $this->candidatesFor(
            $coordinationPlanId,
            'MISSION_LEADER',
            $plan['scheduled_start_at'],
            $plan['estimated_end_at']
        );

        $missingRequired = [];
        foreach ($requirements as $requirement) {
            if ($requirement['requirement_type'] === 'required' && $requirement['assigned_count'] < (int) $requirement['min_quantity']) {
                $missingRequired[] = $requirement['equipment_code'] . ' · ' . $requirement['role_name'];
            }
        }
        if ($missionLeader === null) {
            $missingRequired[] = 'Responsable de misión';
        }

        return [
            'requirements' => $requirements,
            'allocations' => $allocations,
            'mission_leader' => $missionLeader,
            'mission_leader_candidates' => $missionLeaderCandidates,
            'missing_required' => $missingRequired,
            'ready_for_approval' => $missingRequired === [],
        ];
    }

    public function reserveRole(int $planId, int $equipmentId, int $roleId, int $employeeId): void
    {
        $db = db_connect();
        $plan = $db->table('coordination_plans')->where('id', $planId)->where('delete_date', null)->get()->getRowArray();
        if ($plan === null) throw new RuntimeException('Plan de coordinación no encontrado.');

        $requirement = $db->table('equipment_role_requirements err')
            ->select('err.*, resource_roles.code AS role_code, equipment_categories.code AS category_code')
            ->join('resource_roles', 'resource_roles.id = err.resource_role_id')
            ->join('equipment', 'equipment.id = err.equipment_id')
            ->join('equipment_categories', 'equipment_categories.id = equipment.category_id', 'left')
            ->where('err.equipment_id', $equipmentId)
            ->where('err.resource_role_id', $roleId)
            ->where('err.status', 1)
            ->get()->getRowArray();
        if ($requirement === null) throw new RuntimeException('El requisito seleccionado no pertenece a la configuración del equipo.');

        $planned = $db->table('coordination_plan_equipment')
            ->where('coordination_plan_id', $planId)->where('equipment_id', $equipmentId)
            ->where('delete_date', null)->get()->getRowArray();
        if ($planned === null) throw new RuntimeException('El equipo no pertenece a esta coordinación.');

        $skillCode = $this->skillCodeFor($requirement['role_code'], $requirement['category_code']);
        $this->assertEligible($planId, $employeeId, $skillCode, $plan['scheduled_start_at'], $plan['estimated_end_at']);

        $currentCount = $db->table('coordination_resource_allocations')
            ->where('coordination_plan_id', $planId)
            ->where('equipment_id', $equipmentId)
            ->where('resource_role_id', $roleId)
            ->where('status', 1)->where('delete_date', null)
            ->whereIn('allocation_status', ['reserved','assigned','working'])
            ->countAllResults();
        if ($currentCount >= (int) $requirement['max_quantity']) {
            throw new RuntimeException('Ya se alcanzó la cantidad máxima configurada para este perfil.');
        }

        $this->insertAllocation($planId, $equipmentId, $roleId, $employeeId, 'operational_role', $plan);
    }

    public function reserveMissionLeader(int $planId, int $employeeId): void
    {
        $db = db_connect();
        $plan = $db->table('coordination_plans')->where('id', $planId)->where('delete_date', null)->get()->getRowArray();
        if ($plan === null) throw new RuntimeException('Plan de coordinación no encontrado.');

        $this->assertEligible($planId, $employeeId, 'MISSION_LEADER', $plan['scheduled_start_at'], $plan['estimated_end_at']);
        $existing = $db->table('coordination_resource_allocations')
            ->where('coordination_plan_id', $planId)->where('allocation_type', 'mission_leader')
            ->where('status', 1)->where('delete_date', null)
            ->whereIn('allocation_status', ['reserved','assigned','working'])->get()->getRowArray();
        if ($existing !== null) {
            throw new RuntimeException('La coordinación ya tiene un responsable de misión. Libérelo antes de asignar otro.');
        }
        $this->insertAllocation($planId, null, null, $employeeId, 'mission_leader', $plan);
    }

    public function release(int $planId, int $allocationId): void
    {
        $db = db_connect();
        $allocation = $db->table('coordination_resource_allocations')
            ->where('id', $allocationId)->where('coordination_plan_id', $planId)
            ->where('status', 1)->where('delete_date', null)->get()->getRowArray();
        if ($allocation === null) throw new RuntimeException('Asignación no encontrada.');
        if (! in_array($allocation['allocation_status'], ['reserved','assigned'], true)) {
            throw new RuntimeException('Esta asignación ya no puede liberarse desde Coordinación.');
        }
        $db->table('coordination_resource_allocations')->where('id', $allocationId)->update([
            'allocation_status' => 'released',
            'released_at' => date('Y-m-d H:i:s'),
            'modify_user' => $this->actor(),
            'modify_date' => date('Y-m-d H:i:s'),
        ]);
    }

    private function candidatesFor(int $planId, ?string $skillCode, ?string $start, ?string $end): array
    {
        if ($skillCode === null) return [];
        $db = db_connect();
        $rows = $db->table('employees e')
            ->select('e.id, e.employee_code, e.name, e.availability_status, esa.proficiency_level, esa.certification_number, esa.valid_until, es.requires_certification')
            ->join('employee_skill_assignments esa', 'esa.employee_id = e.id AND esa.status = 1 AND esa.delete_date IS NULL')
            ->join('employee_skills es', 'es.id = esa.skill_id AND es.status = 1 AND es.delete_date IS NULL')
            ->where('es.code', $skillCode)
            ->where('e.status', 1)->where('e.delete_date', null)
            ->where('e.employment_status', 'active')
            ->whereIn('e.availability_status', ['available','reserved'])
            ->orderBy('e.name')->get()->getResultArray();

        $today = date('Y-m-d');
        return array_values(array_filter($rows, function(array $row) use ($planId, $start, $end, $today): bool {
            if ((int) $row['requires_certification'] === 1) {
                if (empty($row['certification_number'])) return false;
                if (! empty($row['valid_until']) && $row['valid_until'] < $today) return false;
            }
            return ! $this->hasConflict((int) $row['id'], $planId, $start, $end);
        }));
    }

    private function assertEligible(int $planId, int $employeeId, string $skillCode, ?string $start, ?string $end): void
    {
        $candidateIds = array_column($this->candidatesFor($planId, $skillCode, $start, $end), 'id');
        if (! in_array($employeeId, array_map('intval', $candidateIds), true)) {
            throw new RuntimeException('El colaborador no está disponible, no posee la habilidad requerida o su certificación no es válida.');
        }
    }

    private function hasConflict(int $employeeId, int $currentPlanId, ?string $start, ?string $end): bool
    {
        if ($start === null || $end === null) return false;
        $db = db_connect();
        return $db->table('coordination_resource_allocations')
            ->where('employee_id', $employeeId)
            ->where('coordination_plan_id !=', $currentPlanId)
            ->where('status', 1)->where('delete_date', null)
            ->whereIn('allocation_status', ['reserved','assigned','working'])
            ->where('starts_at <', $end)
            ->where('ends_at >', $start)
            ->countAllResults() > 0;
    }

    private function insertAllocation(int $planId, ?int $equipmentId, ?int $roleId, int $employeeId, string $type, array $plan): void
    {
        $db = db_connect();
        $duplicate = $db->table('coordination_resource_allocations')
            ->where('coordination_plan_id', $planId)->where('employee_id', $employeeId)
            ->where('status', 1)->where('delete_date', null)
            ->whereIn('allocation_status', ['reserved','assigned','working'])->get()->getRowArray();
        if ($duplicate !== null) throw new RuntimeException('El colaborador ya forma parte del equipo operativo de esta coordinación.');

        $db->table('coordination_resource_allocations')->insert([
            'coordination_plan_id' => $planId,
            'equipment_id' => $equipmentId,
            'resource_role_id' => $roleId,
            'employee_id' => $employeeId,
            'allocation_type' => $type,
            'allocation_status' => 'reserved',
            'starts_at' => $plan['scheduled_start_at'],
            'ends_at' => $plan['estimated_end_at'],
            'assigned_by_user_id' => session('auth_user_id') ?: null,
            'assigned_at' => date('Y-m-d H:i:s'),
            'status' => 1,
            'entry_user' => $this->actor(),
            'entry_date' => date('Y-m-d H:i:s'),
        ]);
    }

    private function skillCodeFor(string $roleCode, ?string $categoryCode): ?string
    {
        return match ($roleCode) {
            'DRIVER' => 'DRIVER',
            'HELPER' => 'HELPER',
            'RIGGER' => 'RIGGER',
            'OPERATOR' => match ($categoryCode) {
                'CRANE' => 'CRANE_OPERATOR',
                'FORKLIFT' => 'FORKLIFT_OPERATOR',
                'TELEHANDLER' => 'TELEHANDLER_OPERATOR',
                'MANLIFT' => 'MANLIFT_OPERATOR',
                default => null,
            },
            default => null,
        };
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: 'system');
    }
}
