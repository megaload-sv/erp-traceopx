<?php

namespace App\Controllers;

use App\Models\CommercialItemModel;
use CodeIgniter\HTTP\RedirectResponse;

class CommercialItemsController extends BaseController
{
    public function index(): string
    {
        $db = db_connect();

        return view('commercial_items/index', [
            'title' => 'Catálogo comercial',
            'items' => (new CommercialItemModel())
                ->select('commercial_items.*, commercial_units.name AS unit_name, commercial_units.code AS unit_code, commercial_item_groups.name AS group_name, commercial_item_groups.code AS group_code')
                ->join('commercial_units', 'commercial_units.id = commercial_items.default_unit_id', 'left')
                ->join('commercial_item_groups', 'commercial_item_groups.id = commercial_items.item_group_id', 'left')
                ->orderBy('commercial_item_groups.display_order', 'ASC')
                ->orderBy('commercial_items.display_order', 'ASC')
                ->orderBy('commercial_items.name', 'ASC')
                ->findAll(),
            'groups' => $db->table('commercial_item_groups')->where('status', 1)->orderBy('display_order')->orderBy('name')->get()->getResultArray(),
            'units' => $db->table('commercial_units')->where('status', 1)->orderBy('display_order')->orderBy('name')->get()->getResultArray(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $name = trim((string) $this->request->getPost('name'));
        $code = strtoupper(trim((string) $this->request->getPost('code')));
        $price = (float) $this->request->getPost('suggested_price');

        if ($name === '' || $code === '') {
            return redirect()->back()->withInput()->with('error', 'Código y nombre son obligatorios.');
        }

        if ($price < 0) {
            return redirect()->back()->withInput()->with('error', 'El precio sugerido no puede ser negativo.');
        }

        $model = new CommercialItemModel();
        if ($model->where('code', $code)->first() !== null) {
            return redirect()->back()->withInput()->with('error', 'Ya existe un concepto con ese código.');
        }

        $id = $model->insert([
            'uuid' => $this->uuidV4(),
            'code' => $code,
            'item_type' => (string) ($this->request->getPost('item_type') ?: 'service'),
            'item_group_id' => $this->nullableInt('item_group_id'),
            'name' => $name,
            'long_description' => trim((string) $this->request->getPost('long_description')) ?: null,
            'default_unit_id' => $this->nullableInt('default_unit_id'),
            'suggested_price' => $price,
            'allows_price_override' => 1,
            'allows_unit_override' => $this->request->getPost('allows_unit_override') ? 1 : 0,
            'display_order' => 0,
            'status' => 1,
        ], true);

        if ($id === false) {
            return redirect()->back()->withInput()->with('error', 'No fue posible guardar el concepto comercial.');
        }

        return redirect()->to(route_to('commercial_items.index'))->with('success', 'Concepto agregado al catálogo comercial.');
    }

    private function nullableInt(string $field): ?int
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : (int) $value;
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
