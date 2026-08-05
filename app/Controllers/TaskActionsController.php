<?php

namespace App\Controllers;

use App\Services\ActivityService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;

class TaskActionsController extends BaseController
{
    public function start(int $id): RedirectResponse
    {
        $task = $this->task($id);
        if ($task['status'] !== 'pending') {
            return $this->backToTask($task, 'La tarea solo puede iniciarse cuando está pendiente.', true);
        }

        db_connect()->table('tasks')->where('id', $id)->update([
            'status' => 'in_progress',
            'started_at' => date('Y-m-d H:i:s'),
            'modify_user' => $this->actor(),
            'modify_date' => date('Y-m-d H:i:s'),
        ]);

        $this->record($task, 'task.started', 'Tarea iniciada', 'La próxima acción fue tomada por el colaborador.');

        return $this->backToTask($task, 'Tarea iniciada correctamente.');
    }

    public function complete(int $id): RedirectResponse
    {
        $task = $this->task($id);
        if (! in_array($task['status'], ['pending', 'in_progress'], true)) {
            return $this->backToTask($task, 'La tarea ya no está activa.', true);
        }

        $note = trim((string) $this->request->getPost('completion_note'));
        if ($note === '') {
            return $this->backToTask($task, 'Registre una nota breve de finalización.', true);
        }

        db_connect()->table('tasks')->where('id', $id)->update([
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'completion_note' => $note,
            'modify_user' => $this->actor(),
            'modify_date' => date('Y-m-d H:i:s'),
        ]);

        $this->record($task, 'task.completed', 'Tarea completada', $note);

        return $this->backToTask($task, 'Tarea completada y registrada en la trazabilidad.');
    }

    public function reschedule(int $id): RedirectResponse
    {
        $task = $this->task($id);
        if (! in_array($task['status'], ['pending', 'in_progress'], true)) {
            return $this->backToTask($task, 'La tarea ya no está activa.', true);
        }

        $dueAt = trim((string) $this->request->getPost('due_at'));
        $reason = trim((string) $this->request->getPost('reschedule_reason'));
        $timestamp = strtotime($dueAt);

        if ($timestamp === false || $timestamp <= time()) {
            return $this->backToTask($task, 'La nueva fecha debe ser válida y posterior al momento actual.', true);
        }
        if ($reason === '') {
            return $this->backToTask($task, 'Indique el motivo de la reprogramación.', true);
        }

        db_connect()->table('tasks')->where('id', $id)->update([
            'due_at' => date('Y-m-d H:i:s', $timestamp),
            'reschedule_reason' => $reason,
            'modify_user' => $this->actor(),
            'modify_date' => date('Y-m-d H:i:s'),
        ]);

        $this->record($task, 'task.rescheduled', 'Tarea reprogramada', $reason . ' Nueva fecha: ' . date('d/m/Y H:i', $timestamp) . '.');

        return $this->backToTask($task, 'Tarea reprogramada correctamente.');
    }

    private function task(int $id): array
    {
        $task = db_connect()->table('tasks')->where('id', $id)->get()->getRowArray();
        if ($task === null) {
            throw new RuntimeException('Tarea no encontrada.');
        }

        return $task;
    }

    private function record(array $task, string $event, string $title, string $description): void
    {
        (new ActivityService())->record(
            (string) $task['related_type'],
            (int) $task['related_id'],
            $event,
            $title,
            $description
        );
    }

    private function backToTask(array $task, string $message, bool $error = false): RedirectResponse
    {
        $redirect = match ((string) $task['related_type']) {
            'customer_conversation' => route_to('customer_conversations.show', (int) $task['related_id']),
            'commercial_request' => route_to('commercial_requests.show', (int) $task['related_id']),
            default => route_to('dashboard'),
        };

        return redirect()->to($redirect)->with($error ? 'error' : 'success', $message);
    }

    private function actor(): string
    {
        return (string) (session('auth_user_email') ?: session('auth_user_name') ?: 'system');
    }
}
