<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class DteFinalIssuePreparationService
{
    public function prepare(int $billingCaseId): array
    {
        $db = db_connect();
        $preIssue = (new DtePreIssueService())->build($billingCaseId);

        if (! ($preIssue['structurally_ready'] ?? false)) {
            $errors = $preIssue['preissue_validation']['errors'] ?? [];
            $message = $errors !== []
                ? (string) ($errors[0]['message'] ?? 'El DTE contiene errores de pre-emisión.')
                : 'El DTE todavía contiene observaciones bloqueantes de pre-emisión.';
            throw new RuntimeException($message);
        }

        $document = $db->table('dte_documents d')
            ->select('d.*, dt.code AS document_code, dt.mh_code')
            ->join('dte_document_types dt', 'dt.id = d.document_type_id')
            ->where('d.billing_case_id', $billingCaseId)
            ->get()->getRowArray();

        if ($document === null) {
            throw new RuntimeException('Documento DTE no encontrado para preparar emisión final.');
        }

        if (! empty($document['final_payload_json']) && ! empty($document['control_number'])) {
            return $this->resultFromStored($document, true);
        }

        if (! in_array((string) $document['status'], ['draft', 'prepared', 'ready_to_issue'], true)) {
            throw new RuntimeException('El DTE ya no se encuentra en un estado que permita preparar la emisión final.');
        }

        $establishment = $db->table('dte_establishments')
            ->where('status', 1)
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'ASC')
            ->get(1)->getRowArray();
        if ($establishment === null) {
            throw new RuntimeException('Configure una sucursal DTE activa antes de preparar la emisión final.');
        }

        $pointOfSale = $db->table('dte_points_of_sale')
            ->where('establishment_id', (int) $establishment['id'])
            ->where('status', 1)
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'ASC')
            ->get(1)->getRowArray();
        if ($pointOfSale === null) {
            throw new RuntimeException('Configure un punto de venta activo para la sucursal seleccionada.');
        }

        $db->transBegin();
        try {
            $locked = $db->query('SELECT * FROM dte_documents WHERE id = ? FOR UPDATE', [(int) $document['id']])->getRowArray();
            if ($locked === null) {
                throw new RuntimeException('No fue posible bloquear el DTE para su preparación final.');
            }

            if (! empty($locked['final_payload_json']) && ! empty($locked['control_number'])) {
                $db->transCommit();
                return $this->resultFromStored($locked + $document, true);
            }

            (new DtePaymentSnapshotService())->freezeForBillingCase($billingCaseId);

            $allocation = (new DteControlNumberService())->assignToDocument(
                (int) $document['id'],
                (int) $establishment['id'],
                (int) $pointOfSale['id'],
                (int) date('Y')
            );

            $issueDate = date('Y-m-d');
            $issueTime = date('H:i:s');
            $now = date('Y-m-d H:i:s');

            $db->table('dte_documents')->where('id', (int) $document['id'])->update([
                'issue_date' => $issueDate,
                'issue_time' => $issueTime,
                'status' => 'ready_to_sign',
                'modify_user' => $this->actor(),
                'modify_date' => $now,
            ]);

            $final = (new DtePreIssueService())->build($billingCaseId);
            $payload = $final['payload'] ?? [];
            $validation = $final['preissue_validation'] ?? ['valid' => false, 'errors' => [], 'warnings' => []];

            if (empty($payload['identificacion']['numeroControl'])) {
                throw new RuntimeException('La validación final no contiene número de control.');
            }
            if (! ($validation['valid'] ?? false)) {
                $first = $validation['errors'][0]['message'] ?? 'La validación final del DTE falló.';
                throw new RuntimeException((string) $first);
            }

            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new RuntimeException('No fue posible serializar el JSON final del DTE.');
            }

            $issuesJson = json_encode([
                'errors' => $validation['errors'] ?? [],
                'warnings' => $validation['warnings'] ?? [],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $hash = hash('sha256', $json);
            $db->table('dte_documents')->where('id', (int) $document['id'])->update([
                'final_payload_json' => $json,
                'final_payload_hash' => $hash,
                'final_validation_status' => 'valid',
                'final_validation_issues_json' => $issuesJson !== false ? $issuesJson : null,
                'final_prepared_at' => $now,
                'final_prepared_by' => $this->actor(),
                'fiscal_locked_at' => $now,
                'modify_user' => $this->actor(),
                'modify_date' => $now,
            ]);

            $billingCase = $db->table('billing_cases')->where('id', $billingCaseId)->get()->getRowArray();
            if ($billingCase !== null && ! empty($billingCase['service_case_id'])) {
                $db->table('service_case_events')->insert([
                    'service_case_id' => (int) $billingCase['service_case_id'],
                    'event_code' => 'billing.dte_ready_to_sign',
                    'title' => 'DTE preparado para firma',
                    'description' => 'Se congeló la información fiscal y se asignó el número de control ' . $allocation['control_number'] . '.',
                    'entity_type' => 'dte_document',
                    'entity_id' => (int) $document['id'],
                    'occurred_at' => $now,
                    'entry_user' => $this->actor(),
                    'entry_date' => $now,
                ]);
            }

            if (! $db->transCommit()) {
                throw new RuntimeException('No fue posible confirmar la preparación final del DTE.');
            }

            $stored = $db->table('dte_documents d')
                ->select('d.*, dt.code AS document_code, dt.mh_code')
                ->join('dte_document_types dt', 'dt.id = d.document_type_id')
                ->where('d.id', (int) $document['id'])->get()->getRowArray();

            return $this->resultFromStored($stored ?? $document, false);
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function resultFromStored(array $document, bool $alreadyPrepared): array
    {
        return [
            'document_id' => (int) $document['id'],
            'control_number' => (string) ($document['control_number'] ?? ''),
            'status' => (string) ($document['status'] ?? ''),
            'hash' => (string) ($document['final_payload_hash'] ?? ''),
            'prepared_at' => $document['final_prepared_at'] ?? null,
            'already_prepared' => $alreadyPrepared,
        ];
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
