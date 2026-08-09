<?php

namespace App\Controllers;

use App\Services\ResourceAllocationService;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class CoordinationResourcesController extends BaseController
{
    public function reserveRole(int $planId): RedirectResponse
    {
        $equipmentId = (int) $this->request->getPost('equipment_id');
        $roleId = (int) $this->request->getPost('role_id');
        $employeeId = (int) $this->request->getPost('employee_id');

        try {
            if ($equipmentId <= 0 || $roleId <= 0 || $employeeId <= 0) {
                throw new \RuntimeException('Seleccione un colaborador válido para la función.');
            }

            (new ResourceAllocationService())->reserveRole($planId, $equipmentId, $roleId, $employeeId);
            $this->recordEvent($planId, 'coordination.resource_reserved', 'Recurso humano reservado', 'Se reservó un colaborador para una función operativa.');

            return redirect()->to(route_to('coordination.show', $planId))->with('success', 'Colaborador reservado correctamente.');
        } catch (Throwable $e) {
            log_message('error', 'Error reservando recurso operativo: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(route_to('coordination.show', $planId))->with('error', $e->getMessage());
        }
    }

    public function reserveMissionLeader(int $planId): RedirectResponse
    {
        $employeeId = (int) $this->request->getPost('employee_id');

        try {
            if ($employeeId <= 0) {
                throw new \RuntimeException('Seleccione un responsable de misión válido.');
            }

            (new ResourceAllocationService())->reserveMissionLeader($planId, $employeeId);
            $this->recordEvent($planId, 'coordination.mission_leader_reserved', 'Responsable de misión asignado', 'Se definió el responsable operativo de la misión.');

            return redirect()->to(route_to('coordination.show', $planId))->with('success', 'Responsable de misión reservado correctamente.');
        } catch (Throwable $e) {
            log_message('error', 'Error reservando responsable de misión: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(route_to('coordination.show', $planId))->with('error', $e->getMessage());
        }
    }

    public function release(int $planId, int $allocationId): RedirectResponse
    {
        try {
            $plan = db_connect()->table('coordination_plans')
                ->where('id', $planId)
                ->where('delete_date', null)
                ->get()->getRowArray();

            if ($plan === null) {
                throw new \RuntimeException('Plan de coordinación no encontrado.');
            }
            if ($plan['status'] !== 'draft') {
                throw new \RuntimeException('La coordinación ya fue aprobada. Cualquier sustitución deberá realizarse mediante el flujo controlado de cambios.');
            }

            (new ResourceAllocationService())->release($planId, $allocationId);
            $this->recordEvent($planId, 'coordination.resource_released', 'Recurso liberado', 'Se liberó una asignación de la coordinación antes de su aprobación.');

            return redirect()->to(route_to('coordination.show', $planId))->with('success', 'Recurso liberado correctamente.');
        } catch (Throwable $e) {
            log_message('error', 'Error liberando recurso: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(route_to('coordination.show', $planId))->with('error', $e->getMessage());
        }
    }

    private function recordEvent(int $planId, string $eventCode, string $title, string $description): void
    {
        $db = db_connect();
        $plan = $db->table('coordination_plans')->where('id', $planId)->get()->getRowArray();
        if ($plan === null) {
            return;
        }

        $db->table('service_case_events')->insert([
            'service_case_id' => (int) $plan['service_case_id'],
            'event_code' => $eventCode,
            'title' => $title,
            'description' => $description,
            'entity_type' => 'coordination_plan',
            'entity_id' => $planId,
            'occurred_at' => date('Y-m-d H:i:s'),
            'entry_user' => (string) (session('auth_user_email') ?: 'system'),
            'entry_date' => date('Y-m-d H:i:s'),
        ]);
    }
}
