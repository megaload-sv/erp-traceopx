<?php

namespace App\Controllers;

use App\Models\CommercialRequestModel;
use App\Models\CustomerConversationModel;
use App\Services\ActivityService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

class CustomerConversationsController extends BaseController
{
    public function index(): string
    {
        $rows = (new CustomerConversationModel())->workspaceList();
        $now = time();
        foreach ($rows as &$row) {
            $due = $row['first_responded_at'] ? null : strtotime((string) $row['first_response_due_at']);
            $row['runtime_sla_status'] = $due === null ? 'fulfilled' : ($due < $now ? 'overdue' : (($due - $now) <= 900 ? 'warning' : 'on_time'));
        }
        unset($row);

        return view('customer_conversations/index', [
            'title' => 'Atención comercial',
            'conversations' => $rows,
            'metrics' => [
                'total' => count($rows),
                'new' => count(array_filter($rows, static fn ($r) => $r['attention_status'] === 'new')),
                'waiting' => count(array_filter($rows, static fn ($r) => $r['attention_status'] === 'waiting_customer')),
                'overdue' => count(array_filter($rows, static fn ($r) => $r['runtime_sla_status'] === 'overdue')),
            ],
        ]);
    }

    public function create(): string
    {
        $db = db_connect();
        return view('customer_conversations/form', [
            'title' => 'Nueva atención comercial',
            'customers' => $db->table('customers')->where('status', 1)->orderBy('business_name')->get()->getResultArray(),
            'contacts' => $db->table('customer_contacts')->where('status', 1)->orderBy('name')->get()->getResultArray(),
            'users' => $db->table('users')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'policies' => $db->table('commercial_sla_policies')->where('status', 1)->orderBy('name')->get()->getResultArray(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $db = db_connect();
        $policy = $db->table('commercial_sla_policies')->where('id', (int) $this->request->getPost('sla_policy_id'))->where('status', 1)->get()->getRowArray();
        if ($policy === null) {
            return redirect()->back()->withInput()->with('error', 'Seleccione una política de SLA válida.');
        }

        $startedAt = $this->request->getPost('started_at') ?: date('Y-m-d H:i:s');
        $startedTimestamp = strtotime((string) $startedAt);
        $model = new CustomerConversationModel();
        $id = $model->insert([
            'uuid' => $this->uuidV4(),
            'code' => $model->nextCode(),
            'primary_channel' => (string) $this->request->getPost('primary_channel'),
            'customer_id' => $this->nullableInt('customer_id'),
            'contact_id' => $this->nullableInt('contact_id'),
            'assigned_user_id' => $this->nullableInt('assigned_user_id'),
            'sla_policy_id' => (int) $policy['id'],
            'subject' => trim((string) $this->request->getPost('subject')),
            'summary' => $this->nullable('summary'),
            'attention_status' => 'new',
            'priority' => (string) ($this->request->getPost('priority') ?: 'normal'),
            'started_at' => date('Y-m-d H:i:s', $startedTimestamp),
            'first_response_due_at' => date('Y-m-d H:i:s', $startedTimestamp + ((int) $policy['first_response_minutes'] * 60)),
            'status' => 1,
        ], true);

        if ($id === false) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        $id = (int) $id;
        $this->addInteractionRecord($id, (string) $this->request->getPost('primary_channel'), 'inbound', 'message', trim((string) $this->request->getPost('initial_message')), $startedAt);
        $this->createTask($id, $this->nullableInt('assigned_user_id'), 'Responder atención comercial', 'first_response', date('Y-m-d H:i:s', $startedTimestamp + ((int) $policy['first_response_minutes'] * 60)));
        (new ActivityService())->record('customer_conversation', $id, 'attention.created', 'Atención comercial iniciada', 'Se inició una atención por ' . ucfirst((string) $this->request->getPost('primary_channel')) . '.');

        return redirect()->to(route_to('customer_conversations.show', $id))->with('success', 'Atención creada con SLA y tarea de primera respuesta.');
    }

    public function show(int $id): string
    {
        $conversation = (new CustomerConversationModel())->detail($id);
        if ($conversation === null) {
            throw new RuntimeException('Atención no encontrada.');
        }

        $db = db_connect();
        return view('customer_conversations/show', [
            'title' => $conversation['code'],
            'conversation' => $conversation,
            'interactions' => $db->table('customer_conversation_interactions')->where('conversation_id', $id)->where('status', 1)->orderBy('occurred_at', 'ASC')->get()->getResultArray(),
            'tasks' => $db->table('tasks')->where('related_type', 'customer_conversation')->where('related_id', $id)->orderBy('due_at', 'ASC')->get()->getResultArray(),
        ]);
    }

    public function addInteraction(int $id): RedirectResponse
    {
        $model = new CustomerConversationModel();
        $conversation = $model->find($id);
        if ($conversation === null) {
            throw new RuntimeException('Atención no encontrada.');
        }

        $direction = (string) $this->request->getPost('direction');
        $occurredAt = $this->request->getPost('occurred_at') ?: date('Y-m-d H:i:s');
        $this->addInteractionRecord($id, (string) $this->request->getPost('channel'), $direction, (string) $this->request->getPost('interaction_type'), trim((string) $this->request->getPost('body')), $occurredAt);

        $update = ['attention_status' => $direction === 'outbound' ? 'in_attention' : $conversation['attention_status']];
        if ($direction === 'outbound' && empty($conversation['first_responded_at'])) {
            $update['first_responded_at'] = date('Y-m-d H:i:s', strtotime((string) $occurredAt));
            db_connect()->table('tasks')->where('related_type', 'customer_conversation')->where('related_id', $id)->where('task_type', 'first_response')->where('status', 'pending')->update(['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);
        }
        $model->update($id, $update);

        return redirect()->to(route_to('customer_conversations.show', $id))->with('success', 'Interacción registrada.');
    }

    public function waitCustomer(int $id): RedirectResponse
    {
        $followUpAt = $this->request->getPost('next_follow_up_at');
        if (! $followUpAt) {
            return redirect()->back()->with('error', 'Indique la fecha del próximo seguimiento.');
        }
        $model = new CustomerConversationModel();
        $conversation = $model->find($id);
        if ($conversation === null) {
            throw new RuntimeException('Atención no encontrada.');
        }
        $model->update($id, ['attention_status' => 'waiting_customer', 'next_follow_up_at' => date('Y-m-d H:i:s', strtotime((string) $followUpAt))]);
        $this->createTask($id, $conversation['assigned_user_id'] ? (int) $conversation['assigned_user_id'] : null, 'Dar seguimiento al cliente', 'customer_follow_up', date('Y-m-d H:i:s', strtotime((string) $followUpAt)));
        return redirect()->to(route_to('customer_conversations.show', $id))->with('success', 'Atención en espera con seguimiento programado.');
    }

    public function markInformationComplete(int $id): RedirectResponse
    {
        $model = new CustomerConversationModel();
        $conversation = $model->find($id);
        if ($conversation === null) {
            throw new RuntimeException('Atención no encontrada.');
        }
        if (! empty($conversation['commercial_request_id'])) {
            return redirect()->to(route_to('customer_conversations.show', $id))->with('error', 'Esta atención ya fue convertida en solicitud comercial.');
        }
        $model->update($id, ['attention_status' => 'information_complete', 'qualified_at' => date('Y-m-d H:i:s')]);
        $this->createTask($id, $conversation['assigned_user_id'] ? (int) $conversation['assigned_user_id'] : null, 'Crear solicitud comercial', 'create_commercial_request', date('Y-m-d H:i:s', strtotime('+30 minutes')));
        return redirect()->to(route_to('customer_conversations.show', $id))->with('success', 'Información completa. Se creó la siguiente acción para formalizar la solicitud.');
    }

    public function convertToRequest(int $id): RedirectResponse
    {
        $db = db_connect();
        $db->transBegin();

        try {
            $conversationModel = new CustomerConversationModel();
            $conversation = $conversationModel->find($id);
            if ($conversation === null) {
                throw new RuntimeException('Atención no encontrada.');
            }
            if ($conversation['attention_status'] !== 'information_complete') {
                throw new RuntimeException('La información debe estar completa antes de crear la solicitud comercial.');
            }
            if (! empty($conversation['commercial_request_id'])) {
                throw new RuntimeException('Esta atención ya posee una solicitud comercial relacionada.');
            }

            $policy = null;
            if (! empty($conversation['sla_policy_id'])) {
                $policy = $db->table('commercial_sla_policies')->where('id', (int) $conversation['sla_policy_id'])->where('status', 1)->get()->getRowArray();
            }
            if ($policy === null) {
                $requestChannel = $this->requestChannel((string) $conversation['primary_channel']);
                $policy = $db->table('commercial_sla_policies')->where('channel', $requestChannel)->where('status', 1)->orderBy('id')->get()->getRowArray();
            }
            if ($policy === null) {
                throw new RuntimeException('No existe una política de SLA activa para formalizar esta solicitud.');
            }

            $requestModel = new CommercialRequestModel();
            $receivedAt = date('Y-m-d H:i:s');
            $requestId = $requestModel->insert([
                'uuid' => $this->uuidV4(),
                'code' => $requestModel->nextCode(),
                'channel' => $this->requestChannel((string) $conversation['primary_channel']),
                'source_detail' => 'Atención ' . $conversation['code'],
                'customer_id' => $conversation['customer_id'] ?: null,
                'contact_id' => $conversation['contact_id'] ?: null,
                'source_conversation_id' => $id,
                'sla_policy_id' => (int) $policy['id'],
                'assigned_user_id' => $conversation['assigned_user_id'] ?: null,
                'subject' => (string) $conversation['subject'],
                'description' => trim((string) ($conversation['summary'] ?: 'Solicitud formalizada desde la atención ' . $conversation['code'] . '.')),
                'requested_services' => trim((string) ($conversation['summary'] ?: 'Pendiente de detallar durante la preparación de la cotización.')),
                'priority' => (string) $conversation['priority'],
                'status' => 'ready_to_quote',
                'sla_status' => 'on_time',
                'escalation_level' => 0,
                'received_at' => $receivedAt,
                'first_response_due_at' => $receivedAt,
                'first_responded_at' => $receivedAt,
                'quotation_due_at' => date('Y-m-d H:i:s', strtotime($receivedAt) + ((int) $policy['quotation_delivery_minutes'] * 60)),
            ], true);

            if ($requestId === false) {
                throw new RuntimeException(implode(' ', $requestModel->errors()));
            }

            $requestId = (int) $requestId;
            $conversationModel->update($id, [
                'commercial_request_id' => $requestId,
                'attention_status' => 'converted',
                'closed_at' => date('Y-m-d H:i:s'),
            ]);

            $db->table('tasks')
                ->where('related_type', 'customer_conversation')
                ->where('related_id', $id)
                ->where('task_type', 'create_commercial_request')
                ->where('status', 'pending')
                ->update(['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);

            $this->createRequestTask(
                $requestId,
                $conversation['assigned_user_id'] ? (int) $conversation['assigned_user_id'] : null,
                date('Y-m-d H:i:s', strtotime($receivedAt) + ((int) $policy['quotation_delivery_minutes'] * 60))
            );

            (new ActivityService())->record('customer_conversation', $id, 'attention.converted', 'Atención convertida', 'La atención se formalizó como solicitud comercial.');
            (new ActivityService())->record('commercial_request', $requestId, 'commercial_request.created_from_attention', 'Solicitud creada desde atención', 'La solicitud conserva la trazabilidad de ' . $conversation['code'] . '.');

            $db->transCommit();
            return redirect()->to(route_to('customer_conversations.show', $id))->with('success', 'Solicitud comercial creada y tarea de preparación de cotización asignada.');
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Error convirtiendo atención {id}: {message}', ['id' => $id, 'message' => $e->getMessage()]);
            return redirect()->to(route_to('customer_conversations.show', $id))->with('error', $e->getMessage());
        }
    }

    private function addInteractionRecord(int $conversationId, string $channel, string $direction, string $type, string $body, string $occurredAt): void
    {
        db_connect()->table('customer_conversation_interactions')->insert([
            'conversation_id' => $conversationId,
            'channel' => $channel,
            'direction' => $direction,
            'interaction_type' => $type,
            'body' => $body,
            'actor_user_id' => session('auth_user_id') ?: null,
            'occurred_at' => date('Y-m-d H:i:s', strtotime($occurredAt)),
            'status' => 1,
            'entry_user' => (string) (session('auth_user_email') ?: 'system'),
            'entry_date' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createTask(int $conversationId, ?int $assignedUserId, string $title, string $type, string $dueAt): void
    {
        db_connect()->table('tasks')->insert([
            'uuid' => $this->uuidV4(), 'title' => $title,
            'description' => 'Siguiente acción generada por el motor de atención comercial.',
            'task_type' => $type, 'related_type' => 'customer_conversation', 'related_id' => $conversationId,
            'assigned_user_id' => $assignedUserId, 'priority' => 'high', 'status' => 'pending',
            'due_at' => $dueAt, 'is_automatic' => 1, 'escalation_level' => 0,
            'entry_user' => (string) (session('auth_user_email') ?: 'system'), 'entry_date' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createRequestTask(int $requestId, ?int $assignedUserId, string $dueAt): void
    {
        db_connect()->table('tasks')->insert([
            'uuid' => $this->uuidV4(),
            'title' => 'Preparar cotización',
            'description' => 'Preparar la cotización de la solicitud comercial dentro del SLA establecido.',
            'task_type' => 'prepare_quotation',
            'related_type' => 'commercial_request',
            'related_id' => $requestId,
            'assigned_user_id' => $assignedUserId,
            'priority' => 'high',
            'status' => 'pending',
            'due_at' => $dueAt,
            'is_automatic' => 1,
            'escalation_level' => 0,
            'entry_user' => (string) (session('auth_user_email') ?: 'system'),
            'entry_date' => date('Y-m-d H:i:s'),
        ]);
    }

    private function requestChannel(string $channel): string
    {
        return in_array($channel, ['whatsapp', 'email'], true) ? $channel : 'manual';
    }

    private function nullable(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : $value;
    }

    private function nullableInt(string $field): ?int
    {
        $value = trim((string) $this->request->getPost($field));
        return $value === '' ? null : (int) $value;
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16); $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
