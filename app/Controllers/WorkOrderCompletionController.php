<?php

namespace App\Controllers;

use App\Services\WorkOrderCompletionService;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class WorkOrderCompletionController extends BaseController
{
    public function finish(int $id): RedirectResponse
    {
        try {
            (new WorkOrderCompletionService())->finish(
                $id,
                (string) $this->request->getPost('finished_at'),
                (string) $this->request->getPost('completion_summary'),
                (string) $this->request->getPost('completion_notes')
            );

            return redirect()->to(route_to('work_orders.show', $id))
                ->with('success', 'Trabajo operativo finalizado. El Expediente quedó pendiente de aceptación del cliente.');
        } catch (Throwable $e) {
            log_message('error', 'Error finalizando operativamente OT {id}: {message}', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->to(route_to('work_orders.show', $id) . '#operational-completion')
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
