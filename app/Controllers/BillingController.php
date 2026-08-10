<?php

namespace App\Controllers;

use App\Services\BillingPreparationService;
use App\Services\DteDocumentService;
use App\Services\DteJsonBuilderService;
use App\Services\DteReceiverService;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class BillingController extends BaseController
{
    public function index(): string
    {
        return view('billing/index', [
            'title' => 'Facturación',
            'cases' => (new BillingPreparationService())->eligibleCases(),
            'documentTypes' => BillingPreparationService::DOCUMENT_TYPES,
        ]);
    }

    public function prepare(int $serviceCaseId): RedirectResponse
    {
        try {
            $id = (new BillingPreparationService())->createFromServiceCase(
                $serviceCaseId,
                trim((string) $this->request->getPost('document_type')),
                trim((string) $this->request->getPost('notes'))
            );
            return redirect()->to(route_to('billing.show', $id))->with('success', 'Preparación de facturación creada correctamente.');
        } catch (Throwable $e) {
            log_message('error', 'Error preparando facturación: {message}', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(int $id): string
    {
        $workspace = (new BillingPreparationService())->workspace($id);
        $dte = (new DteDocumentService())->workspace($id);
        $receiver = new DteReceiverService();
        $jsonPreview = (new DteJsonBuilderService())->buildForBillingCase($id);

        $workspace['dteDocument'] = $dte['document'];
        $workspace['dteItems'] = $dte['items'];
        $workspace['taxCatalog'] = $dte['taxCatalog'];
        $workspace['receiverCatalogs'] = $dte['receiverCatalogs'];
        $workspace['receiverIssues'] = $dte['receiverIssues'];
        $workspace['receiverMeta'] = $receiver->snapshotMeta((int) $dte['document']['id']);
        $workspace['dteJsonPreview'] = $jsonPreview;
        $workspace['taxSummary'] = ! empty($dte['document']['tax_summary_json'])
            ? (json_decode((string) $dte['document']['tax_summary_json'], true) ?: [])
            : [];

        return view('billing/show', ['title' => 'Facturación ' . $workspace['billingCase']['code']] + $workspace);
    }

    public function jsonPreview(int $billingCaseId)
    {
        try {
            $preview = (new DteJsonBuilderService())->buildForBillingCase($billingCaseId);
            return $this->response
                ->setContentType('application/json')
                ->setBody($preview['json']);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function updateItemTax(int $billingCaseId, int $itemId): RedirectResponse
    {
        try {
            (new DteDocumentService())->updateItemTaxClassification(
                $billingCaseId,
                $itemId,
                trim((string) $this->request->getPost('fiscal_classification')),
                trim((string) $this->request->getPost('tax_code')) ?: null
            );

            return redirect()->to(route_to('billing.show', $billingCaseId) . '#dte-items')
                ->with('success', 'Clasificación fiscal actualizada y totales DTE recalculados.');
        } catch (Throwable $e) {
            log_message('error', 'Error actualizando clasificación fiscal DTE: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(route_to('billing.show', $billingCaseId) . '#dte-items')
                ->with('error', $e->getMessage());
        }
    }

    public function updateReceiver(int $billingCaseId): RedirectResponse
    {
        try {
            $result = (new DteReceiverService())->update($billingCaseId, $this->request->getPost());
            $message = ($result['receiver_validation_status'] ?? null) === 'valid'
                ? 'Receptor fiscal actualizado y validado correctamente.'
                : 'Receptor fiscal actualizado. Aún existen datos pendientes para este tipo de DTE.';

            return redirect()->to(route_to('billing.show', $billingCaseId) . '#receiver')->with('success', $message);
        } catch (Throwable $e) {
            log_message('error', 'Error actualizando receptor fiscal DTE: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(route_to('billing.show', $billingCaseId) . '#receiver')->withInput()->with('error', $e->getMessage());
        }
    }

    public function restoreReceiver(int $billingCaseId): RedirectResponse
    {
        try {
            (new DteReceiverService())->restoreFromCustomer($billingCaseId);
            return redirect()->to(route_to('billing.show', $billingCaseId) . '#receiver')
                ->with('success', 'Snapshot del receptor restaurado desde los datos fiscales actuales del Cliente.');
        } catch (Throwable $e) {
            log_message('error', 'Error restaurando receptor fiscal DTE: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(route_to('billing.show', $billingCaseId) . '#receiver')
                ->with('error', $e->getMessage());
        }
    }
}
