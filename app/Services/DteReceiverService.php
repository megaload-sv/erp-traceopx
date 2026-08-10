<?php

namespace App\Services;

use RuntimeException;

class DteReceiverService
{
    public function ensureSnapshot(int $documentId): array
    {
        $db = db_connect();
        $document = $this->documentById($documentId);
        if (! empty($document['receiver_snapshot_at'])) {
            return $this->validateAndPersist($documentId);
        }

        $snapshot = $this->masterSnapshot($document);
        $snapshot['receiver_snapshot_at'] = date('Y-m-d H:i:s');
        $snapshot['modify_user'] = $this->actor();
        $snapshot['modify_date'] = date('Y-m-d H:i:s');

        $db->table('dte_documents')->where('id', $documentId)->update($snapshot);
        return $this->validateAndPersist($documentId);
    }

    public function restoreFromCustomer(int $billingCaseId): array
    {
        $db = db_connect();
        $document = $this->documentByBillingCase($billingCaseId);
        if ((string) $document['status'] !== 'draft') {
            throw new RuntimeException('Solo se puede restaurar el receptor desde Clientes mientras el DTE está en borrador.');
        }

        $snapshot = $this->masterSnapshot($document);
        $snapshot['receiver_snapshot_at'] = date('Y-m-d H:i:s');
        $snapshot['modify_user'] = $this->actor();
        $snapshot['modify_date'] = date('Y-m-d H:i:s');

        $db->table('dte_documents')->where('id', (int) $document['id'])->update($snapshot);
        return $this->validateAndPersist((int) $document['id']);
    }

    public function snapshotMeta(int $documentId): array
    {
        $document = $this->documentById($documentId);
        $master = $this->masterSnapshot($document);

        $fields = [
            'receiver_name_snapshot',
            'receiver_trade_name_snapshot',
            'receiver_document_type',
            'receiver_document_number',
            'receiver_nrc',
            'receiver_activity_code',
            'receiver_activity_description',
            'receiver_email',
            'receiver_phone',
            'receiver_country_code',
            'receiver_country_name',
            'receiver_person_type',
            'receiver_department_code',
            'receiver_municipality_code',
            'receiver_address',
            'receiver_source_address_id',
        ];

        $differences = [];
        foreach ($fields as $field) {
            if ($this->comparable($document[$field] ?? null) !== $this->comparable($master[$field] ?? null)) {
                $differences[] = $field;
            }
        }

        return [
            'source' => $differences === [] ? 'customer' : 'modified',
            'is_modified' => $differences !== [],
            'difference_count' => count($differences),
            'differences' => $differences,
            'master' => $master,
        ];
    }

