<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class WorkOrderChecklistService
{
    public function ensureForWorkOrder(int $workOrderId): ?array
    {
        $db = db_connect();
        if (! $db->tableExists('work_order_checklists')) {
            return null;
        }

        $order = $db->table('work_orders')->where('id', $workOrderId)->where('delete_date', null)->get()->getRowArray();
        if ($order === null) {
            throw new RuntimeException('Orden de Trabajo no encontrada.');
        }

        $existing = $db->table('work_order_checklists')->where('work_order_id', $workOrderId)->get()->getRowArray();
        if ($existing !== null) {
            return $this->detail((int) $existing['id']);
        }

        $template = $db->table('work_order_checklist_templates')
            ->where('status', 1)
            ->where('service_key', null)
            ->orderBy('id', 'ASC')
            ->get()->getRowArray();

        if ($template === null) {
            return null;
        }

        $items = $db->table('work_order_checklist_template_items')
            ->where('template_id', (int) $template['id'])
            ->where('status', 1)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();

        $now = date('Y-m-d H:i:s');
        $db->transBegin();
        try {
            $db->table('work_order_checklists')->insert([
                'work_order_id' => $workOrderId,
                'service_case_id' => (int) $order['service_case_id'],
                'template_id' => (int) $template['id'],
                'template_code_snapshot' => $template['code'],
                'template_name_snapshot' => $template['name'],
                'status' => 'pending',
                'entry_date' => $now,
            ]);
            $checklistId = (int) $db->insertID();
            if ($checklistId <= 0) {
                throw new RuntimeException('No fue posible crear el checklist operativo.');
            }

            foreach ($items as $item) {
                $db->table('work_order_checklist_items')->insert([
                    'checklist_id' => $checklistId,
                    'template_item_id' => (int) $item['id'],
                    'item_code_snapshot' => $item['code'],
                    'item_label_snapshot' => $item['label'],
                    'item_description_snapshot' => $item['description'],
                    'stage_snapshot' => $item['stage'],
                    'is_required' => (int) $item['is_required'],
                    'sort_order' => (int) $item['sort_order'],
                ]);
            }

            $db->transCommit();
            return $this->detail($checklistId);
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function answer(int $workOrderId, int $itemId, string $response, ?string $notes = null): array
    {
        $db = db_connect();
        $order = $db->table('work_orders')->where('id', $workOrderId)->where('delete_date', null)->get()->getRowArray();
        if ($order === null) {
            throw new RuntimeException('Orden de Trabajo no encontrada.');
        }
        if (! in_array($order['status'], ['issued', 'in_progress'], true)) {
            throw new RuntimeException('El checklist solo puede actualizarse mientras la Orden de Trabajo está emitida o en ejecución.');
        }

        $checklist = $this->ensureForWorkOrder($workOrderId);
        if ($checklist === null) {
            throw new RuntimeException('No existe un checklist operativo configurado.');
        }

        $item = $db->table('work_order_checklist_items')
            ->where('id', $itemId)
            ->where('checklist_id', (int) $checklist['id'])
            ->get()->getRowArray();
        if ($item === null) {
            throw new RuntimeException('Ítem de checklist no encontrado.');
        }

        if (! in_array($response, ['yes', 'no', 'na'], true)) {
            throw new RuntimeException('Seleccione una respuesta válida para el checklist.');
        }
        if ((int) $item['is_required'] === 1 && $response === 'na') {
            throw new RuntimeException('Un requisito obligatorio no puede marcarse como No aplica.');
        }

        $cleanNotes = trim((string) $notes);
        if ($response === 'no' && $cleanNotes === '') {
            throw new RuntimeException('Cuando un requisito no se cumple debe registrar una observación.');
        }

        $now = date('Y-m-d H:i:s');
        $actor = $this->actor();
        $db->transBegin();
        try {
            $db->table('work_order_checklist_items')->where('id', $itemId)->update([
                'response' => $response,
                'notes' => $cleanNotes !== '' ? $cleanNotes : null,
                'answered_at' => $now,
                'answered_by' => $actor,
            ]);

            $summary = $this->summary((int) $checklist['id']);
            $newStatus = $summary['required_pending'] === 0 && $summary['required_failed'] === 0 ? 'completed' : 'in_progress';
            $db->table('work_order_checklists')->where('id', (int) $checklist['id'])->update([
                'status' => $newStatus,
                'completed_at' => $newStatus === 'completed' ? $now : null,
                'completed_by' => $newStatus === 'completed' ? $actor : null,
                'modify_date' => $now,
            ]);

            $label = $item['item_label_snapshot'];
            $responseLabel = ['yes' => 'Cumple', 'no' => 'No cumple', 'na' => 'No aplica'][$response];
            $description = $label . ': ' . $responseLabel . ($cleanNotes !== '' ? '. ' . $cleanNotes : '');

            $db->table('mission_logs')->insert([
                'work_order_id' => $workOrderId,
                'service_case_id' => (int) $order['service_case_id'],
                'log_type' => 'checklist',
                'category' => 'quality',
                'event_code' => 'work_order.checklist_answered',
                'title' => 'Checklist operativo actualizado',
                'description' => $description,
                'visibility' => 'internal',
                'occurred_at' => $now,
                'actor_user_id' => session('auth_user_id') ?: null,
                'metadata_json' => json_encode(['checklist_item_id' => $itemId, 'response' => $response], JSON_UNESCAPED_UNICODE),
                'entry_user' => $actor,
                'entry_date' => $now,
            ]);

            (new ActivityService())->record('work_order', $workOrderId, 'work_order.checklist_answered', 'Checklist operativo actualizado', $description);
            $db->transCommit();

            (new ProcessEngineService())->evaluate((int) $order['service_case_id']);
            return $this->ensureForWorkOrder($workOrderId) ?? [];
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function detail(int $checklistId): array
    {
        $db = db_connect();
        $checklist = $db->table('work_order_checklists')->where('id', $checklistId)->get()->getRowArray();
        if ($checklist === null) {
            throw new RuntimeException('Checklist operativo no encontrado.');
        }
        $checklist['items'] = $db->table('work_order_checklist_items')
            ->where('checklist_id', $checklistId)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();
        $checklist['summary'] = $this->summary($checklistId);
        return $checklist;
    }

    private function summary(int $checklistId): array
    {
        $rows = db_connect()->table('work_order_checklist_items')->where('checklist_id', $checklistId)->get()->getResultArray();
        $total = count($rows);
        $answered = count(array_filter($rows, static fn(array $row): bool => $row['response'] !== null));
        $required = array_values(array_filter($rows, static fn(array $row): bool => (int) $row['is_required'] === 1));
        $requiredPending = count(array_filter($required, static fn(array $row): bool => $row['response'] === null));
        $requiredFailed = count(array_filter($required, static fn(array $row): bool => $row['response'] === 'no'));
        return [
            'total' => $total,
            'answered' => $answered,
            'percent' => $total > 0 ? (int) round(($answered / $total) * 100) : 100,
            'required_pending' => $requiredPending,
            'required_failed' => $requiredFailed,
            'ready' => $requiredPending === 0 && $requiredFailed === 0,
        ];
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
