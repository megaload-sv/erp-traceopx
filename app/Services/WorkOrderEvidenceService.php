<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

class WorkOrderEvidenceService
{
    private const MAX_BYTES = 15728640; // 15 MB

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    ];

    public function store(int $workOrderId, UploadedFile $file, string $stage, string $description = '', ?int $missionLogId = null): int
    {
        $db = db_connect();
        $order = $db->table('work_orders')->where('id', $workOrderId)->where('delete_date', null)->get()->getRowArray();
        if ($order === null) {
            throw new RuntimeException('Orden de Trabajo no encontrada.');
        }
        if (! in_array($order['status'], ['in_progress', 'working'], true)) {
            throw new RuntimeException('Las evidencias operativas solo pueden cargarse mientras la OT está en ejecución.');
        }
        if (! in_array($stage, ['before', 'during', 'after'], true)) {
            throw new RuntimeException('El momento de la evidencia no es válido.');
        }
        if (! $file->isValid() || $file->hasMoved()) {
            throw new RuntimeException('El archivo recibido no es válido.');
        }
        if ($file->getSize() <= 0 || $file->getSize() > self::MAX_BYTES) {
            throw new RuntimeException('La evidencia debe pesar como máximo 15 MB.');
        }

        $mime = (string) $file->getMimeType();
        if (! isset(self::ALLOWED_MIME[$mime])) {
            throw new RuntimeException('Tipo de archivo no permitido. Use JPG, PNG, WEBP, PDF, DOCX o XLSX.');
        }

        if ($missionLogId !== null) {
            $log = $db->table('mission_logs')->where('id', $missionLogId)->where('work_order_id', $workOrderId)->get()->getRowArray();
            if ($log === null) {
                throw new RuntimeException('La entrada de Mission Log seleccionada no pertenece a esta OT.');
            }
        }

        $extension = self::ALLOWED_MIME[$mime];
        $storedName = bin2hex(random_bytes(20)) . '.' . $extension;
        $relativePath = 'work_order_evidence/' . $workOrderId;
        $absolutePath = WRITEPATH . 'uploads/' . $relativePath;
        if (! is_dir($absolutePath) && ! mkdir($absolutePath, 0775, true) && ! is_dir($absolutePath)) {
            throw new RuntimeException('No fue posible preparar el almacenamiento de evidencias.');
        }

        $file->move($absolutePath, $storedName);
        $fullPath = $absolutePath . DIRECTORY_SEPARATOR . $storedName;
        if (! is_file($fullPath)) {
            throw new RuntimeException('No fue posible almacenar la evidencia.');
        }

        $originalName = mb_substr(basename((string) $file->getClientName()), 0, 255);
        $isImage = str_starts_with($mime, 'image/');
        $now = date('Y-m-d H:i:s');

        $id = $db->table('work_order_evidence')->insert([
            'work_order_id' => $workOrderId,
            'service_case_id' => (int) $order['service_case_id'],
            'mission_log_id' => $missionLogId,
            'evidence_type' => $isImage ? 'photo' : 'document',
            'stage' => $stage,
            'title' => $originalName,
            'description' => trim($description) !== '' ? trim($description) : null,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'relative_path' => $relativePath,
            'mime_type' => $mime,
            'extension' => $extension,
            'size_bytes' => filesize($fullPath) ?: 0,
            'sha256' => hash_file('sha256', $fullPath),
            'visibility' => 'internal',
            'occurred_at' => $now,
            'uploaded_by_user_id' => session('auth_user_id') ?: null,
            'entry_user' => $this->actor(),
            'entry_date' => $now,
        ], true);

        if ($id === false) {
            @unlink($fullPath);
            throw new RuntimeException('No fue posible registrar la evidencia.');
        }

        $db->table('mission_logs')->insert([
            'work_order_id' => $workOrderId,
            'service_case_id' => (int) $order['service_case_id'],
            'log_type' => 'system',
            'category' => 'evidence',
            'event_code' => 'evidence.uploaded',
            'title' => 'Evidencia agregada',
            'description' => $originalName . ($description !== '' ? ' · ' . trim($description) : ''),
            'visibility' => 'internal',
            'occurred_at' => $now,
            'actor_user_id' => session('auth_user_id') ?: null,
            'metadata_json' => json_encode(['evidence_id' => (int) $id, 'stage' => $stage], JSON_UNESCAPED_UNICODE),
            'entry_user' => $this->actor(),
            'entry_date' => $now,
        ]);

        (new ActivityService())->record('work_order', $workOrderId, 'work_order.evidence_uploaded', 'Evidencia agregada', $originalName);

        return (int) $id;
    }

    public function findForDownload(int $workOrderId, int $evidenceId): array
    {
        $row = db_connect()->table('work_order_evidence')
            ->where('id', $evidenceId)
            ->where('work_order_id', $workOrderId)
            ->where('delete_date', null)
            ->get()->getRowArray();
        if ($row === null) {
            throw new RuntimeException('Evidencia no encontrada.');
        }

        $path = WRITEPATH . 'uploads/' . $row['relative_path'] . DIRECTORY_SEPARATOR . $row['stored_name'];
        if (! is_file($path)) {
            throw new RuntimeException('El archivo físico de la evidencia no está disponible.');
        }
        $row['absolute_path'] = $path;
        return $row;
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: 'system');
    }
}
