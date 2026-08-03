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
        return view('customers/addresses/form', [
            'title' => 'Agregar dirección',
            'customer' => $this->customer($customerId),
            'address' => null,
        ]);
    }

    public function store(int $customerId): RedirectResponse
    {
        $this->customer($customerId);
        $model = new CustomerAddressModel();
        $isPrimary = $model->customerHasAddresses($customerId) ? 0 : 1;

        $id = $model->insert($this->payload($customerId, $isPrimary), true);
        if ($id === false) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        $label = trim((string) $this->request->getPost('label')) ?: 'Dirección';
        (new ActivityService())->record('customer', $customerId, 'customer.address_added', 'Dirección agregada', "Se agregó {$label} al perfil del cliente.");

        return redirect()->to(route_to('customers.show', $customerId))->with('success', 'Dirección agregada correctamente.');
    }

    public function edit(int $customerId, int $addressId): string
    {
        return view('customers/addresses/form', [
            'title' => 'Editar dirección',
            'customer' => $this->customer($customerId),
            'address' => $this->address($customerId, $addressId),
        ]);
    }

    public function update(int $customerId, int $addressId): RedirectResponse
    {
        $address = $this->address($customerId, $addressId);
        $model = new CustomerAddressModel();

        if (! $model->update($addressId, $this->payload($customerId, (int) $address['is_primary']))) {
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
            return redirect()->back()->with('error', 'No fue posible cambiar la dirección principal.');
        }

        $label = $address['label'] ?: 'la dirección seleccionada';
        (new ActivityService())->record('customer', $customerId, 'customer.primary_address_changed', 'Dirección principal actualizada', "Se seleccionó {$label} como dirección principal.");

        return redirect()->to(route_to('customers.show', $customerId))->with('success', 'Dirección principal actualizada.');
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

    private function payload(int $customerId, int $isPrimary): array
    {
        return [
            'customer_id' => $customerId,
            'address_type' => (string) ($this->request->getPost('address_type') ?: 'operational'),
            'label' => $this->nullable('label'),
            'address_line' => trim((string) $this->request->getPost('address_line')),
            'municipality' => $this->nullable('municipality'),
            'department' => $this->nullable('department'),
            'country' => $this->nullable('country') ?? 'El Salvador',
            'is_primary' => $isPrimary,
            'status' => (int) ($this->request->getPost('status') ?? 1),
        ];
    }

    private function nullable(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : $value;
    }
}
