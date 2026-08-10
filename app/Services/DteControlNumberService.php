<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class DteControlNumberService
{
    public function allocate(
        int $documentTypeId,
        int $establishmentId,
        int $pointOfSaleId,
        ?int $year = null
    ): array {
        $db = db_connect();
        $year ??= (int) date('Y');

        $type = $db->table('dte_document_types')
            ->select('id, mh_code, code, name')
            ->where('id', $documentTypeId)
            ->where('status', 1)
            ->get()->getRowArray();

        $establishment = $db->table('dte_establishments')
            ->select('id, mh_code, code, name')
            ->where('id', $establishmentId)
            ->where('status', 1)
            ->get()->getRowArray();

        $pointOfSale = $db->table('dte_points_of_sale')
            ->select('id, establishment_id, mh_code, code, name')
            ->where('id', $pointOfSaleId)
            ->where('establishment_id', $establishmentId)
            ->where('status', 1)
            ->get()->getRowArray();

        if ($type === null) {
            throw new RuntimeException('El tipo de DTE no está configurado o se encuentra inactivo.');
        }
        if ($establishment === null) {
            throw new RuntimeException('La sucursal DTE no está configurada o se encuentra inactiva.');
        }
        if ($pointOfSale === null) {
            throw new RuntimeException('El punto de venta no pertenece a la sucursal seleccionada o se encuentra inactivo.');
        }

        $mhDocumentCode = strtoupper(trim((string) $type['mh_code']));
        $establishmentCode = strtoupper(trim((string) $establishment['mh_code']));
        $pointOfSaleCode = strtoupper(trim((string) $pointOfSale['mh_code']));

        if (! preg_match('/^[A-Z0-9]{2}$/', $mhDocumentCode)) {
            throw new RuntimeException('El código MH del tipo DTE debe tener exactamente 2 caracteres alfanuméricos.');
        }
        if (! preg_match('/^[A-Z0-9]{4}$/', $establishmentCode)) {
            throw new RuntimeException('El código MH de sucursal debe tener exactamente 4 caracteres alfanuméricos.');
        }
        if (! preg_match('/^[A-Z0-9]{4}$/', $pointOfSaleCode)) {
            throw new RuntimeException('El código MH del punto de venta debe tener exactamente 4 caracteres alfanuméricos.');
        }
        if ($year < 2000 || $year > 9999) {
            throw new RuntimeException('El año del correlativo no es válido.');
        }

        $now = date('Y-m-d H:i:s');
        $actor = $this->actor();

        $db->transBegin();
        try {
            // Garantiza que exista exactamente una secuencia por alcance. ON DUPLICATE KEY
            // evita condiciones de carrera cuando dos procesos crean el año al mismo tiempo.
            $db->query(
                'INSERT INTO dte_control_sequences '
                . '(document_type_id, establishment_id, point_of_sale_id, year, current_value, entry_user, entry_date) '
                . 'VALUES (?, ?, ?, ?, 0, ?, ?) '
                . 'ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)',
                [$documentTypeId, $establishmentId, $pointOfSaleId, $year, $actor, $now]
            );

            $sequence = $db->query(
                'SELECT id, current_value FROM dte_control_sequences '
                . 'WHERE document_type_id = ? AND establishment_id = ? AND point_of_sale_id = ? AND year = ? '
                . 'FOR UPDATE',
                [$documentTypeId, $establishmentId, $pointOfSaleId, $year]
            )->getRowArray();

            if ($sequence === null) {
                throw new RuntimeException('No fue posible bloquear la secuencia del número de control.');
            }

            $next = (int) $sequence['current_value'] + 1;
            if ($next > 999999999999999) {
                throw new RuntimeException('La secuencia anual alcanzó el máximo permitido de 15 dígitos.');
            }

            $db->table('dte_control_sequences')->where('id', (int) $sequence['id'])->update([
                'current_value' => $next,
                'last_issued_at' => $now,
                'modify_user' => $actor,
                'modify_date' => $now,
            ]);

            $correlative = str_pad((string) $next, 15, '0', STR_PAD_LEFT);
            $controlNumber = 'DTE-'
                . $mhDocumentCode . '-'
                . $establishmentCode
                . $pointOfSaleCode . '-'
                . $correlative;

            if (strlen($controlNumber) !== 31) {
                throw new RuntimeException('El número de control generado no cumple la longitud requerida de 31 caracteres.');
            }

            $db->transCommit();

            return [
                'control_number' => $controlNumber,
                'correlative' => $next,
                'correlative_formatted' => $correlative,
                'year' => $year,
                'document_type_id' => $documentTypeId,
                'document_type_mh_code' => $mhDocumentCode,
                'establishment_id' => $establishmentId,
                'establishment_mh_code' => $establishmentCode,
                'point_of_sale_id' => $pointOfSaleId,
                'point_of_sale_mh_code' => $pointOfSaleCode,
            ];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
