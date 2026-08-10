<?php

namespace App\Controllers;

use App\Services\BillingPreparationService;
use App\Services\DteDocumentService;
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
                trim((string)$this->request->getPost('document_type')),
                trim((string)$this->request->getPost('notes'))
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
        $workspace['taxCatalog'] = $dte['taxCatalog'];

        return view('billing/show', ['title' => 'Facturación ' . $workspace['billingCase']['code']] + $workspace);
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
                ->with('success', 'Clasificación fiscal del ítem actualizada.');
        } catch (Throwable $e) {
            log_message('error', 'Error actualizando clasificación fiscal DTE: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(route_to('billing.show', $billingCaseId) . '#dte-items')
                ->with('error', $e->getMessage());
        }
    }
}
