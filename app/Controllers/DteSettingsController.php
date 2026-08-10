<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class DteSettingsController extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $editEstablishmentId = (int) $this->request->getGet('edit_establishment');
        $editPointOfSaleId = (int) $this->request->getGet('edit_point_of_sale');

        $editEstablishment = $editEstablishmentId > 0
            ? $db->table('dte_establishments')->where('id', $editEstablishmentId)->get()->getRowArray()
            : null;
        $editPointOfSale = $editPointOfSaleId > 0
            ? $db->table('dte_points_of_sale')->where('id', $editPointOfSaleId)->get()->getRowArray()
            : null;

        if ($editEstablishment !== null) {
            $editEstablishment['mh_code_locked'] = $this->establishmentMhCodeIsLocked((int) $editEstablishment['id']);
        }
        if ($editPointOfSale !== null) {
            $editPointOfSale['mh_code_locked'] = $this->pointOfSaleMhCodeIsLocked((int) $editPointOfSale['id']);
        }

        return view('dte_settings/index', [
            'title' => 'Configuración DTE',
            'establishments' => $db->table('dte_establishments')->orderBy('name')->get()->getResultArray(),
            'pointsOfSale' => $db->table('dte_points_of_sale pos')
                ->select('pos.*, est.name AS establishment_name, est.mh_code AS establishment_mh_code')
                ->join('dte_establishments est', 'est.id = pos.establishment_id')
                ->orderBy('est.name')->orderBy('pos.name')->get()->getResultArray(),
            'commercialUnits' => $db->table('commercial_units cu')
                ->select('cu.*, mh.code AS mh_code, mh.name AS mh_name')
                ->join('mh_unit_measurements mh', 'mh.id = cu.mh_unit_measure_id', 'left')
                ->where('cu.delete_date', null)
                ->orderBy('cu.display_order')->orderBy('cu.name')->get()->getResultArray(),
            'mhUnits' => $db->table('mh_unit_measurements')
                ->where('catalog_code', 'CAT-014')->where('status', 1)->orderBy('code')->get()->getResultArray(),
            'sequences' => $db->table('dte_control_sequences seq')
                ->select('seq.*, dt.code AS document_code, dt.mh_code AS document_mh_code, est.name AS establishment_name, est.mh_code AS establishment_mh_code, pos.name AS point_of_sale_name, pos.mh_code AS point_of_sale_mh_code')
                ->join('dte_document_types dt', 'dt.id = seq.document_type_id')
                ->join('dte_establishments est', 'est.id = seq.establishment_id')
                ->join('dte_points_of_sale pos', 'pos.id = seq.point_of_sale_id')
                ->orderBy('seq.year', 'DESC')->orderBy('dt.mh_code')->get()->getResultArray(),
            'editEstablishment' => $editEstablishment,
            'editPointOfSale' => $editPointOfSale,
        ]);
    }

    public function storeEstablishment(): RedirectResponse
    {
        $name = trim((string) $this->request->getPost('name'));
        $mhCode = strtoupper(trim((string) $this->request->getPost('mh_code')));
        $code = strtoupper(trim((string) $this->request->getPost('code')));

        if ($name === '' || $code === '' || ! preg_match('/^[A-Z0-9]{4}$/', $mhCode)) {
            return redirect()->back()->withInput()->with('error', 'Complete la sucursal. El código MH debe tener exactamente 4 caracteres alfanuméricos.');
        }

        $db = db_connect();
        $db->table('dte_establishments')->insert([
            'code' => $code,
            'name' => $name,
            'mh_code' => $mhCode,
            'internal_code' => trim((string) $this->request->getPost('internal_code')) ?: null,
            'is_default' => $this->request->getPost('is_default') ? 1 : 0,
            'status' => 1,
            'entry_user' => $this->actor(),
            'entry_date' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(route_to('dte_settings.index'))->with('success', 'Sucursal DTE registrada.');
    }

    public function updateEstablishment(int $id): RedirectResponse
    {
        $db = db_connect();
        $row = $db->table('dte_establishments')->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return redirect()->to(route_to('dte_settings.index'))->with('error', 'Sucursal DTE no encontrada.');
        }

        $name = trim((string) $this->request->getPost('name'));
        $code = strtoupper(trim((string) $this->request->getPost('code')));
        $requestedMhCode = strtoupper(trim((string) $this->request->getPost('mh_code')));
        $mhCodeLocked = $this->establishmentMhCodeIsLocked($id);
        $mhCode = $mhCodeLocked ? (string) $row['mh_code'] : $requestedMhCode;

        if ($name === '' || $code === '' || ! preg_match('/^[A-Z0-9]{4}$/', $mhCode)) {
            return redirect()->back()->withInput()->with('error', 'Complete la sucursal. El código MH debe tener exactamente 4 caracteres alfanuméricos.');
        }

        $db->table('dte_establishments')->where('id', $id)->update([
            'code' => $code,
            'name' => $name,
            'mh_code' => $mhCode,
            'internal_code' => trim((string) $this->request->getPost('internal_code')) ?: null,
            'is_default' => $this->request->getPost('is_default') ? 1 : 0,
            'status' => (string) $this->request->getPost('status') === '0' ? 0 : 1,
            'modify_user' => $this->actor(),
            'modify_date' => date('Y-m-d H:i:s'),
        ]);

        $message = $mhCodeLocked && $requestedMhCode !== '' && $requestedMhCode !== (string) $row['mh_code']
            ? 'Sucursal actualizada. El código MH se conservó porque ya forma parte de numeración fiscal utilizada.'
            : 'Sucursal DTE actualizada.';

        return redirect()->to(route_to('dte_settings.index') . '#establishments')->with('success', $message);
    }

    public function storePointOfSale(): RedirectResponse
    {
        $establishmentId = (int) $this->request->getPost('establishment_id');
        $name = trim((string) $this->request->getPost('name'));
        $mhCode = strtoupper(trim((string) $this->request->getPost('mh_code')));
        $code = strtoupper(trim((string) $this->request->getPost('code')));

        if ($establishmentId <= 0 || $name === '' || $code === '' || ! preg_match('/^[A-Z0-9]{4}$/', $mhCode)) {
            return redirect()->back()->withInput()->with('error', 'Complete el punto de venta. El código MH debe tener exactamente 4 caracteres alfanuméricos.');
        }

        $db = db_connect();
        if ($db->table('dte_establishments')->where('id', $establishmentId)->where('status', 1)->countAllResults() === 0) {
            return redirect()->back()->withInput()->with('error', 'La sucursal seleccionada no es válida.');
        }

        $db->table('dte_points_of_sale')->insert([
            'establishment_id' => $establishmentId,
            'code' => $code,
            'name' => $name,
            'mh_code' => $mhCode,
            'internal_code' => trim((string) $this->request->getPost('internal_code')) ?: null,
            'is_default' => $this->request->getPost('is_default') ? 1 : 0,
            'status' => 1,
            'entry_user' => $this->actor(),
            'entry_date' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(route_to('dte_settings.index'))->with('success', 'Punto de venta DTE registrado.');
    }

    public function updatePointOfSale(int $id): RedirectResponse
    {
        $db = db_connect();
        $row = $db->table('dte_points_of_sale')->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return redirect()->to(route_to('dte_settings.index'))->with('error', 'Punto de venta DTE no encontrado.');
        }

        $establishmentId = (int) $this->request->getPost('establishment_id');
        $name = trim((string) $this->request->getPost('name'));
        $code = strtoupper(trim((string) $this->request->getPost('code')));
        $requestedMhCode = strtoupper(trim((string) $this->request->getPost('mh_code')));
        $used = $this->pointOfSaleMhCodeIsLocked($id);
        $mhCode = $used ? (string) $row['mh_code'] : $requestedMhCode;

        if ($used) {
            $establishmentId = (int) $row['establishment_id'];
        }

        if ($establishmentId <= 0 || $name === '' || $code === '' || ! preg_match('/^[A-Z0-9]{4}$/', $mhCode)) {
            return redirect()->back()->withInput()->with('error', 'Complete el punto de venta. El código MH debe tener exactamente 4 caracteres alfanuméricos.');
        }
        if ($db->table('dte_establishments')->where('id', $establishmentId)->countAllResults() === 0) {
            return redirect()->back()->withInput()->with('error', 'La sucursal seleccionada no es válida.');
        }

        $db->table('dte_points_of_sale')->where('id', $id)->update([
            'establishment_id' => $establishmentId,
            'code' => $code,
            'name' => $name,
            'mh_code' => $mhCode,
            'internal_code' => trim((string) $this->request->getPost('internal_code')) ?: null,
            'is_default' => $this->request->getPost('is_default') ? 1 : 0,
            'status' => (string) $this->request->getPost('status') === '0' ? 0 : 1,
            'modify_user' => $this->actor(),
            'modify_date' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(route_to('dte_settings.index') . '#points-of-sale')->with('success', $used
            ? 'Punto de venta actualizado. Sucursal y código MH permanecen protegidos porque ya fueron utilizados fiscalmente.'
            : 'Punto de venta DTE actualizado.');
    }

    public function mapUnit(int $commercialUnitId): RedirectResponse
    {
        $mhUnitId = (int) $this->request->getPost('mh_unit_measure_id');
        $db = db_connect();

        if ($db->table('commercial_units')->where('id', $commercialUnitId)->where('delete_date', null)->countAllResults() === 0) {
            return redirect()->back()->with('error', 'Unidad comercial no encontrada.');
        }

        if ($mhUnitId > 0 && $db->table('mh_unit_measurements')->where('id', $mhUnitId)->where('catalog_code', 'CAT-014')->where('status', 1)->countAllResults() === 0) {
            return redirect()->back()->with('error', 'La unidad CAT-014 seleccionada no es válida.');
        }

        $db->table('commercial_units')->where('id', $commercialUnitId)->update([
            'mh_unit_measure_id' => $mhUnitId > 0 ? $mhUnitId : null,
            'modify_user' => $this->actor(),
            'modify_date' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(route_to('dte_settings.index') . '#units')->with('success', 'Mapeo CAT-014 actualizado.');
    }

    private function establishmentMhCodeIsLocked(int $id): bool
    {
        $db = db_connect();
        if ($db->table('dte_documents')->where('establishment_id', $id)->where('control_number IS NOT NULL', null, false)->countAllResults() > 0) {
            return true;
        }

        return $db->table('dte_control_sequences')->where('establishment_id', $id)->where('current_value >', 0)->countAllResults() > 0;
    }

    private function pointOfSaleMhCodeIsLocked(int $id): bool
    {
        $db = db_connect();
        if ($db->table('dte_documents')->where('point_of_sale_id', $id)->where('control_number IS NOT NULL', null, false)->countAllResults() > 0) {
            return true;
        }

        return $db->table('dte_control_sequences')->where('point_of_sale_id', $id)->where('current_value >', 0)->countAllResults() > 0;
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
