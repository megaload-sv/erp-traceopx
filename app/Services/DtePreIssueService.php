<?php

namespace App\Services;

use RuntimeException;

class DtePreIssueService
{
    public function build(int $billingCaseId): array
    {
        $db = db_connect();
        $base = (new DteJsonBuilderService())->buildForBillingCase($billingCaseId);
        $document = $db->table('dte_documents d')
            ->select('d.*, dt.code AS document_code, dt.schema_file')
            ->join('dte_document_types dt', 'dt.id = d.document_type_id')
            ->where('d.billing_case_id', $billingCaseId)
            ->get()->getRowArray();
        if ($document === null) {
            throw new RuntimeException('Documento DTE no encontrado para pre-emisión.');
        }

        $paymentSnapshot = (new DtePaymentSnapshotService())->previewForBillingCase($billingCaseId);
        $payload = $base['payload'];
        if (isset($payload['resumen']) && is_array($payload['resumen'])) {
            $payload['resumen']['condicionOperacion'] = (int) ($paymentSnapshot['condition_code'] ?? $payload['resumen']['condicionOperacion'] ?? 1);
            $payload['resumen']['pagos'] = $paymentSnapshot['payments'] ?? null;
            if (array_key_exists('numPagoElectronico', $payload['resumen'])) {
                $payload['resumen']['numPagoElectronico'] = $paymentSnapshot['electronic_payment_number'] ?? null;
            }
        }

        $validation = (new DtePreIssueValidationService())->validate($payload, $document);
        $encodedIssues = json_encode([
            'errors' => $validation['errors'],
            'warnings' => $validation['warnings'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($db->fieldExists('preissue_validation_status', 'dte_documents')) {
            $db->table('dte_documents')->where('id', (int) $document['id'])->update([
                'preissue_validation_status' => $validation['valid'] ? 'valid' : 'invalid',
                'preissue_validation_issues_json' => $encodedIssues !== false ? $encodedIssues : null,
                'preissue_validated_at' => $validation['validated_at'],
                'modify_user' => $this->actor(),
                'modify_date' => date('Y-m-d H:i:s'),
            ]);
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('No fue posible serializar el JSON DTE de pre-emisión.');
        }

        $builderBlocking = array_values(array_filter(
            $base['issues'] ?? [],
            static fn(string $issue): bool => ! str_starts_with($issue, 'Número de control pendiente')
        ));
        $ready = $builderBlocking === [] && $validation['valid'];

        return $base + [
            'payload' => $payload,
            'json' => $json,
            'preissue_validation' => $validation,
            'payment_snapshot' => $paymentSnapshot,
            'structurally_ready' => $ready,
            'final_schema_ready' => $ready && ! empty($document['control_number']),
        ];
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
