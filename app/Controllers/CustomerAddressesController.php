<?php

namespace App\Controllers;

use App\Models\CustomerAddressModel;
use App\Models\CustomerModel;
use App\Services\ActivityService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;

class CustomerAddressesController extends BaseController
{
    public function create(int $customerId): string
    {
        return view('customers/addresses/form', $this->formData($this->customer($customerId), null, 'Agregar dirección'));
    }

    public function store(int $customerId): RedirectResponse
    {
        $this->customer($customerId);
        $model = new CustomerAddressModel();
        $payload = $this->payload($customerId, $model->customerHasAddresses($customerId) ? 0 : 1);

        if (($error = $this->geographyError($payload)) !== null) {
            return redirect()->back()->withInput()->with('errors', [$error]);
        }

        $id = $model->insert($payload, true);
        if ($id === false) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        $label = $payload['label'] ?: 'Dirección';
        (new ActivityService())->record('customer', $customerId, 'customer.address_added', 'Dirección agregada', "Se agregó {$label} al perfil del cliente.");

        return redirect()->to(route_to('customers.show', $customerId))->with('success', 'Dirección agregada correctamente.');
    }

    public function edit(int $customerId, int $addressId): string
    {
        return view('customers/addresses/form', $this->formData(
            $this->customer($customerId),
            $this->address($customerId, $addressId),
            'Editar dirección'
        ));
    }

    public function update(int $customerId, int $addressId): RedirectResponse
    {
        $address = $this->address($customerId, $addressId);
        $model = new CustomerAddressModel();
        $payload = $this->payload($customerId, (int) $address['is_primary']);

        if ((int) $address['is_primary'] === 1 && (int) $payload['status'] === 0) {
            return redirect()->back()->withInput()->with('errors', ['Selecciona otra ubicación principal antes de desactivar esta.']);
        }

        if (($error = $this->geographyError($payload)) !== null) {
            return redirect()->back()->withInput()->with('errors', [$error]);
        }

        if (! $model->update($addressId, $payload)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        (new ActivityService())->record('customer', $customerId, 'customer.address_updated', 'Dirección actualizada', 'Se actualizaron los datos de una ubicación del cliente.');

        return redirect()->to(route_to('customers.show', $customerId))->with('success', 'Dirección actualizada correctamente.');
    }

    public function makePrimary(int $customerId, int $addressId): RedirectResponse
    {
        $address = $this->address($customerId, $addressId);
        $model = new CustomerAddressModel();

        if (! $model->makePrimary($customerId, $addressId)) {
            return redirect()->back()->with('error', 'Solo una ubicación activa puede seleccionarse como principal.');
        }

        $label = $address['label'] ?: 'la dirección seleccionada';
        (new ActivityService())->record('customer', $customerId, 'customer.primary_address_changed', 'Dirección principal actualizada', "Se seleccionó {$label} como dirección principal.");

        return redirect()->to(route_to('customers.show', $customerId))->with('success', 'Dirección principal actualizada.');
    }

    private function formData(array $customer, ?array $address, string $title): array
    {
        $db = db_connect();

        return [
            'title' => $title,
            'customer' => $customer,
            'address' => $address,
            'countries' => $db->table('mh_countries')->where('status', 1)->orderBy('name')->get()->getResultArray(),
            'departments' => $db->table('mh_departments')->where('status', 1)->orderBy('code')->get()->getResultArray(),
            'municipalities' => $db->table('mh_municipalities')->where('status', 1)->orderBy('department_code')->orderBy('code')->get()->getResultArray(),
            'districts' => $db->table('mh_districts')->where('status', 1)->orderBy('department_code')->orderBy('code')->get()->getResultArray(),
        ];
    }

    private function payload(int $customerId, int $isPrimary): array
    {
        $countryId = (int) $this->request->getPost('country_id');
        $country = db_connect()->table('mh_countries')->select('code')->where(['id' => $countryId, 'status' => 1])->get()->getRowArray();
        $isElSalvador = ($country['code'] ?? '') === 'SV';

        return [
            'customer_id' => $customerId,
            'address_type' => (string) ($this->request->getPost('address_type') ?: 'operational'),
            'label' => $this->nullable('label'),
            'address_line' => trim((string) $this->request->getPost('address_line')),
            'country_id' => $countryId ?: null,
            'department_id' => $isElSalvador ? $this->nullableInt('department_id') : null,
            'municipality_id' => $isElSalvador ? $this->nullableInt('municipality_id') : null,
            'district_id' => $isElSalvador ? $this->nullableInt('district_id') : null,
            'foreign_state' => $isElSalvador ? null : $this->nullable('foreign_state'),
            'foreign_city' => $isElSalvador ? null : $this->nullable('foreign_city'),
            'is_primary' => $isPrimary,
            'status' => (int) ($this->request->getPost('status') ?? 1),
        ];
    }

    private function geographyError(array $payload): ?string
    {
        if (empty($payload['country_id'])) {
            return 'Debes seleccionar el país de la ubicación.';
        }

        $db = db_connect();
        $country = $db->table('mh_countries')->where(['id' => $payload['country_id'], 'status' => 1])->get()->getRowArray();
        if ($country === null) {
            return 'El país seleccionado no es válido.';
        }

        if ($country['code'] !== 'SV') {
            return null;
        }

        if (empty($payload['department_id']) || empty($payload['municipality_id']) || empty($payload['district_id'])) {
            return 'Para ubicaciones de El Salvador debes seleccionar departamento, municipio y distrito.';
        }

        $department = $db->table('mh_departments')->where(['id' => $payload['department_id'], 'status' => 1])->get()->getRowArray();
        $municipality = $db->table('mh_municipalities')->where(['id' => $payload['municipality_id'], 'status' => 1])->get()->getRowArray();
        $district = $db->table('mh_districts')->where(['id' => $payload['district_id'], 'status' => 1])->get()->getRowArray();

        if ($department === null || $municipality === null || $district === null) {
            return 'La división territorial seleccionada no es válida.';
        }

        if ($municipality['department_code'] !== $department['code'] || $district['department_code'] !== $department['code']) {
            return 'El municipio y el distrito deben pertenecer al departamento seleccionado.';
        }

        return null;
    }

    private function customer(int $id): array
    {
        $customer = (new CustomerModel())->find($id);
        if ($customer === null) {
            throw new RuntimeException('Cliente no encontrado.');
        }
        return $customer;
    }

    private function address(int $customerId, int $addressId): array
    {
        $address = (new CustomerAddressModel())->where('customer_id', $customerId)->find($addressId);
        if ($address === null) {
            throw new RuntimeException('Dirección no encontrada.');
        }
        return $address;
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
}
