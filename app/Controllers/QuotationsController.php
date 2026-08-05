<?php

namespace App\Controllers;

use App\Models\QuotationModel;
use App\Services\QuotationService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

class QuotationsController extends BaseController
{
    public function index(): string
    {
        $quotations = (new QuotationModel())
            ->select('quotations.*, customers.business_name, users.name AS assigned_user_name, payment_terms.name AS payment_term_name')
            ->join('customers', 'customers.id = quotations.customer_id', 'left')
            ->join('users', 'users.id = quotations.assigned_user_id', 'left')
            ->join('payment_terms', 'payment_terms.id = quotations.payment_term_id', 'left')
            ->orderBy('quotations.quotation_date', 'DESC')
            ->orderBy('quotations.id', 'DESC')
            ->findAll();

        return view('quotations/index', [
            'title' => 'Cotizaciones',
            'quotations' => $quotations,
            'metrics' => [
                'total' => count($quotations),
                'draft' => count(array_filter($quotations, static fn (array $q): bool => $q['status'] === 'draft')),
                'sent' => count(array_filter($quotations, static fn (array $q): bool => $q['status'] === 'sent')),
                'accepted' => count(array_filter($quotations, static fn (array $q): bool => $q['status'] === 'accepted')),
            ],
        ]);
    }

    public function create(): string
    {
        $db = db_connect();
        $users = $db->table('users')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
        $currentEmail = (string) session('auth_user_email');
        $defaultUserId = null;

        foreach ($users as $user) {
            if ($currentEmail !== '' && strcasecmp((string) ($user['email'] ?? ''), $currentEmail) === 0) {
                $defaultUserId = (int) $user['id'];
                break;
            }
        }

        return view('quotations/form', [
            'title' => 'Nueva cotización',
            'customers' => $db->table('customers')->where('status', 1)->orderBy('business_name')->get()->getResultArray(),
            'contacts' => $db->table('customer_contacts')->where('status', 1)->orderBy('is_primary', 'DESC')->orderBy('name')->get()->getResultArray(),
            'users' => $users,
            'paymentTerms' => $db->table('payment_terms')->where('status', 1)->orderBy('name')->get()->getResultArray(),
            'defaultUserId' => $defaultUserId,
            'commercialRequestId' => $this->request->getGet('commercial_request_id'),
        ]);
    }

    public function store(): RedirectResponse
    {
        $db = db_connect();
        $customerId = (int) $this->request->getPost('customer_id');
        $assignedUserId = (int) $this->request->getPost('assigned_user_id');
        $contactId = (int) $this->request->getPost('contact_id');

        $customer = $db->table('customers')->where('id', $customerId)->where('status', 1)->get()->getRowArray();
        $user = $db->table('users')->where('id', $assignedUserId)->where('is_active', 1)->get()->getRowArray();
        $contact = $contactId > 0
            ? $db->table('customer_contacts')->where('id', $contactId)->where('customer_id', $customerId)->where('status', 1)->get()->getRowArray()
            : null;

        if ($customer === null) {
            return redirect()->back()->withInput()->with('error', 'Seleccione un cliente válido.');
        }
        if ($user === null) {
            return redirect()->back()->withInput()->with('error', 'Seleccione un agente comercial válido.');
        }
        if ($contactId > 0 && $contact === null) {
            return redirect()->back()->withInput()->with('error', 'El contacto seleccionado no pertenece al cliente.');
        }

        $subject = trim((string) $this->request->getPost('subject'));
        if ($subject === '') {
            return redirect()->back()->withInput()->with('error', 'Ingrese el asunto de la cotización.');
        }

        $db->transStart();

        try {
            $quotationId = (new QuotationService())->createDraft([
                'commercial_request_id' => $this->nullableInt('commercial_request_id'),
                'customer_id' => $customerId,
                'assigned_user_id' => $assignedUserId,
                'payment_term_id' => $this->nullableInt('payment_term_id'),
                'origin_type' => $this->nullableInt('commercial_request_id') ? 'commercial_request' : 'direct',
                'subject' => $subject,
                'quotation_date' => (string) ($this->request->getPost('quotation_date') ?: date('Y-m-d')),
                'validity_days' => max(1, (int) ($this->request->getPost('validity_days') ?: 30)),
                'terms_and_conditions' => null,
                'agent_name_snapshot' => $user['name'] ?? null,
                'agent_email_snapshot' => $user['email'] ?? null,
                'agent_phone_snapshot' => $user['phone'] ?? null,
            ]);

            if ($contact !== null) {
                $db->table('quotation_recipients')->insert([
                    'quotation_id' => $quotationId,
                    'customer_contact_id' => (int) $contact['id'],
                    'is_primary' => 1,
                    'entry_user' => (string) (session('auth_user_email') ?: 'system'),
                    'entry_date' => date('Y-m-d H:i:s'),
                ]);
            }

            $db->transComplete();

            if (! $db->transStatus()) {
                throw new RuntimeException('No fue posible guardar la cotización.');
            }

            return redirect()->to(route_to('quotations.show', $quotationId))->with('success', 'Cotización creada como borrador.');
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error creando cotización: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with('error', 'No fue posible crear el borrador de cotización.');
        }
    }

    public function show(int $id): string
    {
        $quotation = (new QuotationModel())
            ->select('quotations.*, customers.business_name, customers.trade_name, users.name AS assigned_user_name, payment_terms.name AS payment_term_name')
            ->join('customers', 'customers.id = quotations.customer_id', 'left')
            ->join('users', 'users.id = quotations.assigned_user_id', 'left')
            ->join('payment_terms', 'payment_terms.id = quotations.payment_term_id', 'left')
            ->find($id);

        if ($quotation === null) {
            throw new RuntimeException('Cotización no encontrada.');
        }

        $recipient = db_connect()->table('quotation_recipients')
            ->select('quotation_recipients.*, customer_contacts.name, customer_contacts.email, customer_contacts.phone')
            ->join('customer_contacts', 'customer_contacts.id = quotation_recipients.customer_contact_id', 'left')
            ->where('quotation_recipients.quotation_id', $id)
            ->where('quotation_recipients.delete_date', null)
            ->orderBy('quotation_recipients.is_primary', 'DESC')
            ->get()
            ->getRowArray();

        return view('quotations/show', [
            'title' => 'Cotización ' . $quotation['code'],
            'quotation' => $quotation,
            'recipient' => $recipient,
        ]);
    }

    private function nullableInt(string $field): ?int
    {
        $value = trim((string) $this->request->getPost($field));

        return $value === '' ? null : (int) $value;
    }
}
