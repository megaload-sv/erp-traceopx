<?php

namespace App\Services;

use InvalidArgumentException;

class QuotationWorkflowService
{
    private const TRANSITIONS = [
        'draft' => ['ready_for_review', 'cancelled'],
        'ready_for_review' => ['draft', 'ready_to_send', 'cancelled'],
        'ready_to_send' => ['ready_for_review', 'sent', 'cancelled'],
        'sent' => ['negotiation', 'accepted', 'rejected', 'expired'],
        'negotiation' => ['ready_for_review', 'accepted', 'rejected', 'expired'],
        'accepted' => [],
        'rejected' => [],
        'expired' => ['draft'],
        'cancelled' => [],
    ];

    public function allowedTransitions(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    public function assertCanTransition(string $from, string $to): void
    {
        if (! in_array($to, $this->allowedTransitions($from), true)) {
            throw new InvalidArgumentException("Transición de cotización no permitida: {$from} → {$to}.");
        }
    }
}
