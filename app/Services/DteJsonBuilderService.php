<?php

namespace App\Services;

use RuntimeException;

class DteJsonBuilderService
{
    public function buildForBillingCase(int $billingCaseId): array
    {
        $db = db_connect();
        $document = $db->table('dte_documents d')
            ->select('d.*, dt.code AS document_code, dt.mh_code, dt.name AS document_name')
            ->join('dte_document_types dt', 'dt.id = d.document_type_id')
            ->where('d.billing_case_id', $billingCaseId)
            ->get()->getRowArray();

        if ($document === null) {
            throw new RuntimeException('Documento DTE no encontrado para construir JSON.');
        }

        $billing = $db->table('billing_cases bc')
            ->select('bc.*, pt.term_type, pt.requires_advance, pt.minimum_advance_percentage')
            ->join('payment_terms pt', 'pt.id = bc.payment_term_id', 'left')
            ->where('bc.id', $billingCaseId)
            ->where('bc.delete_date', null)
            ->get()->getRowArray();
        if ($billing === null) {
            throw new RuntimeException('Preparación de facturación no encontrada para construir DTE.');
        }

        $issuer = $db->table('dte_issuer_profiles')->where('status', 1)->orderBy('id')->get()->getRowArray();
        $items = $db->table('dte_document_items')->where('dte_document_id', (int) $document['id'])->orderBy('sequence')->get()->getResultArray();
        $establishment = $this->defaultEstablishment();
        $pointOfSale = $this->defaultPointOfSale($establishment ? (int) $establishment['id'] : null);

        $issues = [];
        if ($issuer === null) {
            $issues[] = 'Configure los datos fiscales del emisor antes de preparar el DTE.';
        }
        if (($document['receiver_validation_status'] ?? null) !== 'valid') {
            $issues[] = 'El receptor fiscal todavía no supera las validaciones del DTE.';
        }
        if ($items === []) {
            $issues[] = 'El DTE no contiene ítems.';
        }
        foreach ($items as $item) {
            if (empty($item['mh_unit_code'])) {
                $issues[] = 'El ítem #' . $item['sequence'] . ' no tiene unidad CAT-014 asignada.';
            }
        }
        if ($establishment === null || $pointOfSale === null) {
            $issues[] = 'Configure una sucursal y punto de venta DTE predeterminados.';
        }
        if (empty($document['generation_code'])) {
            $issues[] = 'El Código de Generación UUID v4 no está disponible.';
        }

        $code = (string) $document['document_code'];
        $payload = match ($code) {
            'FCF' => $this->buildFcf($document, $billing, $issuer, $items, $establishment, $pointOfSale),
            'CCF' => $this->buildCcf($document, $billing, $issuer, $items, $establishment, $pointOfSale),
            'FEX' => $this->buildFex($document, $billing, $issuer, $items, $establishment, $pointOfSale),
            default => null,
        };

        if ($payload === null) {
            $issues[] = 'El JSON Builder de pre-emisión todavía no está habilitado para ' . $code . '.';
            $payload = [];
        }

        // El número de control se reserva únicamente durante la emisión final.
        if (empty($document['control_number'])) {
            $issues[] = 'Número de control pendiente: se asignará únicamente al preparar la emisión final.';
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('No fue posible serializar el JSON técnico del DTE.');
        }

        $blockingIssues = array_values(array_filter($issues, static fn (string $issue): bool => !str_starts_with($issue, 'Número de control pendiente')));

        return [
            'payload' => $payload,
            'json' => $json,
            'issues' => $issues,
            'structurally_ready' => $blockingIssues === [],
            'final_schema_ready' => $blockingIssues === [] && ! empty($document['control_number']),
            'mode' => empty($document['control_number']) ? 'pre_issue' : 'final',
        ];
    }

    private function buildFcf(array $d, array $billing, ?array $issuer, array $items, ?array $est, ?array $pos): array
    {
        return [
            'identificacion' => $this->identification($d, 'motivoContin'),
            'documentoRelacionado' => null,
            'emisor' => $this->issuer($issuer, $est, $pos),
            'receptor' => $this->localReceiver($d, true),
            'otrosDocumentos' => null,
            'ventaTercero' => null,
            'cuerpoDocumento' => array_map(fn (array $item): array => $this->fcfItem($item), $items),
            'resumen' => $this->localSummary($d, $billing, true),
            'extension' => null,
            'apendice' => null,
        ];
    }

