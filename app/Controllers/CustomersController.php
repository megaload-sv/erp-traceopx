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
        return view('customers/form', $this->formData('Nuevo cliente', null));
    }

    public function store(): RedirectResponse
    {
        $payload = $this->customerPayload();
        if (($error = $this->geographyError($payload)) !== null) {
            return redirect()->back()->withInput()->with('errors', [$error]);
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $customers = new CustomerModel();
            $customerId = $customers->insert($payload + [
                'uuid' => $this->uuidV4(),
                'code' => $customers->nextCode(),
                'status' => 1,
            ], true);

            if ($customerId === false) {
                return $this->rollbackWithErrors($db, $customers->errors());
            }

            $customerId = (int) $customerId;
            $this->storeOptionalContact($customerId);
            $this->storeOptionalAddress($customerId, $payload);
            (new ActivityService())->record('customer', $customerId, 'customer.created', 'Cliente creado', 'Se registró el cliente en ERP TraceOPX.');
            $db->transCommit();

            return redirect()->to(route_to('customers.show', $customerId))->with('success', 'Cliente creado correctamente.');
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

        $db = db_connect();
        return view('customers/show', [
            'title' => $customer['business_name'],
            'customer' => $customer,
            'taxpayerType' => $this->catalogRow($db, 'mh_taxpayer_types', $customer['mh_taxpayer_type_id'] ?? null),
            'economicActivity' => $this->catalogRow($db, 'mh_economic_activities', $customer['mh_economic_activity_id'] ?? null),
            'taxCountry' => $this->catalogRow($db, 'mh_countries', $customer['tax_country_id'] ?? null),
            'taxDepartment' => $this->catalogRow($db, 'mh_departments', $customer['tax_department_id'] ?? null),
            'taxMunicipality' => $this->catalogRow($db, 'mh_municipalities', $customer['tax_municipality_id'] ?? null),
            'taxDistrict' => $this->catalogRow($db, 'mh_districts', $customer['tax_district_id'] ?? null),
            'contacts' => (new CustomerContactModel())->forCustomer($id),
            'addresses' => (new CustomerAddressModel())->forCustomer($id),
            'activities' => (new ActivityEventModel())->forEntity('customer', $id),
            'commercialSummary' => ['quotations' => 0, 'activeOrders' => 0, 'invoiced' => 0.00, 'receivable' => 0.00],
        ]);
    }

    public function edit(int $id): string
    {
        $customer = (new CustomerModel())->find($id);
        if ($customer === null) {
            throw new RuntimeException('Cliente no encontrado.');
        }
        return view('customers/form', $this->formData('Editar cliente', $customer));
    }

    public function update(int $id): RedirectResponse
    {
        $model = new CustomerModel();
        if ($model->find($id) === null) {
            return redirect()->to(route_to('customers.index'))->with('error', 'Cliente no encontrado.');
        }

        $payload = $this->customerPayload();
        if (($error = $this->geographyError($payload)) !== null) {
            return redirect()->back()->withInput()->with('errors', [$error]);
        }

        if (! $model->update($id, $payload + ['status' => (int) $this->request->getPost('status')])) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        (new ActivityService())->record('customer', $id, 'customer.updated', 'Cliente actualizado', 'Se actualizaron los datos generales, fiscales y comerciales del cliente.');
        return redirect()->to(route_to('customers.show', $id))->with('success', 'Cliente actualizado correctamente.');
    }

    private function formData(string $title, ?array $customer): array
    {
        $db = db_connect();
        return [
            'title' => $title,
            'customer' => $customer,
            'taxpayerTypes' => $this->catalogOptions($db, 'mh_taxpayer_types'),
            'economicActivities' => $this->catalogOptions($db, 'mh_economic_activities'),
            'countries' => $this->catalogOptions($db, 'mh_countries'),
            'departments' => $this->catalogOptions($db, 'mh_departments'),
            'municipalities' => $db->table('mh_municipalities')->where('status', 1)->orderBy('department_code')->orderBy('name')->get()->getResultArray(),
            'districts' => $db->table('mh_districts')->where('status', 1)->orderBy('department_code')->orderBy('name')->get()->getResultArray(),
        ];
    }

    private function customerPayload(): array
    {
        $countryId = $this->nullableInt('tax_country_id');
        $isElSalvador = $countryId !== null && $this->countryCode($countryId) === 'SV';

        return [
            'customer_type' => (string) $this->request->getPost('customer_type'),
            'mh_taxpayer_type_id' => $this->nullableInt('mh_taxpayer_type_id'),
            'mh_economic_activity_id' => $this->nullableInt('mh_economic_activity_id'),
            'tax_country_id' => $countryId,
            'tax_department_id' => $isElSalvador ? $this->nullableInt('tax_department_id') : null,
            'tax_municipality_id' => $isElSalvador ? $this->nullableInt('tax_municipality_id') : null,
            'tax_district_id' => $isElSalvador ? $this->nullableInt('tax_district_id') : null,
            'foreign_state' => $isElSalvador ? null : $this->nullable('foreign_state'),
            'foreign_city' => $isElSalvador ? null : $this->nullable('foreign_city'),
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
        ];
    }

    private function geographyError(array $payload): ?string
    {
        if (empty($payload['tax_country_id'])) {
            return 'Debes seleccionar el país fiscal del cliente.';
        }

        $db = db_connect();
        $country = $db->table('mh_countries')->where(['id' => $payload['tax_country_id'], 'status' => 1])->get()->getRowArray();
        if ($country === null) {
            return 'El país fiscal seleccionado no es válido.';
        }
        if ($country['code'] !== 'SV') {
            return null;
        }
        if (empty($payload['tax_department_id']) || empty($payload['tax_municipality_id']) || empty($payload['tax_district_id'])) {
            return 'Para clientes de El Salvador debes seleccionar departamento, municipio y distrito.';
        }

        $department = $db->table('mh_departments')->where(['id' => $payload['tax_department_id'], 'status' => 1])->get()->getRowArray();
        $municipality = $db->table('mh_municipalities')->where(['id' => $payload['tax_municipality_id'], 'status' => 1])->get()->getRowArray();
        $district = $db->table('mh_districts')->where(['id' => $payload['tax_district_id'], 'status' => 1])->get()->getRowArray();
        if ($department === null || $municipality === null || $district === null) {
            return 'La división territorial fiscal seleccionada no es válida.';
        }
        if ($municipality['department_code'] !== $department['code'] || $district['department_code'] !== $department['code']) {
            return 'El municipio y el distrito fiscal deben pertenecer al departamento seleccionado.';
        }
        return null;
    }

    private function storeOptionalContact(int $customerId): void
    {
        $name = trim((string) $this->request->getPost('contact_name'));
        if ($name === '') return;

        $model = new CustomerContactModel();
        if ($model->insert([
            'customer_id' => $customerId,
            'name' => $name,
            'position' => $this->nullable('contact_position'),
            'contact_role' => 'commercial',
            'email' => $this->nullable('contact_email'),
            'phone' => $this->nullable('contact_phone'),
            'is_primary' => 1,
            'status' => 1,
        ]) === false) {
            throw new RuntimeException(implode(' ', $model->errors()));
        }
        (new ActivityService())->record('customer', $customerId, 'customer.contact_added', 'Contacto principal agregado', "Se agregó a {$name} como contacto comercial principal.");
    }

    private function storeOptionalAddress(int $customerId, array $customerPayload): void
    {
        $address = trim((string) $this->request->getPost('address_line'));
        if ($address === '') return;

        $model = new CustomerAddressModel();
        if ($model->insert([
            'customer_id' => $customerId,
            'label' => 'Dirección fiscal principal',
            'address_type' => 'fiscal',
            'address_line' => $address,
            'country_id' => $customerPayload['tax_country_id'],
            'department_id' => $customerPayload['tax_department_id'],
            'municipality_id' => $customerPayload['tax_municipality_id'],
            'district_id' => $customerPayload['tax_district_id'],
            'foreign_state' => $customerPayload['foreign_state'],
            'foreign_city' => $customerPayload['foreign_city'],
            'is_primary' => 1,
            'status' => 1,
        ]) === false) {
            throw new RuntimeException(implode(' ', $model->errors()));
        }
        (new ActivityService())->record('customer', $customerId, 'customer.address_added', 'Dirección fiscal agregada', 'Se registró la dirección fiscal principal.');
    }

    private function catalogOptions($db, string $table): array
    {
        return $db->table($table)->where('status', 1)->orderBy('code')->get()->getResultArray();
    }

    private function catalogRow($db, string $table, ?int $id): ?array
    {
        return $id ? $db->table($table)->where('id', $id)->get()->getRowArray() : null;
    }

    private function countryCode(int $id): ?string
    {
        $row = db_connect()->table('mh_countries')->select('code')->where(['id' => $id, 'status' => 1])->get()->getRowArray();
        return $row['code'] ?? null;
    }

    private function nullable(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : $value;
    }

    private function nullableInt(string $field): ?int
    {
        $value = (int) $this->request->getPost($field);
        return $value > 0 ? $value : null;
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
