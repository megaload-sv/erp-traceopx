<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class DteIssuerSettingsController extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $issuer = $db->table('dte_issuer_profiles')->where('status', 1)->orderBy('id')->get()->getRowArray();
        $settings = $db->table('dte_settings')
            ->where('group_code', 'emission')
            ->where('status', 1)
            ->get()->getResultArray();

        $emission = [
            'environment' => '00',
            'default_establishment_id' => null,
            'default_point_of_sale_id' => null,
        ];
        foreach ($settings as $row) {
            $emission[(string) $row['setting_key']] = $row['setting_value'];
        }

        return view('dte_settings/issuer', [
            'title' => 'Emisor DTE',
            'issuer' => $issuer,
            'emission' => $emission,
            'establishments' => $db->table('dte_establishments')->where('status', 1)->orderBy('name')->get()->getResultArray(),
            'pointsOfSale' => $db->table('dte_points_of_sale pos')
                ->select('pos.*, est.name AS establishment_name, est.mh_code AS establishment_mh_code')
                ->join('dte_establishments est', 'est.id = pos.establishment_id')
                ->where('pos.status', 1)
                ->where('est.status', 1)
                ->orderBy('est.name')->orderBy('pos.name')->get()->getResultArray(),
        ]);
    }

    public function saveIssuer(): RedirectResponse
    {
        $data = [
            'legal_name' => trim((string) $this->request->getPost('legal_name')),
            'trade_name' => trim((string) $this->request->getPost('trade_name')) ?: null,
            'nit' => strtoupper(trim((string) $this->request->getPost('nit'))),
            'nrc' => strtoupper(trim((string) $this->request->getPost('nrc'))) ?: null,
            'activity_code' => trim((string) $this->request->getPost('activity_code')),
            'activity_description' => trim((string) $this->request->getPost('activity_description')),
            'establishment_type_code' => strtoupper(trim((string) $this->request->getPost('establishment_type_code'))) ?: null,
            'department_code' => trim((string) $this->request->getPost('department_code')),
            'municipality_code' => trim((string) $this->request->getPost('municipality_code')),
            'address_complement' => trim((string) $this->request->getPost('address_complement')),
            'phone' => trim((string) $this->request->getPost('phone')),
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'mh_establishment_code' => strtoupper(trim((string) $this->request->getPost('mh_establishment_code'))) ?: null,
            'mh_establishment_code_alt' => strtoupper(trim((string) $this->request->getPost('mh_establishment_code_alt'))) ?: null,
        ];

        foreach (['legal_name', 'nit', 'activity_code', 'activity_description', 'department_code', 'municipality_code', 'address_complement', 'phone', 'email'] as $required) {
            if ($data[$required] === '') {
                return redirect()->back()->withInput()->with('error', 'Complete todos los datos fiscales obligatorios del emisor.');
            }
        }
        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'El correo electrónico del emisor no es válido.');
        }

        $db = db_connect();
        $current = $db->table('dte_issuer_profiles')->where('status', 1)->orderBy('id')->get()->getRowArray();
        $now = date('Y-m-d H:i:s');

        if ($current === null) {
            $db->table('dte_issuer_profiles')->insert($data + [
                'status' => 1,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);
            $message = 'Configuración fiscal del emisor registrada.';
        } else {
            $db->table('dte_issuer_profiles')->where('id', (int) $current['id'])->update($data + [
                'modify_user' => $this->actor(),
                'modify_date' => $now,
            ]);
            $message = 'Configuración fiscal del emisor actualizada.';
        }

        return redirect()->to(route_to('dte_settings.issuer'))->with('success', $message);
    }

    public function saveEmission(): RedirectResponse
    {
        $environment = (string) $this->request->getPost('environment');
        $establishmentId = (int) $this->request->getPost('default_establishment_id');
        $pointOfSaleId = (int) $this->request->getPost('default_point_of_sale_id');

        if (! in_array($environment, ['00', '01'], true)) {
            return redirect()->back()->withInput()->with('error', 'El ambiente de emisión no es válido.');
        }

        $db = db_connect();
        if ($establishmentId <= 0 || $db->table('dte_establishments')->where('id', $establishmentId)->where('status', 1)->countAllResults() === 0) {
            return redirect()->back()->withInput()->with('error', 'Seleccione una sucursal DTE activa como predeterminada.');
        }

        $point = $db->table('dte_points_of_sale')
            ->where('id', $pointOfSaleId)
            ->where('establishment_id', $establishmentId)
            ->where('status', 1)
            ->get()->getRowArray();
        if ($point === null) {
            return redirect()->back()->withInput()->with('error', 'El punto de venta predeterminado debe pertenecer a la sucursal seleccionada.');
        }

        $this->saveSetting('environment', $environment);
        $this->saveSetting('default_establishment_id', (string) $establishmentId);
        $this->saveSetting('default_point_of_sale_id', (string) $pointOfSaleId);

        // Conservamos una sola marca default coherente con la configuración general.
        $db->table('dte_establishments')->update(['is_default' => 0]);
        $db->table('dte_establishments')->where('id', $establishmentId)->update(['is_default' => 1]);
        $db->table('dte_points_of_sale')->update(['is_default' => 0]);
        $db->table('dte_points_of_sale')->where('id', $pointOfSaleId)->update(['is_default' => 1]);

        return redirect()->to(route_to('dte_settings.issuer') . '#emission')->with('success', 'Parámetros predeterminados de emisión actualizados.');
    }

    private function saveSetting(string $key, string $value): void
    {
        $db = db_connect();
        $row = $db->table('dte_settings')
            ->where('group_code', 'emission')
            ->where('setting_key', $key)
            ->get()->getRowArray();
        $now = date('Y-m-d H:i:s');

        if ($row === null) {
            $db->table('dte_settings')->insert([
                'group_code' => 'emission',
                'setting_key' => $key,
                'setting_value' => $value,
                'value_type' => $key === 'environment' ? 'string' : 'integer',
                'status' => 1,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);
            return;
        }

        $db->table('dte_settings')->where('id', (int) $row['id'])->update([
            'setting_value' => $value,
            'modify_user' => $this->actor(),
            'modify_date' => $now,
        ]);
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
