<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

class CoordinationPlanningController extends BaseController
{
    public function update(int $planId): RedirectResponse
    {
        $db = db_connect();

        try {
            $plan = $db->table('coordination_plans')
                ->where('id', $planId)
                ->where('delete_date', null)
                ->get()->getRowArray();

            if ($plan === null) {
                throw new RuntimeException('Plan de coordinación no encontrado.');
            }
            if ($plan['status'] !== 'draft') {
                throw new RuntimeException('La planificación ya fue aprobada y no puede modificarse directamente.');
            }

            $scheduledStart = $this->dateTime('scheduled_start_at');
            $estimatedEnd = $this->dateTime('estimated_end_at');
            $location = trim((string) $this->request->getPost('location'));
            $scopeNotes = trim((string) $this->request->getPost('scope_notes'));

            if ($scheduledStart === null || $estimatedEnd === null) {
                throw new RuntimeException('Defina la fecha programada y el fin estimado.');
            }
            if (strtotime($estimatedEnd) <= strtotime($scheduledStart)) {
                throw new RuntimeException('El fin estimado debe ser posterior a la fecha programada.');
            }
            if ($location === '') {
                throw new RuntimeException('Ingrese el lugar de ejecución.');
            }
            if ($scopeNotes === '') {
                throw new RuntimeException('Ingrese el alcance operativo.');
            }

            $db->transBegin();
            $db->table('coordination_plans')->where('id', $planId)->update([
                'scheduled_start_at' => $scheduledStart,
                'estimated_end_at' => $estimatedEnd,
                'location' => $location,
                'location_reference' => $this->nullable('location_reference'),
                'priority' => (string) ($this->request->getPost('priority') ?: 'normal'),
                'scope_notes' => $scopeNotes,
                'coordination_notes' => $this->nullable('coordination_notes'),
                'modify_user' => $this->actor(),
                'modify_date' => date('Y-m-d H:i:s'),
            ]);

            // Las reservas existentes deben seguir el nuevo rango de programación.
            $db->table('coordination_resource_allocations')
                ->where('coordination_plan_id', $planId)
                ->where('status', 1)
                ->where('delete_date', null)
                ->where('allocation_status', 'reserved')
                ->update([
                    'starts_at' => $scheduledStart,
                    'ends_at' => $estimatedEnd,
                    'modify_user' => $this->actor(),
                    'modify_date' => date('Y-m-d H:i:s'),
                ]);

            $db->table('service_case_events')->insert([
                'service_case_id' => (int) $plan['service_case_id'],
                'event_code' => 'coordination.planning_updated',
                'title' => 'Planificación operativa actualizada',
                'description' => 'Se actualizaron programación, ubicación o alcance antes de la aprobación.',
                'occurred_at' => date('Y-m-d H:i:s'),
                'entry_user' => $this->actor(),
                'entry_date' => date('Y-m-d H:i:s'),
            ]);

            $db->transCommit();
            return redirect()->to(route_to('coordination.show', $planId))->with('success', 'Planificación operativa actualizada correctamente.');
        } catch (Throwable $e) {
            if ($db->transStatus() === false) {
                $db->transRollback();
            }
            log_message('error', 'Error actualizando planificación {id}: {message}', [
                'id' => $planId,
                'message' => $e->getMessage(),
            ]);
            return redirect()->to(route_to('coordination.show', $planId))->with('error', $e->getMessage());
        }
    }

    private function dateTime(string $field): ?string
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

    private function nullable(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : $value;
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: 'system');
    }
}
