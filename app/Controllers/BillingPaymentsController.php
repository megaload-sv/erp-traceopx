<?php

namespace App\Controllers;

use App\Services\BillingPaymentService;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class BillingPaymentsController extends BaseController
{
    public function index(int $billingCaseId): string
    {
        $workspace = (new BillingPaymentService())->workspace($billingCaseId);

        return view('billing/payments', [
            'title' => 'Pagos ' . $workspace['billingCase']['code'],
        ] + $workspace);
    }

    public function store(int $billingCaseId): RedirectResponse
    {
        try {
            (new BillingPaymentService())->registerConfirmed($billingCaseId, $this->request->getPost());

            return redirect()->to(route_to('billing.payments.index', $billingCaseId))
                ->with('success', 'Pago registrado y confirmado. Los saldos y la política financiera fueron recalculados.');
        } catch (Throwable $e) {
            log_message('error', 'Error registrando pago de facturación: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(route_to('billing.payments.index', $billingCaseId))
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
