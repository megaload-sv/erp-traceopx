<?php

namespace App\Services;

class ServiceCaseBillingRuleService
{
    public function derive(?array $paymentTerm): array
    {
        $structuredRequiresAdvance = isset($paymentTerm['requires_advance'])
            ? (int) $paymentTerm['requires_advance'] === 1
            : null;
        $structuredPercentage = isset($paymentTerm['minimum_advance_percentage'])
            ? (float) $paymentTerm['minimum_advance_percentage']
            : null;

        if ($structuredRequiresAdvance !== null) {
            $requiresAdvance = $structuredRequiresAdvance;
            $percentage = $requiresAdvance ? max(0, min(100, (float) ($structuredPercentage ?? 0))) : 0.0;
        } else {
            $text = mb_strtoupper(trim((string) ($paymentTerm['name'] ?? $paymentTerm['description'] ?? '')));
            $requiresAdvance = str_contains($text, 'ANTICIP');
            $percentage = 0.0;
            if ($requiresAdvance && preg_match('/(\d{1,3})\s*%/', $text, $matches) === 1) {
                $percentage = min(100, (float) $matches[1]);
            }
        }

        return [
            'requires_advance' => $requiresAdvance ? 1 : 0,
            'advance_percentage' => $percentage,
            'coordination_blocked_until_advance' => $requiresAdvance ? 1 : 0,
            'rule_notes' => $requiresAdvance
                ? sprintf('La coordinación queda bloqueada hasta registrar y validar el anticipo requerido de %.2f%%.', $percentage)
                : 'La condición comercial no exige anticipo previo para liberar coordinación.',
        ];
    }
}
