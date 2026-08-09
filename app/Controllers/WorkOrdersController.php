<?php

namespace App\Controllers;

use App\Models\WorkOrderModel;
use App\Services\WorkOrderService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

class WorkOrdersController extends BaseController
{
    public function index(): string
    {
        return view('work_orders/index', [
            'title' => 'Órdenes de trabajo',
            'workOrders' => (new WorkOrderModel())->workspaceList(),
        ]);
    }

    public function createFromCoordination(int $coordinationId): RedirectResponse
    {
        try {
            $id = (new WorkOrderService())->createFromCoordination($coordinationId);
            return redirect()->to(route_to('work_orders.show', $id))
                ->with('success', 'Orden de Trabajo generada correctamente.');
        } catch (Throwable $e) {
            log_message('error', 'Error generando OT desde coordinación {id}: {message}', [
                'id' => $coordinationId,
                'message' => $e->getMessage(),
            ]);
            return redirect()->to(route_to('coordination.show', $coordinationId))->with('error', $e->getMessage());
        }
    }

    public function show(int $id): string
    {
        $order = (new WorkOrderModel())->detail($id);
        if ($order === null) {
            throw new RuntimeException('Orden de Trabajo no encontrada.');
        }

        $db = db_connect();
        $equipment = $db->table('work_order_equipment')
            ->where('work_order_id', $id)
            ->orderBy('id')->get()->getResultArray();
        $team = $db->table('work_order_team')
            ->where('work_order_id', $id)
            ->orderBy('allocation_type', 'DESC')
            ->orderBy('id')->get()->getResultArray();

        return view('work_orders/show', [
            'title' => 'Orden de Trabajo ' . $order['code'],
            'order' => $order,
            'equipment' => $equipment,
            'team' => $team,
        ]);
    }
}
