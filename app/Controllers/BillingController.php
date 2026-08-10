<?php

namespace App\Controllers;

use App\Services\BillingPreparationService;
use App\Services\DteDocumentService;
use App\Services\DteTaxCalculationService;
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
        $preparation = new BillingPreparationService();
        $workspace = $preparation->workspace($id);

        if (($workspace['dteDocument']['status'] ?? null) === 'draft') {
            (new DteTaxCalculationService())->recalculate((int) $workspace['dteDocument']['id']);
            $workspace = $preparation->workspace($id);
        }

        $dte = (new DteDocumentService())->workspace($id);
        $workspace['taxCatalog'] = $dte['taxCatalog'];
        $workspace['taxSummary'] = ! empty($workspace['dteDocument']['tax_summary_json'])
            ? (json_decode((string) $workspace['dteDocument']['tax_summary_json'], true) ?: [])
            : [];

        return view('billing/show', ['title' => 'Facturación ' . $workspace['billingCase']['code']] + $workspace);
    }

    public function updateItemTax(int $billingCaseId, int $itemId): RedirectResponse
    {
        try {
            $dteService = new DteDocumentService();
            $dteService->updateItemTaxClassification(
                $billingCaseId,
                $itemId,
                trim((string) $this->request->getPost('fiscal_classification')),
                trim((string) $this->request->getPost('tax_code')) ?: null
            );

            $dte = $dteService->workspace($billingCaseId);
            (new DteTaxCalculationService())->recalculate((int) $dte['document']['id']);

            return redirect()->to(route_to('billing.show', $billingCaseId) . '#dte-items')
                ->with('success', 'Clasificación fiscal actualizada y totales DTE recalculados.');
        } catch (Throwable $e) {
            log_message('error', 'Error actualizando clasificación fiscal DTE: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(route_to('billing.show', $billingCaseId) . '#dte-items')
                ->with('error', $e->getMessage());
        }
    }
}
