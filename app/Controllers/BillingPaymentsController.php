<?php

namespace App\Controllers;

use App\Services\BillingPaymentEvidenceService;
use App\Services\BillingPaymentService;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class BillingPaymentsController extends BaseController
{
    public function index(int $billingCaseId)
    {
        $evidenceId = (int) $this->request->getGet('download_evidence');
        if ($evidenceId > 0) {
            try {
                $evidence = (new BillingPaymentEvidenceService())->findForDownload($billingCaseId, $evidenceId);
                return $this->response->download($evidence['absolute_path'], null)->setFileName($evidence['original_name']);
            } catch (Throwable $e) {
                return redirect()->to(route_to('billing.payments.index', $billingCaseId))->with('error', $e->getMessage());
            }
        }

        $workspace = (new BillingPaymentService())->workspace($billingCaseId);

        return view('billing/payments', [
            'title' => 'Pagos ' . $workspace['billingCase']['code'],
        ] + $workspace);
    }

    public function store(int $billingCaseId): RedirectResponse
    {
        try {
            $file = $this->request->getFile('payment_receipt');
            (new BillingPaymentService())->registerConfirmed(
                $billingCaseId,
                $this->request->getPost(),
                $file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE ? $file : null
            );

            return redirect()->to(route_to('billing.payments.index', $billingCaseId))
                ->with('success', 'Pago registrado y confirmado. Los saldos, la evidencia y el Expediente fueron recalculados.');
        } catch (Throwable $e) {
            log_message('error', 'Error registrando pago de facturación: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(route_to('billing.payments.index', $billingCaseId))
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
