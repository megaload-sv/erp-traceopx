<?php

namespace App\Services;

use RuntimeException;

class DteReceiverService
{
    public function ensureSnapshot(int $documentId): array
    {
        $db = db_connect();
        $document = $db->table('dte_documents d')
            ->select('d.*, dt.code AS document_code')
            ->join('dte_document_types dt', 'dt.id = d.document_type_id')
            ->where('d.id', $documentId)
            ->get()->getRowArray();

        if ($document === null) {
            throw new RuntimeException('Documento DTE no encontrado.');
        }

        if (! empty($document['receiver_snapshot_at'])) {
            return $this->validateAndPersist($documentId);
        }

        $customer = $db->table('customers c')
            ->select('c.*, ea.code AS activity_code, ea.name AS activity_name, co.code AS country_code, co.name AS country_name, dep.code AS department_code, mun.code AS municipality_code')
            ->join('mh_economic_activities ea', 'ea.id = c.mh_economic_activity_id', 'left')
            ->join('mh_countries co', 'co.id = c.tax_country_id', 'left')
            ->join('mh_departments dep', 'dep.id = c.tax_department_id', 'left')
            ->join('mh_municipalities mun', 'mun.id = c.tax_municipality_id', 'left')
            ->where('c.id', (int) $document['customer_id'])
            ->get()->getRowArray();

        if ($customer === null) {
            throw new RuntimeException('Cliente relacionado al DTE no encontrado.');
        }

        $address = $db->table('customer_addresses ca')
            ->select('ca.*, co.code AS country_code, co.name AS country_name, dep.code AS department_code, mun.code AS municipality_code')
            ->join('mh_countries co', 'co.id = ca.country_id', 'left')
            ->join('mh_departments dep', 'dep.id = ca.department_id', 'left')
            ->join('mh_municipalities mun', 'mun.id = ca.municipality_id', 'left')
            ->where('ca.customer_id', (int) $customer['id'])
            ->where('ca.status', 1)
            ->where('ca.delete_date', null)
            ->orderBy('ca.address_type = "fiscal"', 'DESC', false)
            ->orderBy('ca.is_primary', 'DESC')
            ->orderBy('ca.id', 'ASC')
            ->get(1)->getRowArray();

        $taxId = preg_replace('/[^0-9]/', '', (string) ($customer['tax_id'] ?? ''));
        $documentType = in_array(strlen($taxId), [9, 14], true) ? '36' : null;
        $now = date('Y-m-d H:i:s');

        $db->table('dte_documents')->where('id', $documentId)->update([
            'receiver_name_snapshot' => $customer['business_name'] ?? null,
            'receiver_trade_name_snapshot' => $customer['trade_name'] ?? null,
            'receiver_document_type' => $documentType,
            'receiver_document_number' => $taxId !== '' ? $taxId : null,
            'receiver_nrc' => preg_replace('/[^0-9]/', '', (string) ($customer['registration_number'] ?? '')) ?: null,
            'receiver_activity_code' => $customer['activity_code'] ?? null,
            'receiver_activity_description' => $customer['activity_name'] ?? null,
            'receiver_email' => $customer['email'] ?? null,
            'receiver_phone' => $customer['phone'] ?? null,
            'receiver_country_code' => $address['country_code'] ?? $customer['country_code'] ?? null,
            'receiver_country_name' => $address['country_name'] ?? $customer['country_name'] ?? null,
            'receiver_person_type' => (string) ($customer['customer_type'] ?? '') === 'company' ? 1 : 2,
            'receiver_department_code' => $address['department_code'] ?? $customer['department_code'] ?? null,
            'receiver_municipality_code' => $address['municipality_code'] ?? $customer['municipality_code'] ?? null,
            'receiver_address' => $address['address_line'] ?? null,
            'receiver_source_address_id' => isset($address['id']) ? (int) $address['id'] : null,
            'receiver_snapshot_at' => $now,
            'modify_user' => $this->actor(),
            'modify_date' => $now,
        ]);

        return $this->validateAndPersist($documentId);
    }