    public function update(int $billingCaseId, array $input): array
    {
        $db = db_connect();
        $document = $this->documentByBillingCase($billingCaseId);
        if ((string) $document['status'] !== 'draft') {
            throw new RuntimeException('Solo se puede modificar el receptor de un DTE en borrador.');
        }

        $activityCode = $this->nullable($input['receiver_activity_code'] ?? null);
        $activityDescription = $this->nullable($input['receiver_activity_description'] ?? null);
        if ($activityCode !== null) {
            $activity = $db->table('mh_catalog_values')
                ->where('catalog_code', 'CAT-019')->where('code', $activityCode)->where('status', 1)
                ->get()->getRowArray();
            if ($activity === null) {
                throw new RuntimeException('La actividad económica del receptor no existe en CAT-019.');
            }
            $activityDescription = (string) $activity['name'];
        }

        $departmentCode = $this->nullable($input['receiver_department_code'] ?? null);
        $municipalityCode = $this->nullable($input['receiver_municipality_code'] ?? null);
        if ((string) $document['document_code'] !== 'FEX' && $departmentCode !== null) {
            if ($db->table('mh_catalog_values')->where('catalog_code', 'CAT-012')->where('code', $departmentCode)->where('status', 1)->countAllResults() === 0) {
                throw new RuntimeException('El departamento del receptor no existe en CAT-012.');
            }
            if ($municipalityCode !== null && $db->table('mh_catalog_values')->where('catalog_code', 'CAT-013')->where('parent_code', $departmentCode)->where('code', $municipalityCode)->where('status', 1)->countAllResults() === 0) {
                throw new RuntimeException('El municipio seleccionado no corresponde al departamento del receptor.');
            }
        }

        $personType = ($input['receiver_person_type'] ?? '') !== '' ? (int) $input['receiver_person_type'] : null;
        if ($personType !== null && ! in_array($personType, [1, 2], true)) {
            throw new RuntimeException('El tipo de persona del receptor no es válido.');
        }

        $data = [
            'receiver_name_snapshot' => $this->nullable($input['receiver_name'] ?? null),
            'receiver_trade_name_snapshot' => $this->nullable($input['receiver_trade_name'] ?? null),
            'receiver_document_type' => $this->nullable($input['receiver_document_type'] ?? null),
            'receiver_document_number' => $this->nullable($input['receiver_document_number'] ?? null),
            'receiver_nrc' => $this->digitsOrNull($input['receiver_nrc'] ?? null),
            'receiver_activity_code' => $activityCode,
            'receiver_activity_description' => $activityDescription,
            'receiver_email' => strtolower((string) ($this->nullable($input['receiver_email'] ?? null) ?? '')) ?: null,
            'receiver_phone' => $this->nullable($input['receiver_phone'] ?? null),
            'receiver_country_code' => $this->nullable($input['receiver_country_code'] ?? null),
            'receiver_country_name' => $this->nullable($input['receiver_country_name'] ?? null),
            'receiver_person_type' => $personType,
            'receiver_department_code' => $departmentCode,
            'receiver_municipality_code' => $municipalityCode,
            'receiver_address' => $this->nullable($input['receiver_address'] ?? null),
            'receiver_snapshot_at' => date('Y-m-d H:i:s'),
            'modify_user' => $this->actor(),
            'modify_date' => date('Y-m-d H:i:s'),
        ];

        if ($data['receiver_email'] !== null && ! filter_var($data['receiver_email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El correo electrónico del receptor no es válido.');
        }

        $db->table('dte_documents')->where('id', (int) $document['id'])->update($data);
        return $this->validateAndPersist((int) $document['id']);
    }

    public function catalogs(): array
    {
        $db = db_connect();
        return [
            'activities' => $db->table('mh_catalog_values')->where('catalog_code', 'CAT-019')->where('status', 1)->orderBy('code')->get()->getResultArray(),
            'departments' => $db->table('mh_catalog_values')->where('catalog_code', 'CAT-012')->where('status', 1)->orderBy('code')->get()->getResultArray(),
            'municipalities' => $db->table('mh_catalog_values')->where('catalog_code', 'CAT-013')->where('status', 1)->orderBy('parent_code')->orderBy('code')->get()->getResultArray(),
            'countries' => $db->table('mh_countries')->where('status', 1)->where('delete_date', null)->orderBy('name')->get()->getResultArray(),
        ];
    }

    private function masterSnapshot(array $document): array
    {
        $db = db_connect();
        $customer = $db->table('customers c')
            ->select('c.*, ea.code AS activity_code, ea.name AS activity_name, co.code AS country_code, co.name AS country_name, dep.code AS department_code, mun.code AS municipality_code')
            ->join('mh_economic_activities ea', 'ea.id = c.mh_economic_activity_id', 'left')
            ->join('mh_countries co', 'co.id = c.tax_country_id', 'left')
            ->join('mh_departments dep', 'dep.id = c.tax_department_id', 'left')
            ->join('mh_municipalities mun', 'mun.id = c.tax_municipality_id', 'left')
            ->where('c.id', (int) $document['customer_id'])->get()->getRowArray();
        if ($customer === null) {
            throw new RuntimeException('Cliente relacionado al DTE no encontrado.');
        }

        $address = $db->table('customer_addresses ca')
            ->select('ca.*, co.code AS country_code, co.name AS country_name, dep.code AS department_code, mun.code AS municipality_code')
            ->join('mh_countries co', 'co.id = ca.country_id', 'left')
            ->join('mh_departments dep', 'dep.id = ca.department_id', 'left')
            ->join('mh_municipalities mun', 'mun.id = ca.municipality_id', 'left')
            ->where('ca.customer_id', (int) $customer['id'])->where('ca.status', 1)->where('ca.delete_date', null)
            ->orderBy('ca.address_type = "fiscal"', 'DESC', false)->orderBy('ca.is_primary', 'DESC')->orderBy('ca.id', 'ASC')
            ->get(1)->getRowArray();

        $taxIdRaw = trim((string) ($customer['tax_id'] ?? ''));
        $taxIdDigits = preg_replace('/[^0-9]/', '', $taxIdRaw);
        $documentType = in_array(strlen($taxIdDigits), [9, 14], true) ? '36' : null;

        return [
            'receiver_name_snapshot' => $customer['business_name'] ?? null,
            'receiver_trade_name_snapshot' => $customer['trade_name'] ?? null,
            'receiver_document_type' => $documentType,
            'receiver_document_number' => $taxIdDigits !== '' ? $taxIdDigits : null,
            'receiver_nrc' => preg_replace('/[^0-9]/', '', (string) ($customer['registration_number'] ?? '')) ?: null,
            'receiver_activity_code' => $customer['activity_code'] ?? null,
            'receiver_activity_description' => $customer['activity_name'] ?? null,
            'receiver_email' => $customer['email'] ?? null,
            'receiver_phone' => $customer['phone'] ?? null,
            'receiver_country_code' => $address['country_code'] ?? $customer['country_code'] ?? null,
            'receiver_country_name' => $address['country_name'] ?? $customer['country_name'] ?? null,
            'receiver_person_type' => null,
            'receiver_department_code' => $address['department_code'] ?? $customer['department_code'] ?? null,
            'receiver_municipality_code' => $address['municipality_code'] ?? $customer['municipality_code'] ?? null,
            'receiver_address' => $address['address_line'] ?? null,
            'receiver_source_address_id' => isset($address['id']) ? (int) $address['id'] : null,
        ];
    }

    private function documentById(int $documentId): array
    {
        $document = db_connect()->table('dte_documents d')
            ->select('d.*, dt.code AS document_code')
            ->join('dte_document_types dt', 'dt.id = d.document_type_id')
            ->where('d.id', $documentId)->get()->getRowArray();
        if ($document === null) {
            throw new RuntimeException('Documento DTE no encontrado.');
        }
        return $document;
    }

    private function documentByBillingCase(int $billingCaseId): array
    {
        $document = db_connect()->table('dte_documents d')
            ->select('d.*, dt.code AS document_code')
            ->join('dte_document_types dt', 'dt.id = d.document_type_id')
            ->where('d.billing_case_id', $billingCaseId)->get()->getRowArray();
        if ($document === null) {
            throw new RuntimeException('Documento DTE no encontrado.');
        }
        return $document;
    }

    private function validateAndPersist(int $documentId): array
    {
        $db = db_connect();
        $d = $this->documentById($documentId);
        $issues = [];
        $code = (string) $d['document_code'];

        if ($code === 'CCF') {
            $this->require($issues, $d, [
                'receiver_document_number' => 'NIT', 'receiver_nrc' => 'NRC', 'receiver_name_snapshot' => 'Razón social',
                'receiver_activity_code' => 'Actividad económica', 'receiver_activity_description' => 'Descripción de actividad',
                'receiver_department_code' => 'Departamento', 'receiver_municipality_code' => 'Municipio', 'receiver_address' => 'Dirección', 'receiver_email' => 'Correo electrónico',
            ]);
            if (($d['receiver_document_number'] ?? null) && ! preg_match('/^([0-9]{14}|[0-9]{9})$/', (string) $d['receiver_document_number'])) {
                $issues[] = 'El NIT del receptor debe contener 9 o 14 dígitos.';
            }
            if (($d['receiver_nrc'] ?? null) && ! preg_match('/^[0-9]{1,8}$/', (string) $d['receiver_nrc'])) {
                $issues[] = 'El NRC del receptor debe contener únicamente dígitos, máximo 8.';
            }
        } elseif ($code === 'FEX') {
            $this->require($issues, $d, [
                'receiver_name_snapshot' => 'Nombre o razón social', 'receiver_country_code' => 'País', 'receiver_country_name' => 'Nombre del país', 'receiver_address' => 'Complemento de dirección',
                'receiver_document_type' => 'Tipo de documento', 'receiver_document_number' => 'Número de documento', 'receiver_person_type' => 'Tipo de persona', 'receiver_activity_description' => 'Descripción de actividad',
            ]);
        } elseif ($code === 'FCF') {
            if ((float) ($d['operation_total'] ?? 0) >= 1095) {
                $this->require($issues, $d, ['receiver_document_type' => 'Tipo de documento', 'receiver_document_number' => 'Número de documento', 'receiver_name_snapshot' => 'Nombre del receptor']);
            }
            if (($d['receiver_document_type'] ?? null) === '36' && ! preg_match('/^([0-9]{14}|[0-9]{9})$/', (string) ($d['receiver_document_number'] ?? ''))) {
                $issues[] = 'Para tipo de documento 36, el número debe ser NIT de 9 o 14 dígitos.';
            }
            if (($d['receiver_document_type'] ?? null) === '13' && ! preg_match('/^[0-9]{8}-[0-9]$/', (string) ($d['receiver_document_number'] ?? ''))) {
                $issues[] = 'Para tipo de documento 13, el número debe tener formato DUI 00000000-0.';
            }
        }

        $status = $issues === [] ? 'valid' : 'incomplete';
        $db->table('dte_documents')->where('id', $documentId)->update([
            'receiver_validation_status' => $status,
            'receiver_validation_issues_json' => $issues !== [] ? json_encode($issues, JSON_UNESCAPED_UNICODE) : null,
            'modify_user' => $this->actor(),
            'modify_date' => date('Y-m-d H:i:s'),
        ]);

        $d['receiver_validation_status'] = $status;
        $d['receiver_validation_issues_json'] = $issues !== [] ? json_encode($issues, JSON_UNESCAPED_UNICODE) : null;
        return $d;
    }

    private function require(array &$issues, array $row, array $fields): void
    {
        foreach ($fields as $field => $label) {
            $value = $row[$field] ?? null;
            if ($value === null || trim((string) $value) === '') {
                $issues[] = 'Falta ' . $label . ' del receptor.';
            }
        }
    }

    private function comparable(mixed $value): string
    {
        if ($value === null) return '';
        return trim(mb_strtolower((string) $value));
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function digitsOrNull(mixed $value): ?string
    {
        $value = preg_replace('/[^0-9]/', '', (string) $value);
        return $value === '' ? null : $value;
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
