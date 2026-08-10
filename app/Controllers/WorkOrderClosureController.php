<?php

namespace App\Controllers;

use App\Services\WorkOrderClosureService;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class WorkOrderClosureController extends BaseController
{
    public function close(int $id): RedirectResponse
    {
        try {
            (new WorkOrderClosureService())->close(
                $id,
                (string) $this->request->getPost('closure_notes')
            );

            return redirect()->to(route_to('work_orders.show', $id) . '#formal-closure')
                ->with('success', 'Orden de Trabajo cerrada formalmente. El Expediente quedó preparado para facturación.');
        } catch (Throwable $e) {
            log_message('error', 'Error cerrando formalmente OT {id}: {message}', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->to(route_to('work_orders.show', $id) . '#formal-closure')
                ->with('error', $e->getMessage());
        }
    }
}