    private function buildCcf(array $d, array $billing, ?array $issuer, array $items, ?array $est, ?array $pos): array
    {
        return [
            'identificacion' => $this->identification($d, 'motivoContin'),
            'documentoRelacionado' => null,
            'emisor' => $this->issuer($issuer, $est, $pos),
            'receptor' => $this->localReceiver($d, false),
            'otrosDocumentos' => null,
            'ventaTercero' => null,
            'cuerpoDocumento' => array_map(fn (array $item): array => $this->ccfItem($item), $items),
            'resumen' => $this->localSummary($d, $billing, false),
            'extension' => null,
            'apendice' => null,
        ];
    }

    private function buildFex(array $d, array $billing, ?array $issuer, array $items, ?array $est, ?array $pos): array
    {
        return [
            'identificacion' => $this->identification($d, 'motivoContigencia'),
            'emisor' => $this->issuer($issuer, $est, $pos),
            'receptor' => [
                'nombre' => $d['receiver_name_snapshot'] ?: null,
                'codPais' => $d['receiver_country_code'] ?: null,
                'nombrePais' => $d['receiver_country_name'] ?: null,
                'complemento' => $d['receiver_address'] ?: null,
                'tipoDocumento' => $d['receiver_document_type'] ?: null,
                'numDocumento' => $d['receiver_document_number'] ?: null,
                'nombreComercial' => $d['receiver_trade_name_snapshot'] ?: null,
                'tipoPersona' => ! empty($d['receiver_person_type']) ? (int) $d['receiver_person_type'] : null,
                'codActividad' => $d['receiver_activity_code'] ?: null,
                'descActividad' => $d['receiver_activity_description'] ?: null,
                'telefono' => $d['receiver_phone'] ?: null,
                'correo' => $d['receiver_email'] ?: null,
            ],
            'otrosDocumentos' => null,
            'ventaTercero' => null,
            'cuerpoDocumento' => array_map(fn (array $item): array => $this->fexItem($item), $items),
            'resumen' => [
                'totalGravada' => $this->money($d['taxed_total']),
                'descuento' => 0.00,
                'porcentajeDescuento' => $this->money($d['discount_percentage'] ?? 0),
                'totalDescu' => $this->money($d['discount_total']),
                'seguro' => null,
                'flete' => null,
                'montoTotalOperacion' => $this->money($d['operation_total']),
                'totalNoGravado' => $this->money($d['non_taxable_total'] ?? 0),
                'totalPagar' => $this->money($d['amount_payable']),
                'totalLetras' => $this->amountInWords((float) $d['amount_payable']),
                'condicionOperacion' => $this->operationCondition($billing),
                'pagos' => null,
                'codIncoterms' => null,
                'descIncoterms' => null,
                'numPagoElectronico' => null,
                'observaciones' => null,
            ],
            'apendice' => null,
        ];
    }

    private function identification(array $d, string $reasonKey): array
    {
        $date = $d['issue_date'] ?: date('Y-m-d');
        $time = $d['issue_time'] ?: date('H:i:s');
        return [
            'version' => (int) $d['schema_version'],
            'ambiente' => (string) $d['environment'],
            'tipoDte' => (string) $d['mh_code'],
            'numeroControl' => $d['control_number'] ?: null,
            'codigoGeneracion' => strtoupper((string) $d['generation_code']),
            'tipoModelo' => (int) ($d['generation_model'] ?: 1),
            'tipoOperacion' => (int) ($d['operation_type'] ?: 1),
            'tipoContingencia' => ! empty($d['contingency_type']) ? (int) $d['contingency_type'] : null,
            $reasonKey => $d['contingency_reason'] ?: null,
            'fecEmi' => $date,
            'horEmi' => $time,
            'tipoMoneda' => (string) ($d['currency_code'] ?: 'USD'),
        ];
    }

    private function issuer(?array $issuer, ?array $est, ?array $pos): array
    {
        return [
            'nit' => $this->digits($issuer['nit'] ?? null),
            'nrc' => $this->digits($issuer['nrc'] ?? null),
            'nombre' => $issuer['legal_name'] ?? null,
            'codActividad' => $issuer['activity_code'] ?? null,
            'descActividad' => $issuer['activity_description'] ?? null,
            'nombreComercial' => $issuer['trade_name'] ?? null,
            'tipoEstablecimiento' => $issuer['establishment_type_code'] ?? null,
            'direccion' => [
                'departamento' => $issuer['department_code'] ?? null,
                'municipio' => $issuer['municipality_code'] ?? null,
                'complemento' => $issuer['address_complement'] ?? null,
            ],
            'telefono' => $issuer['phone'] ?? null,
            'correo' => $issuer['email'] ?? null,
            'codEstableMH' => $est['mh_code'] ?? ($issuer['mh_establishment_code'] ?? null),
            'codEstable' => $est['internal_code'] ?? ($issuer['mh_establishment_code_alt'] ?? null),
            'codPuntoVentaMH' => $pos['mh_code'] ?? null,
            'codPuntoVenta' => $pos['internal_code'] ?? null,
        ];
    }

