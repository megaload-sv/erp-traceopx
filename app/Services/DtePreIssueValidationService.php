<?php

namespace App\Services;

class DtePreIssueValidationService
{
    public function validate(array $payload, array $document): array
    {
        $errors = [];
        $warnings = [];
        $code = (string) ($document['document_code'] ?? '');
        $schema = (string) ($document['schema_file'] ?? '');

        if ($payload === []) {
            $this->error($errors, '$', 'No existe payload DTE para validar.');
            return $this->result($errors, $warnings, $schema);
        }

        $id = $payload['identificacion'] ?? null;
        if (! is_array($id)) {
            $this->error($errors, 'identificacion', 'El bloque identificacion es obligatorio.');
        } else {
            $expected = match ($code) { 'FCF' => ['01', 1], 'CCF' => ['03', 3], 'FEX' => ['11', 1], default => [null, null] };
            if ($expected[0] !== null && (string) ($id['tipoDte'] ?? '') !== $expected[0]) {
                $this->error($errors, 'identificacion.tipoDte', 'El tipo DTE no corresponde al schema configurado.');
            }
            if ($expected[1] !== null && (int) ($id['version'] ?? 0) !== $expected[1]) {
                $this->error($errors, 'identificacion.version', 'La versión del DTE no corresponde al schema configurado.');
            }
            if (! in_array((string) ($id['ambiente'] ?? ''), ['00', '01'], true)) {
                $this->error($errors, 'identificacion.ambiente', 'Ambiente inválido; debe ser 00 o 01.');
            }
            $uuid = strtoupper((string) ($id['codigoGeneracion'] ?? ''));
            if (! preg_match('/^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}$/', $uuid)) {
                $this->error($errors, 'identificacion.codigoGeneracion', 'Código de Generación UUID inválido.');
            }
            if (empty($id['numeroControl'])) {
                $this->warning($warnings, 'identificacion.numeroControl', 'Pendiente de asignar en la preparación final; no bloquea la pre-emisión.');
            }
            if ((string) ($id['tipoMoneda'] ?? '') !== 'USD') {
                $this->error($errors, 'identificacion.tipoMoneda', 'La moneda del DTE debe ser USD.');
            }
        }

        $this->validateIssuer($payload['emisor'] ?? null, $errors);
        $this->validateReceiver($code, $payload['receptor'] ?? null, $errors);
        $this->validateItems($code, $payload['cuerpoDocumento'] ?? null, $errors);
        $this->validateSummary($payload['resumen'] ?? null, $errors, $warnings);

        return $this->result($errors, $warnings, $schema);
    }

    private function validateIssuer(mixed $issuer, array &$errors): void
    {
        if (! is_array($issuer)) {
            $this->error($errors, 'emisor', 'El bloque emisor es obligatorio.');
            return;
        }
        foreach (['nit','nrc','nombre','codActividad','descActividad','tipoEstablecimiento','direccion','correo'] as $field) {
            if ($this->emptyValue($issuer[$field] ?? null)) {
                $this->error($errors, 'emisor.' . $field, 'Dato obligatorio del emisor no disponible.');
            }
        }
        if (is_array($issuer['direccion'] ?? null)) {
            foreach (['departamento','municipio','complemento'] as $field) {
                if ($this->emptyValue($issuer['direccion'][$field] ?? null)) {
                    $this->error($errors, 'emisor.direccion.' . $field, 'Dato obligatorio de dirección del emisor no disponible.');
                }
            }
        }
    }

    private function validateReceiver(string $code, mixed $receiver, array &$errors): void
    {
        if (! is_array($receiver)) {
            if ($code !== 'FCF') {
                $this->error($errors, 'receptor', 'El receptor es obligatorio para este tipo de DTE.');
            }
            return;
        }
        if ($code === 'CCF') {
            foreach (['nit','nrc','nombre','codActividad','descActividad','direccion','correo'] as $field) {
                if ($this->emptyValue($receiver[$field] ?? null)) $this->error($errors, 'receptor.' . $field, 'Dato obligatorio del receptor CCF no disponible.');
            }
        } elseif ($code === 'FEX') {
            foreach (['nombre','codPais','nombrePais','complemento','tipoDocumento','numDocumento','tipoPersona'] as $field) {
                if ($this->emptyValue($receiver[$field] ?? null)) $this->error($errors, 'receptor.' . $field, 'Dato obligatorio del receptor de exportación no disponible.');
            }
        }
    }

