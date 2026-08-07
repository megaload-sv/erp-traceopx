<?php

namespace App\Services;

class ServiceCaseBillingRuleService
{
    public function derive(?array $paymentTerm): array
    {
        $text = mb_strtoupper(trim((string) ($paymentTerm['name'] ?? $paymentTerm['description'] ?? '')));
        $requiresAdvance = str_contains($text, 'ANTICIP');
        $percentage = 0.0;

        if ($requiresAdvance && preg_match('/(\d{1,3})\s*%/', $text, $matches) === 1) {
            $percentage = min(100, (float) $matches[1]);
        } elseif ($requiresAdvance && str_contains($text, '100%')) {
            $percentage = 100.0;
        }

        return [
            'requires_advance' => $requiresAdvance ? 1 : 0,
            'advance_percentage' => $percentage,
            'coordination_blocked_until_advance' => $requiresAdvance ? 1 : 0,
            'rule_notes' => $requiresAdvance
                ? 'La coordinación queda bloqueada hasta registrar y validar el anticipo requerido.'
                : 'No se detectó un anticipo obligatorio en la forma de pago seleccionada.',
        ];
    }
}
