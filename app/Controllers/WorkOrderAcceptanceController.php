<?php

namespace App\Controllers;

use App\Services\WorkOrderAcceptanceService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

class WorkOrderAcceptanceController extends BaseController
{
    public function store(int $workOrderId): RedirectResponse
    {
        try {
            $signature = $this->request->getFile('signature_file');
            (new WorkOrderAcceptanceService())->record($workOrderId, [
                'customer_contact_id' => $this->request->getPost('customer_contact_id'),
                'result' => $this->request->getPost('result'),
                'receiver_name' => $this->request->getPost('receiver_name'),
                'receiver_position' => $this->request->getPost('receiver_position'),
                'receiver_email' => $this->request->getPost('receiver_email'),
                'receiver_phone' => $this->request->getPost('receiver_phone'),
                'accepted_at' => $this->request->getPost('accepted_at'),
                'observations' => $this->request->getPost('observations'),
            ], $signature);

            return redirect()->to(route_to('work_orders.show', $workOrderId) . '#customer-acceptance')
                ->with('success', 'Recepción del cliente registrada correctamente.');
        } catch (Throwable $e) {
            log_message('error', 'Error registrando aceptación de OT {id}: {message}', [
                'id' => $workOrderId,
                'message' => $e->getMessage(),
            ]);
            return redirect()->to(route_to('work_orders.show', $workOrderId) . '#customer-acceptance')
                ->withInput()->with('error', $e->getMessage());
        }
    }

    public function signature(int $workOrderId, int $acceptanceId)
    {
        try {
            $record = (new WorkOrderAcceptanceService())->findSignature($workOrderId, $acceptanceId);
            return $this->response
                ->download($record['absolute_path'], null)
                ->setFileName((string) ($record['signature_original_name'] ?: 'aceptacion-cliente'));
        } catch (Throwable $e) {
            log_message('error', 'Error descargando firma de aceptación {acceptance}: {message}', [
                'acceptance' => $acceptanceId,
                'message' => $e->getMessage(),
            ]);
            return redirect()->to(route_to('work_orders.show', $workOrderId) . '#customer-acceptance')
                ->with('error', $e->getMessage());
        }
    }
}