    private function localReceiver(array $d, bool $fcf): array
    {
        $base = [
            'nrc' => $d['receiver_nrc'] ?: null,
            'nombre' => $d['receiver_name_snapshot'] ?: null,
            'codActividad' => $d['receiver_activity_code'] ?: null,
            'descActividad' => $d['receiver_activity_description'] ?: null,
            'direccion' => ($d['receiver_department_code'] || $d['receiver_municipality_code'] || $d['receiver_address']) ? [
                'departamento' => $d['receiver_department_code'] ?: null,
                'municipio' => $d['receiver_municipality_code'] ?: null,
                'complemento' => $d['receiver_address'] ?: null,
            ] : null,
            'telefono' => $d['receiver_phone'] ?: null,
            'correo' => $d['receiver_email'] ?: null,
        ];

        if ($fcf) {
            return ['tipoDocumento' => $d['receiver_document_type'] ?: null, 'numDocumento' => $d['receiver_document_number'] ?: null] + $base;
        }

        return [
            'nit' => $d['receiver_document_number'] ?: null,
            'nrc' => $base['nrc'],
            'nombre' => $base['nombre'],
            'codActividad' => $base['codActividad'],
            'descActividad' => $base['descActividad'],
            'nombreComercial' => $d['receiver_trade_name_snapshot'] ?: null,
            'direccion' => $base['direccion'],
            'telefono' => $base['telefono'],
            'correo' => $base['correo'],
        ];
    }

    private function fcfItem(array $i): array
    {
        return [
            'numItem' => (int) $i['sequence'], 'tipoItem' => (int) ($i['item_type'] ?: 2), 'numeroDocumento' => null,
            'codigo' => $i['code'] ?: null, 'codTributo' => $i['tax_code'] ?: null, 'descripcion' => (string) $i['description'],
            'cantidad' => (float) $i['quantity'], 'uniMedida' => ! empty($i['mh_unit_code']) ? (int) $i['mh_unit_code'] : null,
            'precioUni' => $this->decimal8($i['unit_price']), 'montoDescu' => $this->decimal8($i['discount_amount']),
            'ventaNoSuj' => $this->decimal8($i['non_subject_sale']), 'ventaExenta' => $this->decimal8($i['exempt_sale']), 'ventaGravada' => $this->decimal8($i['taxed_sale']),
            'tributos' => $this->taxCodes($i['tax_codes_json'] ?? null), 'psv' => $this->decimal8($i['suggested_sale_price'] ?? 0),
            'noGravado' => $this->decimal8($i['non_taxable_amount'] ?? 0), 'ivaItem' => $this->decimal8($i['iva_item'] ?? 0),
        ];
    }

    private function ccfItem(array $i): array
    {
        $row = $this->fcfItem($i);
        unset($row['ivaItem']);
        return $row;
    }

    private function fexItem(array $i): array
    {
        return [
            'numItem' => (int) $i['sequence'], 'codigo' => $i['code'] ?: null, 'descripcion' => (string) $i['description'],
            'cantidad' => (float) $i['quantity'], 'uniMedida' => ! empty($i['mh_unit_code']) ? (int) $i['mh_unit_code'] : null,
            'precioUni' => $this->decimal8($i['unit_price']), 'montoDescu' => $this->decimal8($i['discount_amount']),
            'ventaGravada' => $this->decimal8($i['taxed_sale']), 'tributos' => $this->taxCodes($i['tax_codes_json'] ?? null),
            'noGravado' => $this->decimal8($i['non_taxable_amount'] ?? 0),
        ];
    }

    private function localSummary(array $d, array $billing, bool $fcf): array
    {
        $summary = [
            'totalNoSuj' => $this->money($d['non_subject_total']), 'totalExenta' => $this->money($d['exempt_total']), 'totalGravada' => $this->money($d['taxed_total']),
            'subTotalVentas' => $this->money($d['subtotal']), 'descuNoSuj' => 0.00, 'descuExenta' => 0.00, 'descuGravada' => 0.00,
            'porcentajeDescuento' => $this->money($d['discount_percentage'] ?? 0), 'totalDescu' => $this->money($d['discount_total']),
            'tributos' => $this->taxSummary($d['tax_summary_json'] ?? null), 'subTotal' => $this->money($d['subtotal']),
            'ivaRete1' => $this->money($d['iva_retained']), 'reteRenta' => $this->money($d['income_tax_retained']),
            'montoTotalOperacion' => $this->money($d['operation_total']), 'totalNoGravado' => $this->money($d['non_taxable_total'] ?? 0),
            'totalPagar' => $this->money($d['amount_payable']), 'totalLetras' => $this->amountInWords((float) $d['amount_payable']),
            'saldoFavor' => $this->money($d['balance_in_favor'] ?? 0), 'condicionOperacion' => $this->operationCondition($billing),
            'pagos' => null, 'numPagoElectronico' => null,
        ];
        if ($fcf) {
            $summary['totalIva'] = $this->money($d['iva_total']);
        } else {
            $summary['ivaPerci1'] = $this->money($d['iva_perceived']);
        }
        return $summary;
    }

