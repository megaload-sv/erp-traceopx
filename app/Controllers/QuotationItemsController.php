<?php

namespace App\Controllers;

use App\Models\CommercialItemModel;
use App\Models\QuotationItemModel;
use App\Models\QuotationModel;
use App\Services\ActivityService;
use App\Services\QuotationService;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class QuotationItemsController extends BaseController
{
    public function store(int $quotationId): RedirectResponse
    {
        $quotation = (new QuotationModel())->find($quotationId);

        if ($quotation === null) {
            return redirect()->to(route_to('quotations.index'))->with('error', 'Cotización no encontrada.');
        }

        if ($quotation['status'] !== 'draft') {
            return redirect()->to(route_to('quotations.show', $quotationId))
                ->with('error', 'Solo se pueden modificar conceptos mientras la cotización está en borrador.');
        }

        $sourceType = (string) ($this->request->getPost('source_type') ?: 'manual');
        $catalogItemId = $this->nullableInt('commercial_item_id');
        $catalogItem = null;

        if ($sourceType === 'catalog') {
            $catalogItem = $catalogItemId
                ? (new CommercialItemModel())->where('status', 1)->find($catalogItemId)
                : null;

            if ($catalogItem === null) {
                return redirect()->back()->withInput()->with('error', 'Seleccione un servicio válido del catálogo.');
            }
        } elseif ($sourceType !== 'manual') {
            return redirect()->back()->withInput()->with('error', 'El origen del concepto no es válido.');
        }

        $description = trim((string) $this->request->getPost('description'));
        $longDescription = trim((string) $this->request->getPost('long_description'));
        $unitId = $this->nullableInt('unit_id');
        $quantity = (float) $this->request->getPost('quantity');
        $unitPrice = (float) $this->request->getPost('unit_price');

        if ($catalogItem !== null) {
            $description = $description !== '' ? $description : (string) $catalogItem['name'];
            $longDescription = $longDescription !== ''
                ? $longDescription
                : (string) ($catalogItem['long_description'] ?? '');
            $unitId ??= ! empty($catalogItem['default_unit_id'])
                ? (int) $catalogItem['default_unit_id']
                : null;

            if ($unitPrice <= 0) {
                $unitPrice = (float) $catalogItem['suggested_price'];
            }
        }

        if ($description === '') {
            return redirect()->back()->withInput()->with('error', 'Ingrese la descripción del concepto.');
        }

        if ($quantity <= 0) {
            return redirect()->back()->withInput()->with('error', 'La cantidad debe ser mayor que cero.');
        }

        if ($unitPrice < 0) {
            return redirect()->back()->withInput()->with('error', 'El precio no puede ser negativo.');
        }

        $itemModel = new QuotationItemModel();
        $existingItem = null;

        if ($sourceType === 'catalog' && $catalogItemId !== null) {
            $existingItem = $itemModel
                ->where('quotation_id', $quotationId)
                ->where('commercial_item_id', $catalogItemId)
                ->first();
        }

        if ($existingItem !== null && (string) $this->request->getPost('merge_duplicate') !== '1') {
            return redirect()->back()->withInput()->with(
                'error',
                'Este servicio ya está incluido. Confirme la acción para incrementar su cantidad.'
            );
        }

        $db = db_connect();
        $db->transBegin();

        try {
            if ($existingItem !== null) {
                $lockedItem = $db->query(
                    'SELECT * FROM quotation_items WHERE id = ? FOR UPDATE',
                    [(int) $existingItem['id']]
                )->getRowArray();

                if ($lockedItem === null) {
                    throw new \RuntimeException('No fue posible localizar el concepto existente.');
                }

                $newQuantity = (float) $lockedItem['quantity'] + $quantity;
                $newLineTotal = round($newQuantity * (float) $lockedItem['unit_price'], 2);

                $itemModel->update((int) $lockedItem['id'], [
                    'quantity' => $newQuantity,
                    'line_total' => $newLineTotal,
                    'modify_user' => (string) (session('auth_user_email') ?: 'system'),
                ]);

                (new ActivityService())->record(
                    'quotation',
                    $quotationId,
                    'quotation.item_quantity_incremented',
                    'Cantidad de servicio actualizada',
                    $description . ': +' . number_format($quantity, 3) . ' unidades.'
                );

                $successMessage = 'El servicio ya existía; su cantidad fue incrementada correctamente.';
            } else {
                $sortOrder = ((int) ($itemModel
                    ->where('quotation_id', $quotationId)
                    ->selectMax('sort_order')
                    ->first()['sort_order'] ?? 0)) + 1;

                $itemId = $itemModel->insert([
                    'quotation_id' => $quotationId,
                    'commercial_item_id' => $catalogItemId,
                    'source_type' => $sourceType,
                    'description' => $description,
                    'long_description' => $longDescription ?: null,
                    'unit_id' => $unitId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => round($quantity * $unitPrice, 2),
                    'sort_order' => $sortOrder,
                ], true);

                if ($itemId === false) {
                    throw new \RuntimeException('No fue posible agregar el concepto.');
                }

                (new ActivityService())->record(
                    'quotation',
                    $quotationId,
                    'quotation.item_added',
                    'Concepto agregado',
                    $description . ' · ' . ucfirst($sourceType)
                );

                $successMessage = 'Concepto agregado a la cotización.';
            }

            (new QuotationService())->recalculateTotals($quotationId);
            $db->transCommit();

            return redirect()->to(route_to('quotations.show', $quotationId))->with('success', $successMessage);
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error guardando concepto de cotización: {message}', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with(
                'error',
                'No fue posible actualizar los conceptos de la cotización.'
            );
        }
    }

    public function delete(int $quotationId, int $itemId): RedirectResponse
    {
        $quotation = (new QuotationModel())->find($quotationId);
        $itemModel = new QuotationItemModel();
        $item = $itemModel->where('quotation_id', $quotationId)->find($itemId);

        if ($quotation === null || $item === null) {
            return redirect()->to(route_to('quotations.index'))->with('error', 'Concepto no encontrado.');
        }

        if ($quotation['status'] !== 'draft') {
            return redirect()->to(route_to('quotations.show', $quotationId))
                ->with('error', 'Solo se pueden eliminar conceptos en borrador.');
        }

        $itemModel->delete($itemId);
        (new QuotationService())->recalculateTotals($quotationId);
        (new ActivityService())->record(
            'quotation',
            $quotationId,
            'quotation.item_deleted',
            'Concepto eliminado',
            (string) $item['description']
        );

        return redirect()->to(route_to('quotations.show', $quotationId))->with('success', 'Concepto eliminado.');
    }

    private function nullableInt(string $field): ?int
    {
        $value = trim((string) $this->request->getPost($field));

        return $value === '' ? null : (int) $value;
    }
}