    private function validateItems(string $code, mixed $items, array &$errors): void
    {
        if (! is_array($items) || $items === []) {
            $this->error($errors, 'cuerpoDocumento', 'Debe existir al menos un ítem.');
            return;
        }
        foreach ($items as $index => $item) {
            $path = 'cuerpoDocumento.' . $index;
            if (! is_array($item)) {
                $this->error($errors, $path, 'Ítem inválido.');
                continue;
            }
            if ((float) ($item['cantidad'] ?? 0) <= 0) $this->error($errors, $path . '.cantidad', 'La cantidad debe ser mayor que cero.');
            $unit = (int) ($item['uniMedida'] ?? 0);
            if ($unit < 1 || $unit > 99) $this->error($errors, $path . '.uniMedida', 'Unidad CAT-014 inválida.');
            if (trim((string) ($item['descripcion'] ?? '')) === '') $this->error($errors, $path . '.descripcion', 'La descripción es obligatoria.');
            $taxed = (float) ($item['ventaGravada'] ?? 0);
            $taxes = $item['tributos'] ?? null;
            if ($taxed > 0 && (! is_array($taxes) || $taxes === [])) $this->error($errors, $path . '.tributos', 'Una venta gravada debe indicar al menos un tributo.');
            if ($taxed <= 0 && $taxes !== null) $this->error($errors, $path . '.tributos', 'Una línea sin venta gravada debe llevar tributos null.');
            if ($code === 'FEX' && $taxed > 0 && (! is_array($taxes) || ! in_array('C3', $taxes, true))) {
                $this->error($errors, $path . '.tributos', 'La venta gravada de exportación debe utilizar C3.');
            }
            if ($code === 'FCF' && ! array_key_exists('ivaItem', $item)) $this->error($errors, $path . '.ivaItem', 'FCF requiere ivaItem por línea.');
        }
    }

    private function validateSummary(mixed $summary, array &$errors, array &$warnings): void
    {
        if (! is_array($summary)) {
            $this->error($errors, 'resumen', 'El resumen fiscal es obligatorio.');
            return;
        }
        $condition = (int) ($summary['condicionOperacion'] ?? 0);
        if (! in_array($condition, [1,2,3], true)) {
            $this->error($errors, 'resumen.condicionOperacion', 'CAT-016 inválido; debe corresponder a 1, 2 o 3.');
        }
        $payments = $summary['pagos'] ?? null;
        if ($payments !== null && ! is_array($payments)) {
            $this->error($errors, 'resumen.pagos', 'Pagos debe ser array o null.');
        }
        if (is_array($payments)) {
            foreach ($payments as $index => $payment) {
                $path = 'resumen.pagos.' . $index;
                $code = (string) ($payment['codigo'] ?? '');
                if (! preg_match('/^(0[1-9]|1[0-4]|99)$/', $code)) $this->error($errors, $path . '.codigo', 'Código CAT-017 inválido.');
                if ((float) ($payment['montoPago'] ?? -1) < 0) $this->error($errors, $path . '.montoPago', 'Monto de pago inválido.');
                $reference = $payment['referencia'] ?? null;
                if ($reference !== null && mb_strlen((string) $reference) > 50) $this->error($errors, $path . '.referencia', 'La referencia excede 50 caracteres.');
                if ($condition === 2) {
                    if (! preg_match('/^0[1-3]$/', (string) ($payment['plazo'] ?? ''))) $this->error($errors, $path . '.plazo', 'CAT-018 es obligatorio para operaciones a crédito.');
                    if (! is_numeric($payment['periodo'] ?? null) || (float) $payment['periodo'] <= 0) $this->error($errors, $path . '.periodo', 'El período es obligatorio para operaciones a crédito.');
                }
            }
            $paymentTotal = round(array_sum(array_map(static fn(array $p): float => (float) ($p['montoPago'] ?? 0), $payments)), 2);
            $totalPayable = round((float) ($summary['totalPagar'] ?? 0), 2);
            if (abs($paymentTotal - $totalPayable) > 0.01) {
                $this->warning($warnings, 'resumen.pagos', 'Los pagos confirmados suman $' . number_format($paymentTotal, 2) . ' y el total a pagar del DTE es $' . number_format($totalPayable, 2) . '. Esto puede ser válido si existen saldos pendientes o pagos posteriores.');
            }
        }
        if (trim((string) ($summary['totalLetras'] ?? '')) === '') $this->error($errors, 'resumen.totalLetras', 'El total en letras es obligatorio.');
    }

    private function result(array $errors, array $warnings, string $schema): array
    {
        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
            'schema_file' => $schema,
            'validated_at' => date('Y-m-d H:i:s'),
            'mode' => 'pre_issue_schema_aware',
        ];
    }

    private function error(array &$errors, string $path, string $message): void { $errors[] = ['path' => $path, 'message' => $message]; }
    private function warning(array &$warnings, string $path, string $message): void { $warnings[] = ['path' => $path, 'message' => $message]; }
    private function emptyValue(mixed $value): bool { return $value === null || (is_string($value) && trim($value) === '') || (is_array($value) && $value === []); }
}
