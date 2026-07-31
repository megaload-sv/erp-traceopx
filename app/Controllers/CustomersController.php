<?php

namespace App\Controllers;

use App\Models\ActivityEventModel;
use App\Models\CustomerAddressModel;
use App\Models\CustomerContactModel;
use App\Models\CustomerModel;
use App\Services\ActivityService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

class CustomersController extends BaseController
{
    public function index(): string
    {
        $search = trim((string) $this->request->getGet('q'));
        $model = new CustomerModel();

        return view('customers/index', [
            'title' => 'Clientes',
            'customers' => $model->searchList($search),
            'metrics' => $model->dashboardMetrics(),
            'search' => $search,
        ]);
    }

    public function create(): string
    {
        return view('customers/form', [
            'title' => 'Nuevo cliente',
            'customer' => null,
        ]);
    }

    public function store(): RedirectResponse
    {
        $db = db_connect();
        $db->transBegin();

        try {
            $customers = new CustomerModel();
            $customerId = $customers->insert([
                'uuid' => $this->uuidV4(),
                'code' => $customers->nextCode(),
                'customer_type' => (string) $this->request->getPost('customer_type'),
                'lifecycle_stage' => (string) ($this->request->getPost('lifecycle_stage') ?: 'potential'),
                'relationship_tier' => (string) ($this->request->getPost('relationship_tier') ?: 'standard'),
                'assigned_sales_user' => $this->nullable('assigned_sales_user'),
                'next_follow_up_date' => $this->nullable('next_follow_up_date'),
                'business_name' => trim((string) $this->request->getPost('business_name')),
                'trade_name' => $this->nullable('trade_name'),
                'tax_id' => $this->nullable('tax_id'),
                'registration_number' => $this->nullable('registration_number'),
                'email' => $this->nullable('email'),
                'phone' => $this->nullable('phone'),
                'website' => $this->nullable('website'),
                'notes' => $this->nullable('notes'),
                'status' => 1,
            ], true);

            if ($customerId === false) {
                return $this->rollbackWithErrors($db, $customers->errors());
            }

            $customerId = (int) $customerId;
            $this->storeOptionalContact($customerId);
            $this->storeOptionalAddress($customerId);

            (new ActivityService())->record(
                'customer',
                $customerId,
                'customer.created',
                'Cliente creado',
                'Se registró el cliente en ERP TraceOPX.'
            );

            $db->transCommit();

            return redirect()->to(route_to('customers.show', $customerId))
                ->with('success', 'Cliente creado correctamente.');
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error creando cliente: {message}', ['message' => $e->getMessage()]);

            return redirect()->back()->withInput()->with('error', 'No fue posible crear el cliente.');
        }
    }

    public function show(int $id): string
    {
        $customer = (new CustomerModel())->find($id);
        if ($customer === null) {
            throw new RuntimeException('Cliente no encontrado.');
        }

        return view('customers/show', [
            'title' => $customer['business_name'],
            'customer' => $customer,
            'contacts' => (new CustomerContactModel())->where('customer_id', $id)->findAll(),
            'addresses' => (new CustomerAddressModel())->where('customer_id', $id)->findAll(),
            'activities' => (new ActivityEventModel())->forEntity('customer', $id),
            'commercialSummary' => [
                'quotations' => 0,
                'activeOrders' => 0,
                'invoiced' => 0.00,
                'receivable' => 0.00,
            ],
        ]);
    }

    public function edit(int $id): string
    {
        $customer = (new CustomerModel())->find($id);
        if ($customer === null) {
            throw new RuntimeException('Cliente no encontrado.');
        }

        return view('customers/form', [
            'title' => 'Editar cliente',
            'customer' => $customer,
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        $model = new CustomerModel();
        $customer = $model->find($id);
        if ($customer === null) {
            return redirect()->to(route_to('customers.index'))->with('error', 'Cliente no encontrado.');
        }

        $updated = $model->update($id, [
            'customer_type' => (string) $this->request->getPost('customer_type'),
            'lifecycle_stage' => (string) $this->request->getPost('lifecycle_stage'),
            'relationship_tier' => (string) $this->request->getPost('relationship_tier'),
            'assigned_sales_user' => $this->nullable('assigned_sales_user'),
            'next_follow_up_date' => $this->nullable('next_follow_up_date'),
            'business_name' => trim((string) $this->request->getPost('business_name')),
            'trade_name' => $this->nullable('trade_name'),
            'tax_id' => $this->nullable('tax_id'),
            'registration_number' => $this->nullable('registration_number'),
            'email' => $this->nullable('email'),
            'phone' => $this->nullable('phone'),
            'website' => $this->nullable('website'),
            'notes' => $this->nullable('notes'),
            'status' => (int) $this->request->getPost('status'),
        ]);

        if (! $updated) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        (new ActivityService())->record('customer', $id, 'customer.updated', 'Cliente actualizado', 'Se actualizaron los datos generales y comerciales del cliente.');

        return redirect()->to(route_to('customers.show', $id))->with('success', 'Cliente actualizado correctamente.');
    }

    private function storeOptionalContact(int $customerId): void
    {
        $name = trim((string) $this->request->getPost('contact_name'));
        if ($name === '') {
            return;
        }

        $model = new CustomerContactModel();
        if ($model->insert([
            'customer_id' => $customerId,
            'name' => $name,
            'position' => $this->nullable('contact_position'),
            'email' => $this->nullable('contact_email'),
            'phone' => $this->nullable('contact_phone'),
            'is_primary' => 1,
            'status' => 1,
        ]) === false) {
            throw new RuntimeException(implode(' ', $model->errors()));
        }

        (new ActivityService())->record('customer', $customerId, 'customer.contact_added', 'Contacto principal agregado', "Se agregó a {$name} como contacto principal.");
    }

    private function storeOptionalAddress(int $customerId): void
    {
        $address = trim((string) $this->request->getPost('address_line'));
        if ($address === '') {
            return;
        }

        $model = new CustomerAddressModel();
        if ($model->insert([
            'customer_id' => $customerId,
            'address_type' => 'fiscal',
            'address_line' => $address,
            'municipality' => $this->nullable('municipality'),
            'department' => $this->nullable('department'),
            'country' => $this->nullable('country') ?? 'El Salvador',
            'is_primary' => 1,
            'status' => 1,
        ]) === false) {
            throw new RuntimeException(implode(' ', $model->errors()));
        }

        (new ActivityService())->record('customer', $customerId, 'customer.address_added', 'Dirección fiscal agregada', 'Se registró la dirección fiscal principal.');
    }

    private function nullable(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : $value;
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function rollbackWithErrors($db, array $errors): RedirectResponse
    {
        $db->transRollback();
        return redirect()->back()->withInput()->with('errors', $errors);
    }
}
