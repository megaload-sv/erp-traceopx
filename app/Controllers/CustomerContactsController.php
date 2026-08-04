<?php

namespace App\Controllers;

use App\Models\CustomerContactModel;
use App\Models\CustomerModel;
use App\Services\ActivityService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;

class CustomerContactsController extends BaseController
{
    public function create(int $customerId): string
    {
        $customer = (new CustomerModel())->find($customerId);
        if ($customer === null) {
            throw new RuntimeException('Cliente no encontrado.');
        }

        return view('customers/contacts/form', [
            'title' => 'Nuevo contacto',
            'customer' => $customer,
            'contact' => null,
        ]);
    }

    public function store(int $customerId): RedirectResponse
    {
        $customer = (new CustomerModel())->find($customerId);
        if ($customer === null) {
            return redirect()->to(route_to('customers.index'))->with('error', 'Cliente no encontrado.');
        }

        $model = new CustomerContactModel();
        $existingContacts = $model->where('customer_id', $customerId)->countAllResults();
        $makePrimary = $existingContacts === 0 || (int) $this->request->getPost('is_primary') === 1;

        $contactId = $model->insert([
            'customer_id' => $customerId,
            'name' => trim((string) $this->request->getPost('name')),
            'position' => $this->nullable('position'),
            'contact_role' => (string) $this->request->getPost('contact_role'),
            'email' => $this->nullable('email'),
            'phone' => $this->nullable('phone'),
            'is_primary' => 0,
            'status' => 1,
        ], true);

        if ($contactId === false) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        if ($makePrimary) {
            $model->makePrimary($customerId, (int) $contactId);
        }

        (new ActivityService())->record(
            'customer',
            $customerId,
            'customer.contact_added',
            'Contacto agregado',
            sprintf('Se agregó a %s como contacto %s.', trim((string) $this->request->getPost('name')), $this->roleLabel((string) $this->request->getPost('contact_role')))
        );

        return redirect()->to(route_to('customers.show', $customerId))->with('success', 'Contacto agregado correctamente.');
    }

    public function edit(int $customerId, int $contactId): string
    {
        $customer = (new CustomerModel())->find($customerId);
        $contact = (new CustomerContactModel())
            ->where('customer_id', $customerId)
            ->find($contactId);

        if ($customer === null || $contact === null) {
            throw new RuntimeException('Contacto no encontrado.');
        }

        return view('customers/contacts/form', [
            'title' => 'Editar contacto',
            'customer' => $customer,
            'contact' => $contact,
        ]);
    }

    public function update(int $customerId, int $contactId): RedirectResponse
    {
        $model = new CustomerContactModel();
        $contact = $model->where('customer_id', $customerId)->find($contactId);
        if ($contact === null) {
            return redirect()->to(route_to('customers.show', $customerId))->with('error', 'Contacto no encontrado.');
        }

        $updated = $model->update($contactId, [
            'name' => trim((string) $this->request->getPost('name')),
            'position' => $this->nullable('position'),
            'contact_role' => (string) $this->request->getPost('contact_role'),
            'email' => $this->nullable('email'),
            'phone' => $this->nullable('phone'),
            'status' => (int) $this->request->getPost('status'),
        ]);

        if (! $updated) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        if ((int) $this->request->getPost('is_primary') === 1) {
            $model->makePrimary($customerId, $contactId);
        }

        (new ActivityService())->record('customer', $customerId, 'customer.contact_updated', 'Contacto actualizado', 'Se actualizaron los datos de contacto.');

        return redirect()->to(route_to('customers.show', $customerId))->with('success', 'Contacto actualizado correctamente.');
    }

    public function makePrimary(int $customerId, int $contactId): RedirectResponse
    {
        $model = new CustomerContactModel();
        $contact = $model->where('customer_id', $customerId)->find($contactId);
        if ($contact === null) {
            return redirect()->to(route_to('customers.show', $customerId))->with('error', 'Contacto no encontrado.');
        }

        $model->makePrimary($customerId, $contactId);

        (new ActivityService())->record(
            'customer',
            $customerId,
            'customer.primary_contact_changed',
            'Contacto principal actualizado',
            sprintf('%s fue seleccionado como contacto principal.', $contact['name'])
        );

        return redirect()->to(route_to('customers.show', $customerId))->with('success', 'Contacto principal actualizado.');
    }

    private function nullable(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));

        return $value === '' ? null : $value;
    }

    private function roleLabel(string $role): string
    {
        return [
            'commercial' => 'comercial',
            'technical' => 'técnico',
            'billing' => 'de facturación',
            'other' => 'general',
        ][$role] ?? 'general';
    }
}
