<?php

namespace App\Controllers;

use App\Models\WorkOrderModel;
use App\Services\MissionLogService;
use App\Services\WorkOrderChecklistService;
use App\Services\WorkOrderEvidenceService;
use App\Services\WorkOrderService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

class WorkOrdersController extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $pendingCoordinations = $db->table('coordination_plans cp')
            ->select('cp.id, cp.code, cp.scheduled_start_at, cp.location, cp.priority, service_cases.code AS service_case_code, customers.business_name')
            ->join('service_cases', 'service_cases.id = cp.service_case_id')
            ->join('customers', 'customers.id = service_cases.customer_id', 'left')
            ->join('work_orders wo', 'wo.coordination_plan_id = cp.id AND wo.delete_date IS NULL', 'left')
            ->where('cp.status', 'approved')
            ->where('cp.delete_date', null)
            ->where('wo.id', null)
            ->orderBy('cp.scheduled_start_at', 'ASC')
            ->get()->getResultArray();

        return view('work_orders/index', [
            'title' => 'Órdenes de trabajo',
            'workOrders' => (new WorkOrderModel())->workspaceList(),
            'pendingCoordinations' => $pendingCoordinations,
        ]);
    }

    public function createFromCoordination(int $coordinationId): RedirectResponse
    {
        try {
            $id = (new WorkOrderService())->createFromCoordination($coordinationId);
            return redirect()->to(route_to('work_orders.show', $id))->with('success', 'Orden de Trabajo generada correctamente.');
        } catch (Throwable $e) {
            log_message('error', 'Error generando OT desde coordinación {id}: {message}', ['id' => $coordinationId, 'message' => $e->getMessage()]);
            return redirect()->to(route_to('coordination.show', $coordinationId))->with('error', $e->getMessage());
        }
    }

    public function issue(int $id): RedirectResponse
    {
        try {
            (new WorkOrderService())->issue($id, (string) $this->request->getPost('issuance_notes'));
            return redirect()->to(route_to('work_orders.show', $id))->with('success', 'Orden de Trabajo emitida y entregada al Responsable de Misión.');
        } catch (Throwable $e) {
            log_message('error', 'Error emitiendo OT {id}: {message}', ['id' => $id, 'message' => $e->getMessage()]);
            return redirect()->to(route_to('work_orders.show', $id))->with('error', $e->getMessage());
        }
    }

    public function start(int $id): RedirectResponse
    {
        try {
            (new WorkOrderService())->start($id, (string) $this->request->getPost('start_notes'));
            return redirect()->to(route_to('work_orders.show', $id))->with('success', 'Ejecución iniciada. Personal y maquinaria están ahora en operación.');
        } catch (Throwable $e) {
            log_message('error', 'Error iniciando OT {id}: {message}', ['id' => $id, 'message' => $e->getMessage()]);
            return redirect()->to(route_to('work_orders.show', $id))->with('error', $e->getMessage());
        }
    }

    public function addMissionLog(int $id): RedirectResponse
    {
        try {
            (new MissionLogService())->addManualEntry(
                $id,
                (string) $this->request->getPost('event_type'),
                (string) $this->request->getPost('description'),
                (string) $this->request->getPost('occurred_at'),
                (string) $this->request->getPost('publish_to_case') === '1'
            );
            return redirect()->to(route_to('work_orders.show', $id))->with('success', 'Avance operativo registrado en la bitácora de la misión.');
        } catch (Throwable $e) {
            log_message('error', 'Error registrando Mission Log en OT {id}: {message}', ['id' => $id, 'message' => $e->getMessage()]);
            return redirect()->to(route_to('work_orders.show', $id))->withInput()->with('error', $e->getMessage());
        }
    }

    public function answerChecklist(int $id, int $itemId): RedirectResponse
    {
        try {
            (new WorkOrderChecklistService())->answer(
                $id,
                $itemId,
                (string) $this->request->getPost('response'),
                (string) $this->request->getPost('notes')
            );

            return redirect()->to(route_to('work_orders.show', $id) . '#operational-checklist')
                ->with('success', 'Verificación operativa actualizada.');
        } catch (Throwable $e) {
            log_message('error', 'Error actualizando checklist de OT {id}: {message}', ['id' => $id, 'message' => $e->getMessage()]);
            return redirect()->to(route_to('work_orders.show', $id) . '#operational-checklist')
                ->with('error', $e->getMessage());
        }
    }

    public function addEvidence(int $id): RedirectResponse
    {
        try {
            $file = $this->request->getFile('evidence_file');
            if ($file === null) {
                throw new RuntimeException('Seleccione un archivo de evidencia.');
            }

            (new WorkOrderEvidenceService())->store(
                $id,
                $file,
                (string) $this->request->getPost('evidence_stage'),
                (string) $this->request->getPost('evidence_description'),
                $this->nullableInt('mission_log_id')
            );

            return redirect()->to(route_to('work_orders.show', $id))->with('success', 'Evidencia almacenada y vinculada a la misión.');
        } catch (Throwable $e) {
            log_message('error', 'Error cargando evidencia en OT {id}: {message}', ['id' => $id, 'message' => $e->getMessage()]);
            return redirect()->to(route_to('work_orders.show', $id))->with('error', $e->getMessage());
        }
    }

    public function downloadEvidence(int $id, int $evidenceId)
    {
        try {
            $evidence = (new WorkOrderEvidenceService())->findForDownload($id, $evidenceId);
            return $this->response->download($evidence['absolute_path'], null)->setFileName($evidence['original_name']);
        } catch (Throwable $e) {
            log_message('error', 'Error descargando evidencia {evidence} de OT {id}: {message}', [
                'evidence' => $evidenceId,
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            return redirect()->to(route_to('work_orders.show', $id))->with('error', $e->getMessage());
        }
    }

    public function show(int $id): string
    {
        $order = (new WorkOrderModel())->detail($id);
        if ($order === null) {
            throw new RuntimeException('Orden de Trabajo no encontrada.');
        }

        $db = db_connect();
        $equipment = $db->table('work_order_equipment')->where('work_order_id', $id)->orderBy('id')->get()->getResultArray();
        $team = $db->table('work_order_team')->where('work_order_id', $id)->orderBy('allocation_type', 'DESC')->orderBy('id')->get()->getResultArray();
        $missionLogs = $db->tableExists('mission_logs')
            ? $db->table('mission_logs')->where('work_order_id', $id)->orderBy('occurred_at', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray()
            : [];
        $evidence = $db->tableExists('work_order_evidence')
            ? $db->table('work_order_evidence')->where('work_order_id', $id)->where('delete_date', null)->orderBy('occurred_at', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray()
            : [];
        $checklist = $db->tableExists('work_order_checklists')
            ? (new WorkOrderChecklistService())->ensureForWorkOrder($id)
            : null;

        return view('work_orders/show', [
            'title' => 'Orden de Trabajo ' . $order['code'],
            'order' => $order,
            'equipment' => $equipment,
            'team' => $team,
            'missionLogs' => $missionLogs,
            'missionLogEventTypes' => (new MissionLogService())->eventTypes(),
            'evidence' => $evidence,
            'checklist' => $checklist,
        ]);
    }

    private function nullableInt(string $field): ?int
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : (int) $value;
    }
}
