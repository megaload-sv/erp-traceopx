<?php

namespace App\Controllers;

use App\Models\QuotationModel;
use App\Services\ActivityService;
use App\Services\QuotationAcceptanceService;
use App\Services\QuotationWorkflowService;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class QuotationWorkflowController extends BaseController
{
    public function transition(int $quotationId): RedirectResponse
    {
        $target = trim((string) $this->request->getPost('target_status'));
        $model = new QuotationModel();
        $quotation = $model->find($quotationId);

        if ($quotation === null) {
            return redirect()->to(route_to('quotations.index'))->with('error', 'Cotización no encontrada.');
        }

        try {
            (new QuotationWorkflowService())->assertCanTransition((string) $quotation['status'], $target);
            $model->update($quotationId, ['status' => $target]);
            (new ActivityService())->record('quotation', $quotationId, 'quotation.status_changed', 'Estado de cotización actualizado', $quotation['status'] . ' → ' . $target);
            return redirect()->to(route_to('quotations.show', $quotationId))->with('success', 'La cotización avanzó correctamente en el flujo comercial.');
        } catch (Throwable $e) {
            return redirect()->to(route_to('quotations.show', $quotationId))->with('error', $e->getMessage());
        }
    }

    public function accept(int $quotationId): RedirectResponse
    {
        $acceptanceType = trim((string) $this->request->getPost('acceptance_type'));
        $fiscalType = trim((string) $this->request->getPost('fiscal_document_type'));
        $allowedAcceptance = ['signed_document', 'email', 'authorized_confirmation', 'other'];
        $allowedFiscal = ['tax_credit_invoice', 'consumer_invoice', 'export_invoice', 'pending'];

        if (! in_array($acceptanceType, $allowedAcceptance, true) || ! in_array($fiscalType, $allowedFiscal, true)) {
            return redirect()->back()->withInput()->with('error', 'Seleccione valores válidos para la aceptación.');
        }

        $evidencePath = null;
        $originalName = null;
        $file = $this->request->getFile('acceptance_evidence');

        if ($file !== null && $file->isValid() && ! $file->hasMoved()) {
            if ($file->getSize() > 10 * 1024 * 1024) {
                return redirect()->back()->withInput()->with('error', 'La evidencia no puede superar 10 MB.');
            }
            $allowedMime = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
            if (! in_array($file->getMimeType(), $allowedMime, true)) {
                return redirect()->back()->withInput()->with('error', 'La evidencia debe ser PDF, JPG, PNG o WEBP.');
            }

            $uploadDirectory = WRITEPATH . 'uploads/quotation_acceptances';
            if (! is_dir($uploadDirectory) && ! mkdir($uploadDirectory, 0775, true) && ! is_dir($uploadDirectory)) {
                return redirect()->back()->withInput()->with('error', 'No fue posible preparar el almacenamiento de evidencias.');
            }

            $originalName = $file->getClientName();
            $storedName = $file->getRandomName();
            $file->move($uploadDirectory, $storedName);
            $evidencePath = 'quotation_acceptances/' . $storedName;
        }

        if (in_array($acceptanceType, ['signed_document', 'email'], true) && $evidencePath === null) {
            return redirect()->back()->withInput()->with('error', 'Adjunte la evidencia de aceptación correspondiente.');
        }

        $acceptedAt = str_replace('T', ' ', trim((string) $this->request->getPost('accepted_at')));
        if ($acceptedAt !== '' && strlen($acceptedAt) === 16) {
            $acceptedAt .= ':00';
        }

        $contactId = (int) $this->request->getPost('customer_contact_id');

        try {
            $caseId = (new QuotationAcceptanceService())->accept($quotationId, [
                'accepted_at' => $acceptedAt ?: date('Y-m-d H:i:s'),
                'customer_contact_id' => $contactId > 0 ? $contactId : null,
                'accepted_by_name' => (string) $this->request->getPost('accepted_by_name'),
                'acceptance_type' => $acceptanceType,
                'fiscal_document_type' => $fiscalType,
                'evidence_path' => $evidencePath,
                'evidence_original_name' => $originalName,
                'notes' => (string) $this->request->getPost('notes'),
            ]);
            return redirect()->to(route_to('service_cases.show', $caseId))->with('success', 'Cotización aceptada y expediente creado correctamente.');
        } catch (Throwable $e) {
            if ($evidencePath !== null) {
                @unlink(WRITEPATH . 'uploads/' . $evidencePath);
            }
            log_message('error', 'Error aceptando cotización {id}: {message}', ['id' => $quotationId, 'message' => $e->getMessage()]);
            return redirect()->to(route_to('quotations.show', $quotationId))->with('error', $e->getMessage());
        }
    }
}