    private function defaultEstablishment(): ?array
    {
        $id = (int) ($this->setting('emission', 'default_establishment_id') ?? 0);
        return $id > 0 ? db_connect()->table('dte_establishments')->where(['id' => $id, 'status' => 1])->get()->getRowArray() : null;
    }

    private function defaultPointOfSale(?int $establishmentId): ?array
    {
        $id = (int) ($this->setting('emission', 'default_point_of_sale_id') ?? 0);
        if ($id <= 0 || $establishmentId === null) return null;
        return db_connect()->table('dte_points_of_sale')->where(['id' => $id, 'establishment_id' => $establishmentId, 'status' => 1])->get()->getRowArray();
    }

    private function operationCondition(array $billing): int
    {
        $type = strtolower((string) ($billing['term_type'] ?? ''));
        if (in_array($type, ['credit', 'credito'], true)) return 2;
        if (in_array($type, ['cash', 'contado', 'advance', 'prepaid', 'anticipo'], true)) return 1;
        return 3;
    }

    private function setting(string $group, string $key): ?string
    {
        $row = db_connect()->table('dte_settings')->where(['group_code' => $group, 'setting_key' => $key, 'status' => 1])->get()->getRowArray();
        return $row !== null ? (string) $row['setting_value'] : null;
    }

    private function taxCodes(?string $json): ?array
    {
        if ($json === null || trim($json) === '') return null;
        $decoded = json_decode($json, true);
        return is_array($decoded) && $decoded !== [] ? array_values(array_map('strval', $decoded)) : null;
    }

    private function taxSummary(?string $json): ?array
    {
        if ($json === null || trim($json) === '') return null;
        $decoded = json_decode($json, true);
        return is_array($decoded) && $decoded !== [] ? array_values($decoded) : null;
    }

    private function digits(mixed $value): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $value);
        return $digits === '' ? null : $digits;
    }

    private function money(mixed $value): float { return round((float) $value, 2); }
    private function decimal8(mixed $value): float { return round((float) $value, 8); }

    private function amountInWords(float $amount): string
    {
        $whole = (int) floor(abs($amount));
        $cents = (int) round((abs($amount) - $whole) * 100);
        return strtoupper($this->integerToSpanish($whole) . ' DÓLARES ' . str_pad((string) $cents, 2, '0', STR_PAD_LEFT) . '/100 USD');
    }

    private function integerToSpanish(int $n): string
    {
        if ($n === 0) return 'CERO';
        if ($n < 0) return 'MENOS ' . $this->integerToSpanish(-$n);
        $units = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE', 'VEINTIUNO', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];
        if ($n < 30) return $units[$n];
        $tens = [3 => 'TREINTA', 4 => 'CUARENTA', 5 => 'CINCUENTA', 6 => 'SESENTA', 7 => 'SETENTA', 8 => 'OCHENTA', 9 => 'NOVENTA'];
        if ($n < 100) { $r = $n % 10; return $tens[intdiv($n, 10)] . ($r ? ' Y ' . $units[$r] : ''); }
        if ($n === 100) return 'CIEN';
        $hundreds = [1 => 'CIENTO', 2 => 'DOSCIENTOS', 3 => 'TRESCIENTOS', 4 => 'CUATROCIENTOS', 5 => 'QUINIENTOS', 6 => 'SEISCIENTOS', 7 => 'SETECIENTOS', 8 => 'OCHOCIENTOS', 9 => 'NOVECIENTOS'];
        if ($n < 1000) { $r = $n % 100; return $hundreds[intdiv($n, 100)] . ($r ? ' ' . $this->integerToSpanish($r) : ''); }
        if ($n < 1000000) { $q = intdiv($n, 1000); $r = $n % 1000; return ($q === 1 ? 'MIL' : $this->integerToSpanish($q) . ' MIL') . ($r ? ' ' . $this->integerToSpanish($r) : ''); }
        if ($n < 1000000000000) { $q = intdiv($n, 1000000); $r = $n % 1000000; return ($q === 1 ? 'UN MILLÓN' : $this->integerToSpanish($q) . ' MILLONES') . ($r ? ' ' . $this->integerToSpanish($r) : ''); }
        return (string) $n;
    }
}