    public function update(int $billingCaseId, array $input): array
    {
        $db = db_connect();
        $document = $db->table('dte_documents d')
            ->select('d.*, dt.code AS document_code')
            ->join('dte_document_types dt', 'dt.id = d.document_type_id')
            ->where('d.billing_case_id', $billingCaseId)
            ->get()->getRowArray();

        if ($document === null || (string) $document['status'] !== 'draft') {
            throw new RuntimeException('Solo se puede modificar el receptor de un DTE en borrador.');
        }

        $data = [
            'receiver_name_snapshot' => $this->nullable($input['receiver_name'] ?? null),
            'receiver_trade_name_snapshot' => $this->nullable($input['receiver_trade_name'] ?? null),
            'receiver_document_type' => $this->nullable($input['receiver_document_type'] ?? null),
            'receiver_document_number' => $this->nullable($input['receiver_document_number'] ?? null),
            'receiver_nrc' => $this->digitsOrNull($input['receiver_nrc'] ?? null),
            'receiver_activity_code' => $this->nullable($input['receiver_activity_code'] ?? null),
            'receiver_activity_description' => $this->nullable($input['receiver_activity_description'] ?? null),
            'receiver_email' => strtolower((string) ($this->nullable($input['receiver_email'] ?? null) ?? '')) ?: null,
            'receiver_phone' => $this->nullable($input['receiver_phone'] ?? null),
            'receiver_country_code' => $this->nullable($input['receiver_country_code'] ?? null),
            'receiver_country_name' => $this->nullable($input['receiver_country_name'] ?? null),
            'receiver_person_type' => ($input['receiver_person_type'] ?? '') !== '' ? (int) $input['receiver_person_type'] : null,
            'receiver_department_code' => $this->nullable($input['receiver_department_code'] ?? null),
            'receiver_municipality_code' => $this->nullable($input['receiver_municipality_code'] ?? null),
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

    private function validateAndPersist(int $documentId): array
    {
        $db = db_connect();
        $d = $db->table('dte_documents d')
            ->select('d.*, dt.code AS document_code')
            ->join('dte_document_types dt', 'dt.id = d.document_type_id')
            ->where('d.id', $documentId)->get()->getRowArray();
        if ($d === null) throw new RuntimeException('Documento DTE no encontrado.');

        $issues = [];
        $code = (string) $d['document_code'];

        if ($code === 'CCF') {
            $this->require($issues, $d, [
                'receiver_document_number'=>'NIT', 'receiver_nrc'=>'NRC', 'receiver_name_snapshot'=>'Razón social',
                'receiver_activity_code'=>'Actividad económica', 'receiver_activity_description'=>'Descripción de actividad',
                'receiver_department_code'=>'Departamento', 'receiver_municipality_code'=>'Municipio',
                'receiver_address'=>'Dirección', 'receiver_email'=>'Correo electrónico',
            ]);
            if (($d['receiver_document_number'] ?? null) && ! preg_match('/^([0-9]{14}|[0-9]{9})$/', (string)$d['receiver_document_number'])) {
                $issues[] = 'El NIT del receptor debe contener 9 o 14 dígitos.';
            }
        } elseif ($code === 'FEX') {
            $this->require($issues, $d, [
                'receiver_name_snapshot'=>'Nombre o razón social', 'receiver_country_code'=>'País', 'receiver_country_name'=>'Nombre del país',
                'receiver_address'=>'Complemento de dirección', 'receiver_document_type'=>'Tipo de documento',
                'receiver_document_number'=>'Número de documento', 'receiver_person_type'=>'Tipo de persona',
                'receiver_activity_description'=>'Descripción de actividad',
            ]);
        } elseif ($code === 'FCF') {
            // El schema permite receptor nulo; desde $1,095 exige identificación y nombre.
            if ((float)($d['operation_total'] ?? 0) >= 1095) {
                $this->require($issues, $d, [
                    'receiver_document_type'=>'Tipo de documento', 'receiver_document_number'=>'Número de documento', 'receiver_name_snapshot'=>'Nombre del receptor',
                ]);
            }
            if (($d['receiver_document_type'] ?? null) === '36' && ! preg_match('/^([0-9]{14}|[0-9]{9})$/', (string)($d['receiver_document_number'] ?? ''))) {
                $issues[] = 'Para tipo de documento 36, el número debe ser NIT de 9 o 14 dígitos.';
            }
            if (($d['receiver_document_type'] ?? null) === '13' && ! preg_match('/^[0-9]{8}-[0-9]$/', (string)($d['receiver_document_number'] ?? ''))) {
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
            if ($value === null || trim((string)$value) === '') {
                $issues[] = 'Falta ' . $label . ' del receptor.';
            }
        }
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function digitsOrNull(mixed $value): ?string
    {
        $value = preg_replace('/[^0-9]/', '', (string)$value);
        return $value === '' ? null : $value;
    }

    private function actor(): string
    {
        return (string)(session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
