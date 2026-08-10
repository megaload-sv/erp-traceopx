<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

class BillingPaymentEvidenceService
{
    private const MAX_BYTES = 10485760; // 10 MB

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function store(int $billingCaseId, int $paymentId, UploadedFile $file): int
    {
        $db = db_connect();
        $payment = $db->table('billing_payments')
            ->where('id', $paymentId)
            ->where('billing_case_id', $billingCaseId)
            ->where('status', 'confirmed')
            ->get()->getRowArray();
        if ($payment === null) {
            throw new RuntimeException('El pago asociado al comprobante no existe.');
        }
        if (! $file->isValid() || $file->hasMoved()) {
            throw new RuntimeException('El comprobante recibido no es válido.');
        }
        if ($file->getSize() <= 0 || $file->getSize() > self::MAX_BYTES) {
            throw new RuntimeException('El comprobante debe pesar como máximo 10 MB.');
        }

        $mime = (string) $file->getMimeType();
        if (! isset(self::ALLOWED_MIME[$mime])) {
            throw new RuntimeException('Formato de comprobante no permitido. Use JPG, PNG, WEBP o PDF.');
        }

        $extension = self::ALLOWED_MIME[$mime];
        $storedName = bin2hex(random_bytes(20)) . '.' . $extension;
        $relativePath = 'billing_payment_evidence/' . $paymentId;
        $absolutePath = WRITEPATH . 'uploads/' . $relativePath;
        if (! is_dir($absolutePath) && ! mkdir($absolutePath, 0775, true) && ! is_dir($absolutePath)) {
            throw new RuntimeException('No fue posible preparar el almacenamiento del comprobante.');
        }

        $file->move($absolutePath, $storedName);
        $fullPath = $absolutePath . DIRECTORY_SEPARATOR . $storedName;
        if (! is_file($fullPath)) {
            throw new RuntimeException('No fue posible almacenar el comprobante.');
        }

        $now = date('Y-m-d H:i:s');
        $id = $db->table('billing_payment_evidence')->insert([
            'billing_payment_id' => $paymentId,
            'billing_case_id' => $billingCaseId,
            'service_case_id' => (int) $payment['service_case_id'],
            'evidence_type' => 'payment_receipt',
            'original_name' => mb_substr(basename((string) $file->getClientName()), 0, 255),
            'stored_name' => $storedName,
            'relative_path' => $relativePath,
            'mime_type' => $mime,
            'extension' => $extension,
            'size_bytes' => filesize($fullPath) ?: 0,
            'sha256' => hash_file('sha256', $fullPath),
            'uploaded_by_user_id' => session('auth_user_id') ?: null,
            'entry_user' => $this->actor(),
            'entry_date' => $now,
        ], true);

        if ($id === false) {
            @unlink($fullPath);
            throw new RuntimeException('No fue posible registrar el comprobante del pago.');
        }

        return (int) $id;
    }

    public function findForDownload(int $billingCaseId, int $evidenceId): array
    {
        $row = db_connect()->table('billing_payment_evidence')
            ->where('id', $evidenceId)
            ->where('billing_case_id', $billingCaseId)
            ->where('delete_date', null)
            ->get()->getRowArray();
        if ($row === null) {
            throw new RuntimeException('Comprobante no encontrado.');
        }
        $path = WRITEPATH . 'uploads/' . $row['relative_path'] . DIRECTORY_SEPARATOR . $row['stored_name'];
        if (! is_file($path)) {
            throw new RuntimeException('El archivo físico del comprobante no está disponible.');
        }
        $row['absolute_path'] = $path;
        return $row;
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
