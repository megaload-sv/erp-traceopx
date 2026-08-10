<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class WorkOrderClosureService
{
    public function readiness(int $workOrderId): array
    {
        $db = db_connect();
        $order = $db->table('work_orders')
            ->where('id', $workOrderId)
            ->where('delete_date', null)
            ->get()->getRowArray();

        if ($order === null) {
            throw new RuntimeException('Orden de Trabajo no encontrada.');
        }

        $acceptance = $db->tableExists('work_order_acceptances')
            ? $db->table('work_order_acceptances')
                ->where('work_order_id', $workOrderId)
                ->whereIn('result', ['accepted', 'accepted_with_observations'])
                ->orderBy('accepted_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->get(1)->getRowArray()
            : null;

        $blocking = [];
        if ($order['status'] !== 'accepted') {
            $blocking[] = 'La Orden de Trabajo debe contar con una aceptación válida del cliente antes del cierre formal.';
        }
        if ($acceptance === null) {
            $blocking[] = 'No existe una aceptación válida del cliente asociada a esta Orden de Trabajo.';
        }

        return [
            'ready' => $blocking === [],
            'blocking_reasons' => $blocking,
            'acceptance' => $acceptance,
        ];
    }

    public function close(int $workOrderId, string $notes = ''): void
    {
        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $cleanNotes = trim($notes);

        $db->transBegin();
        try {
            $order = $db->query(
                'SELECT * FROM work_orders WHERE id = ? AND delete_date IS NULL FOR UPDATE',
                [$workOrderId]
            )->getRowArray();

            if ($order === null) {
                throw new RuntimeException('Orden de Trabajo no encontrada.');
            }
            if ($order['status'] === 'closed') {
                throw new RuntimeException('La Orden de Trabajo ya se encuentra cerrada.');
            }

            $readiness = $this->readiness($workOrderId);
            if (! $readiness['ready']) {
                throw new RuntimeException(implode(' ', $readiness['blocking_reasons']));
            }

            $acceptance = $readiness['acceptance'];

            $db->table('work_orders')->where('id', $workOrderId)->update([
                'status' => 'closed',
                'closed_at' => $now,
                'closed_by_user_id' => session('auth_user_id') ?: null,
                'closure_notes' => $cleanNotes !== '' ? $cleanNotes : null,
                'modify_user' => $this->actor(),
                'modify_date' => $now,
            ]);

            if ($db->tableExists('mission_logs')) {
                $db->table('mission_logs')->insert([
                    'work_order_id' => $workOrderId,
                    'service_case_id' => (int) $order['service_case_id'],
                    'log_type' => 'closure',
                    'category' => 'operation',
                    'event_code' => 'work_order.closed',
                    'title' => 'Orden de Trabajo cerrada formalmente',
                    'description' => 'Cierre formal autorizado después de la aceptación del cliente por ' . $acceptance['receiver_name'] . '.' . ($cleanNotes !== '' ? ' ' . $cleanNotes : ''),
                    'visibility' => 'internal',
                    'occurred_at' => $now,
                    'actor_user_id' => session('auth_user_id') ?: null,
                    'metadata_json' => json_encode(['acceptance_id' => (int) $acceptance['id']], JSON_UNESCAPED_UNICODE),
                    'entry_user' => $this->actor(),
                    'entry_date' => $now,
                ]);
            }

            $db->table('service_case_events')->insert([
                'service_case_id' => (int) $order['service_case_id'],
                'event_code' => 'work_order.closed',
                'title' => 'Orden de Trabajo cerrada formalmente',
                'description' => 'La Orden de Trabajo ' . $order['code'] . ' fue cerrada formalmente y queda preparada para el proceso de facturación.',
                'entity_type' => 'work_order',
                'entity_id' => $workOrderId,
                'occurred_at' => $now,
                'entry_user' => $this->actor(),
                'entry_date' => $now,
            ]);

            (new ActivityService())->record(
                'work_order',
                $workOrderId,
                'work_order.closed',
                'Orden de Trabajo cerrada formalmente',
                $cleanNotes !== '' ? $cleanNotes : 'Cierre posterior a aceptación del cliente.'
            );

            $db->transCommit();
            (new ProcessEngineService())->evaluate((int) $order['service_case_id']);
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
