<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class MissionLogService
{
    private const EVENT_TYPES = [
        'arrival' => ['category' => 'movement', 'label' => 'Llegada al sitio'],
        'operation_start' => ['category' => 'operation', 'label' => 'Inicio de maniobra'],
        'progress' => ['category' => 'operation', 'label' => 'Avance operativo'],
        'standby' => ['category' => 'standby', 'label' => 'Espera / Stand by'],
        'client_instruction' => ['category' => 'client', 'label' => 'Instrucción del cliente'],
        'condition_change' => ['category' => 'condition', 'label' => 'Cambio de condición'],
        'observation' => ['category' => 'observation', 'label' => 'Observación'],
        'general' => ['category' => 'operation', 'label' => 'Nota general'],
    ];

    public function eventTypes(): array
    {
        return self::EVENT_TYPES;
    }

    public function addManualEntry(
        int $workOrderId,
        string $eventType,
        string $description,
        ?string $occurredAt = null,
        bool $publishToCase = false
    ): int {
        $db = db_connect();

        $order = $db->table('work_orders')
            ->where('id', $workOrderId)
            ->where('delete_date', null)
            ->get()->getRowArray();

        if ($order === null) {
            throw new RuntimeException('Orden de Trabajo no encontrada.');
        }
        if ($order['status'] !== 'in_progress') {
            throw new RuntimeException('Solo se pueden registrar avances manuales mientras la Orden de Trabajo está en ejecución.');
        }
        if (! array_key_exists($eventType, self::EVENT_TYPES)) {
            throw new RuntimeException('El tipo de evento seleccionado no es válido.');
        }

        $cleanDescription = trim($description);
        if ($cleanDescription === '') {
            throw new RuntimeException('Ingrese una descripción para el avance operativo.');
        }

        $eventAt = $this->normalizeDateTime($occurredAt) ?? date('Y-m-d H:i:s');
        $startedAt = ! empty($order['started_at']) ? strtotime((string) $order['started_at']) : null;
        $eventTimestamp = strtotime($eventAt);
        if ($startedAt !== null && $eventTimestamp < $startedAt) {
            throw new RuntimeException('La fecha del avance no puede ser anterior al inicio real de la Orden de Trabajo.');
        }
        if ($eventTimestamp > time() + 300) {
            throw new RuntimeException('La fecha del avance no puede estar en el futuro.');
        }

        $definition = self::EVENT_TYPES[$eventType];
        $title = $definition['label'];
        $eventCode = 'mission.' . $eventType;
        $now = date('Y-m-d H:i:s');

        $db->transBegin();
        try {
            $db->table('mission_logs')->insert([
                'work_order_id' => $workOrderId,
                'service_case_id' => (int) $order['service_case_id'],
                'log_type' => 'manual',
                'category' => $definition['category'],
                'event_code' => $eventCode,
                'title' => $title,
                'description' => $cleanDescription,
                'visibility' => 'internal',
                'occurred_at' => $eventAt,
                'actor_user_id' => session('auth_user_id') ?: null,
                'actor_employee_id' => null,
                'metadata_json' => json_encode([
                    'event_type' => $eventType,
                    'published_to_case' => $publishToCase,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);

            $logId = (int) $db->insertID();
            if ($logId <= 0) {
                throw new RuntimeException('No fue posible registrar el avance operativo.');
            }

            if ($publishToCase) {
                $db->table('service_case_events')->insert([
                    'service_case_id' => (int) $order['service_case_id'],
                    'event_code' => $eventCode,
                    'title' => $title,
                    'description' => $cleanDescription,
                    'entity_type' => 'mission_log',
                    'entity_id' => $logId,
                    'occurred_at' => $eventAt,
                    'entry_user' => $this->actor(),
                    'entry_date' => $now,
                ]);
            }

            (new ActivityService())->record(
                'work_order',
                $workOrderId,
                $eventCode,
                $title,
                $cleanDescription
            );

            $db->transCommit();

            // La etapa no cambia, pero mantenemos al Expediente sincronizado con el estado real.
            (new ProcessEngineService())->evaluate((int) $order['service_case_id']);

            return $logId;
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function normalizeDateTime(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new RuntimeException('La fecha y hora del avance no son válidas.');
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: 'system');
    }
}
