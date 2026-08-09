<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;
use Throwable;

class WorkOrderAcceptanceService
{
    private const RESULTS = [
        'accepted' => 'Aceptado',
        'accepted_with_observations' => 'Aceptado con observaciones',
        'rejected' => 'No aceptado',
    ];

    private const MAX_BYTES = 10485760; // 10 MB
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function results(): array
    {
        return self::RESULTS;
    }

    public function workspace(int $workOrderId): array
    {
        $db = db_connect();
        $order = $db->table('work_orders')->where('id', $workOrderId)->where('delete_date', null)->get()->getRowArray();
        if ($order === null) {
            throw new RuntimeException('Orden de Trabajo no encontrada.');
        }

        $contacts = $db->table('customer_contacts')
            ->where('customer_id', (int) $order['customer_id'])
            ->where('status', 1)
            ->where('delete_date', null)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        $records = $db->tableExists('work_order_acceptances')
            ? $db->table('work_order_acceptances')
                ->where('work_order_id', $workOrderId)
                ->orderBy('accepted_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->get()->getResultArray()
            : [];

        $successful = null;
        foreach ($records as $record) {
            if (in_array($record['result'], ['accepted', 'accepted_with_observations'], true)) {
                $successful = $record;
                break;
            }
        }

        return [
            'order' => $order,
            'contacts' => $contacts,
            'records' => $records,
            'successful' => $successful,
            'results' => self::RESULTS,
        ];
    }

    public function record(int $workOrderId, array $data, ?UploadedFile $signature = null): int
    {
        $db = db_connect();
        $order = $db->table('work_orders')->where('id', $workOrderId)->where('delete_date', null)->get()->getRowArray();
        if ($order === null) {
            throw new RuntimeException('Orden de Trabajo no encontrada.');
        }
        if (! in_array($order['status'], ['finished', 'completed'], true)) {
            throw new RuntimeException('La aceptación del cliente solo puede registrarse después de finalizar el trabajo operativo.');
        }

        $result = trim((string) ($data['result'] ?? ''));
        $contactId = $this->nullableInt($data['customer_contact_id'] ?? null);
        $receiverName = trim((string) ($data['receiver_name'] ?? ''));
        $receiverPosition = trim((string) ($data['receiver_position'] ?? ''));
        $receiverEmail = trim((string) ($data['receiver_email'] ?? ''));
        $receiverPhone = trim((string) ($data['receiver_phone'] ?? ''));
        $observations = trim((string) ($data['observations'] ?? ''));
        $acceptedAtRaw = trim((string) ($data['accepted_at'] ?? ''));

        if (! array_key_exists($result, self::RESULTS)) {
            throw new RuntimeException('Seleccione un resultado válido de aceptación.');
        }
        if ($receiverName === '') {
            throw new RuntimeException('Ingrese el nombre de la persona que recibe el trabajo.');
        }
        if ($acceptedAtRaw === '' || strtotime($acceptedAtRaw) === false) {
            throw new RuntimeException('Ingrese una fecha y hora de aceptación válida.');
        }
        $acceptedAt = date('Y-m-d H:i:s', strtotime($acceptedAtRaw));
        if (strtotime($acceptedAt) < strtotime((string) $order['finished_at'])) {
            throw new RuntimeException('La aceptación no puede ser anterior a la finalización operativa.');
        }
        if (strtotime($acceptedAt) > time() + 300) {
            throw new RuntimeException('La fecha de aceptación no puede estar en el futuro.');
        }
        if (in_array($result, ['accepted_with_observations', 'rejected'], true) && $observations === '') {
            throw new RuntimeException('Debe registrar observaciones para este resultado de aceptación.');
        }

        if ($contactId !== null) {
            $contact = $db->table('customer_contacts')
                ->where('id', $contactId)
                ->where('customer_id', (int) $order['customer_id'])
                ->where('status', 1)
                ->where('delete_date', null)
                ->get()->getRowArray();
            if ($contact === null) {
                throw new RuntimeException('El contacto seleccionado no pertenece al cliente de esta Orden de Trabajo.');
            }
        }

        $existingSuccessful = $db->table('work_order_acceptances')
            ->where('work_order_id', $workOrderId)
            ->whereIn('result', ['accepted', 'accepted_with_observations'])
            ->countAllResults();
        if ($existingSuccessful > 0) {
            throw new RuntimeException('Esta Orden de Trabajo ya cuenta con una aceptación válida del cliente.');
        }

        $signatureMeta = $this->storeSignature($workOrderId, $signature, in_array($result, ['accepted', 'accepted_with_observations'], true));
        $now = date('Y-m-d H:i:s');

        $db->transBegin();
        try {
            $db->table('work_order_acceptances')->insert([
                'work_order_id' => $workOrderId,
                'service_case_id' => (int) $order['service_case_id'],
                'customer_contact_id' => $contactId,
                'result' => $result,
                'receiver_name' => $receiverName,
                'receiver_position' => $receiverPosition !== '' ? $receiverPosition : null,
                'receiver_email' => $receiverEmail !== '' ? $receiverEmail : null,
                'receiver_phone' => $receiverPhone !== '' ? $receiverPhone : null,
                'accepted_at' => $acceptedAt,
                'observations' => $observations !== '' ? $observations : null,
                'signature_original_name' => $signatureMeta['original_name'] ?? null,
                'signature_stored_name' => $signatureMeta['stored_name'] ?? null,
                'signature_relative_path' => $signatureMeta['relative_path'] ?? null,
                'signature_mime_type' => $signatureMeta['mime_type'] ?? null,
                'signature_sha256' => $signatureMeta['sha256'] ?? null,
                'recorded_by_user_id' => session('auth_user_id') ?: null,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);
            $acceptanceId = (int) $db->insertID();
            if ($acceptanceId <= 0) {
                throw new RuntimeException('No fue posible registrar la aceptación del cliente.');
            }

            $isAccepted = in_array($result, ['accepted', 'accepted_with_observations'], true);
            $title = $isAccepted ? 'Aceptación del cliente registrada' : 'Cliente no aceptó la finalización';
            $description = $receiverName . ' · ' . self::RESULTS[$result] . ($observations !== '' ? '. ' . $observations : '');

            $db->table('mission_logs')->insert([
                'work_order_id' => $workOrderId,
                'service_case_id' => (int) $order['service_case_id'],
                'log_type' => 'acceptance',
                'category' => 'customer',
                'event_code' => $isAccepted ? 'customer.acceptance_signed' : 'customer.acceptance_rejected',
                'title' => $title,
                'description' => $description,
                'visibility' => 'internal',
                'occurred_at' => $acceptedAt,
                'actor_user_id' => session('auth_user_id') ?: null,
                'metadata_json' => json_encode(['acceptance_id' => $acceptanceId, 'result' => $result], JSON_UNESCAPED_UNICODE),
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);

            $db->table('service_case_events')->insert([
                'service_case_id' => (int) $order['service_case_id'],
                'event_code' => $isAccepted ? 'customer.acceptance_signed' : 'customer.acceptance_rejected',
                'title' => $title,
                'description' => $description,
                'entity_type' => 'work_order_acceptance',
                'entity_id' => $acceptanceId,
                'occurred_at' => $acceptedAt,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);

            if ($isAccepted) {
                $db->table('work_orders')->where('id', $workOrderId)->update([
                    'status' => 'closed',
                    'closed_at' => $acceptedAt,
                    'modify_user' => $this->actor(),
                    'modify_date' => $now,
                ]);
            }

            (new ActivityService())->record('work_order', $workOrderId, $isAccepted ? 'customer.acceptance_signed' : 'customer.acceptance_rejected', $title, $description);
            $db->transCommit();

            (new ProcessEngineService())->evaluate((int) $order['service_case_id']);
            return $acceptanceId;
        } catch (Throwable $e) {
            $db->transRollback();
            if (! empty($signatureMeta['absolute_path']) && is_file($signatureMeta['absolute_path'])) {
                @unlink($signatureMeta['absolute_path']);
            }
            throw $e;
        }
    }

    public function findSignature(int $workOrderId, int $acceptanceId): array
    {
        $row = db_connect()->table('work_order_acceptances')
            ->where('id', $acceptanceId)
            ->where('work_order_id', $workOrderId)
            ->get()->getRowArray();
        if ($row === null || empty($row['signature_relative_path']) || empty($row['signature_stored_name'])) {
            throw new RuntimeException('La aceptación seleccionada no tiene firma adjunta.');
        }

        $path = WRITEPATH . 'uploads/' . $row['signature_relative_path'] . DIRECTORY_SEPARATOR . $row['signature_stored_name'];
        if (! is_file($path)) {
            throw new RuntimeException('El archivo de firma no está disponible.');
        }
        $row['absolute_path'] = $path;
        return $row;
    }

    private function storeSignature(int $workOrderId, ?UploadedFile $file, bool $required): array
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new RuntimeException('Adjunte la firma o constancia de aceptación del cliente.');
            }
            return [];
        }
        if (! $file->isValid() || $file->hasMoved()) {
            throw new RuntimeException('El archivo de firma recibido no es válido.');
        }
        if ($file->getSize() <= 0 || $file->getSize() > self::MAX_BYTES) {
            throw new RuntimeException('La firma o constancia debe pesar como máximo 10 MB.');
        }
        $mime = (string) $file->getMimeType();
        if (! isset(self::ALLOWED_MIME[$mime])) {
            throw new RuntimeException('La firma debe ser JPG, PNG, WEBP o PDF.');
        }

        $extension = self::ALLOWED_MIME[$mime];
        $storedName = bin2hex(random_bytes(20)) . '.' . $extension;
        $relativePath = 'work_order_acceptance/' . $workOrderId;
        $absoluteDir = WRITEPATH . 'uploads/' . $relativePath;
        if (! is_dir($absoluteDir) && ! mkdir($absoluteDir, 0775, true) && ! is_dir($absoluteDir)) {
            throw new RuntimeException('No fue posible preparar el almacenamiento de la firma.');
        }
        $file->move($absoluteDir, $storedName);
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $storedName;

        return [
            'original_name' => mb_substr(basename((string) $file->getClientName()), 0, 255),
            'stored_name' => $storedName,
            'relative_path' => $relativePath,
            'mime_type' => $mime,
            'sha256' => hash_file('sha256', $absolutePath),
            'absolute_path' => $absolutePath,
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = trim((string) $value);
        return $value === '' ? null : (int) $value;
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
