<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class DteControlNumberService
{
    public function assignToDocument(
        int $dteDocumentId,
        int $establishmentId,
        int $pointOfSaleId,
        ?int $year = null
    ): array {
        $db = db_connect();
        $year ??= (int) date('Y');

        $document = $db->table('dte_documents')
            ->select('id, document_type_id, control_number, control_year, control_correlative, establishment_id, point_of_sale_id')
            ->where('id', $dteDocumentId)
            ->get()->getRowArray();

        if ($document === null) {
            throw new RuntimeException('Documento DTE no encontrado.');
        }

        if (! empty($document['control_number'])) {
            return [
                'control_number' => (string) $document['control_number'],
                'correlative' => (int) $document['control_correlative'],
                'correlative_formatted' => str_pad((string) $document['control_correlative'], 15, '0', STR_PAD_LEFT),
                'year' => (int) $document['control_year'],
                'document_type_id' => (int) $document['document_type_id'],
                'establishment_id' => (int) $document['establishment_id'],
                'point_of_sale_id' => (int) $document['point_of_sale_id'],
                'already_assigned' => true,
            ];
        }

        $context = $this->loadContext(
            (int) $document['document_type_id'],
            $establishmentId,
            $pointOfSaleId,
            $year
        );

        $now = date('Y-m-d H:i:s');
        $actor = $this->actor();

        $db->transBegin();
        try {
            $lockedDocument = $db->query(
                'SELECT id, control_number, control_year, control_correlative, establishment_id, point_of_sale_id '
                . 'FROM dte_documents WHERE id = ? FOR UPDATE',
                [$dteDocumentId]
            )->getRowArray();

            if ($lockedDocument === null) {
                throw new RuntimeException('No fue posible bloquear el documento DTE.');
            }

            if (! empty($lockedDocument['control_number'])) {
                $db->transCommit();
                return [
                    'control_number' => (string) $lockedDocument['control_number'],
                    'correlative' => (int) $lockedDocument['control_correlative'],
                    'correlative_formatted' => str_pad((string) $lockedDocument['control_correlative'], 15, '0', STR_PAD_LEFT),
                    'year' => (int) $lockedDocument['control_year'],
                    'document_type_id' => (int) $document['document_type_id'],
                    'establishment_id' => (int) $lockedDocument['establishment_id'],
                    'point_of_sale_id' => (int) $lockedDocument['point_of_sale_id'],
                    'already_assigned' => true,
                ];
            }

            $allocation = $this->nextSequenceWithinTransaction(
                (int) $document['document_type_id'],
                $establishmentId,
                $pointOfSaleId,
                $year,
                $context,
                $actor,
                $now
            );

            $db->table('dte_documents')->where('id', $dteDocumentId)->update([
                'establishment_id' => $establishmentId,
                'point_of_sale_id' => $pointOfSaleId,
                'control_number' => $allocation['control_number'],
                'control_year' => $year,
                'control_correlative' => $allocation['correlative'],
                'modify_user' => $actor,
                'modify_date' => $now,
            ]);

            $db->transCommit();
            return $allocation + ['already_assigned' => false];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Reserva genérica para procesos que aún no tienen dte_document.
     * Para emisión normal debe preferirse assignToDocument(), que guarda
     * número + correlativo + DTE dentro de la misma transacción.
     */
    public function allocate(
        int $documentTypeId,
        int $establishmentId,
        int $pointOfSaleId,
        ?int $year = null
    ): array {
        $db = db_connect();
        $year ??= (int) date('Y');
        $context = $this->loadContext($documentTypeId, $establishmentId, $pointOfSaleId, $year);
        $now = date('Y-m-d H:i:s');
        $actor = $this->actor();

        $db->transBegin();
        try {
            $allocation = $this->nextSequenceWithinTransaction(
                $documentTypeId,
                $establishmentId,
                $pointOfSaleId,
                $year,
                $context,
                $actor,
                $now
            );
            $db->transCommit();
            return $allocation;
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function loadContext(
        int $documentTypeId,
        int $establishmentId,
        int $pointOfSaleId,
        int $year
    ): array {
        $db = db_connect();

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
        if ($year < 2000 || $year > 9999) {
            throw new RuntimeException('El año del correlativo no es válido.');
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

        return [
            'mh_document_code' => $mhDocumentCode,
            'establishment_code' => $establishmentCode,
            'point_of_sale_code' => $pointOfSaleCode,
        ];
    }

    private function nextSequenceWithinTransaction(
        int $documentTypeId,
        int $establishmentId,
        int $pointOfSaleId,
        int $year,
        array $context,
        string $actor,
        string $now
    ): array {
        $db = db_connect();

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
            . $context['mh_document_code'] . '-'
            . $context['establishment_code']
            . $context['point_of_sale_code'] . '-'
            . $correlative;

        if (strlen($controlNumber) !== 31) {
            throw new RuntimeException('El número de control generado no cumple la longitud requerida de 31 caracteres.');
        }

        return [
            'control_number' => $controlNumber,
            'correlative' => $next,
            'correlative_formatted' => $correlative,
            'year' => $year,
            'document_type_id' => $documentTypeId,
            'document_type_mh_code' => $context['mh_document_code'],
            'establishment_id' => $establishmentId,
            'establishment_mh_code' => $context['establishment_code'],
            'point_of_sale_id' => $pointOfSaleId,
            'point_of_sale_mh_code' => $context['point_of_sale_code'],
        ];
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
