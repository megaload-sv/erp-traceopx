<?php

namespace App\Controllers;

use App\Models\ActivityEventModel;
use App\Models\CommercialRequestModel;
use App\Services\ActivityService;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;

class CommercialRequestsController extends BaseController
{
    public function index(): string
    {
        $model = new CommercialRequestModel();
        $requests = $model->workspaceList();
        $now = time();

        foreach ($requests as &$request) {
            $due = strtotime($request['first_responded_at'] ? $request['quotation_due_at'] : $request['first_response_due_at']);
            $request['runtime_sla_status'] = $due < $now ? 'overdue' : (($due - $now) <= 3600 ? 'warning' : 'on_time');
        }
        unset($request);

        return view('commercial_requests/index', [
            'title' => 'Solicitudes comerciales',
            'requests' => $requests,
            'metrics' => [
                'total' => count($requests),
                'new' => count(array_filter($requests, static fn ($r) => $r['status'] === 'new')),
                'waiting' => count(array_filter($requests, static fn ($r) => $r['status'] === 'waiting_customer')),
                'overdue' => count(array_filter($requests, static fn ($r) => $r['runtime_sla_status'] === 'overdue')),
            ],
        ]);
    }

    public function show(int $id): string
    {
        $request = (new CommercialRequestModel())->detail($id);
        if ($request === null) {
            throw new RuntimeException('Solicitud comercial no encontrada.');
        }

        $db = db_connect();
        $tasks = $db->table('tasks')
            ->where('related_type', 'commercial_request')
            ->where('related_id', $id)
            ->orderBy('due_at', 'ASC')
            ->get()
            ->getResultArray();

        $quotation = $db->table('quotations')
            ->select('quotations.id, quotations.code, quotations.status, quotations.total, service_cases.id AS service_case_id, service_cases.code AS service_case_code')
            ->join('service_cases', 'service_cases.accepted_quotation_id = quotations.id AND service_cases.delete_date IS NULL', 'left')
            ->where('quotations.commercial_request_id', $id)
            ->where('quotations.delete_date', null)
            ->orderBy('quotations.id', 'DESC')
            ->get()
            ->getRowArray();

        return view('commercial_requests/show', [
            'title' => $request['code'],
            'request' => $request,
            'quotation' => $quotation,
            'tasks' => $tasks,
            'events' => (new ActivityEventModel())->forEntity('commercial_request', $id),
        ]);
    }

    public function create(): string
    {
        $db = db_connect();

        return view('commercial_requests/form', [
            'title' => 'Nueva solicitud comercial',
            'customers' => $db->table('customers')->where('status', 1)->orderBy('business_name')->get()->getResultArray(),
            'contacts' => $db->table('customer_contacts')->where('status', 1)->orderBy('name')->get()->getResultArray(),
            'users' => $db->table('users')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'policies' => $db->table('commercial_sla_policies')->where('status', 1)->orderBy('name')->get()->getResultArray(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $db = db_connect();
        $policy = $db->table('commercial_sla_policies')
            ->where('id', (int) $this->request->getPost('sla_policy_id'))
            ->where('status', 1)
            ->get()
            ->getRowArray();

        if ($policy === null) {
            return redirect()->back()->withInput()->with('error', 'La política de SLA seleccionada no es válida.');
        }

        $receivedAt = $this->request->getPost('received_at') ?: date('Y-m-d H:i:s');
        $receivedTimestamp = strtotime((string) $receivedAt);
        $model = new CommercialRequestModel();
        $requestId = $model->insert([
            'uuid' => $this->uuidV4(),
            'code' => $model->nextCode(),
            'channel' => (string) $this->request->getPost('channel'),
            'source_detail' => $this->nullable('source_detail'),
            'customer_id' => $this->nullableInt('customer_id'),
            'contact_id' => $this->nullableInt('contact_id'),
            'sla_policy_id' => (int) $policy['id'],
            'assigned_user_id' => $this->nullableInt('assigned_user_id'),
            'subject' => trim((string) $this->request->getPost('subject')),
            'description' => trim((string) $this->request->getPost('description')),
            'requested_services' => $this->nullable('requested_services'),
            'priority' => (string) ($this->request->getPost('priority') ?: 'normal'),
            'status' => 'new',
            'sla_status' => 'on_time',
            'escalation_level' => 0,
            'received_at' => date('Y-m-d H:i:s', $receivedTimestamp),
            'first_response_due_at' => date('Y-m-d H:i:s', $receivedTimestamp + ((int) $policy['first_response_minutes'] * 60)),
            'quotation_due_at' => date('Y-m-d H:i:s', $receivedTimestamp + ((int) $policy['quotation_delivery_minutes'] * 60)),
        ], true);

        if ($requestId === false) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        $requestId = (int) $requestId;
        $this->createAutomaticTask(
            $requestId,
            (int) ($this->request->getPost('assigned_user_id') ?: 0),
            'Responder solicitud comercial',
            'first_response',
            date('Y-m-d H:i:s', $receivedTimestamp + ((int) $policy['first_response_minutes'] * 60))
        );

        (new ActivityService())->record(
            'commercial_request',
            $requestId,
            'commercial_request.created',
            'Solicitud comercial recibida',
            'Se registró una nueva entrada por ' . ucfirst((string) $this->request->getPost('channel')) . '.'
        );

        return redirect()->to(route_to('commercial_requests.show', $requestId))->with('success', 'Solicitud comercial registrada con SLA y tarea automática.');
    }

    private function createAutomaticTask(int $requestId, int $assignedUserId, string $title, string $type, string $dueAt): void
    {
        db_connect()->table('tasks')->insert([
            'uuid' => $this->uuidV4(),
            'title' => $title,
            'description' => 'Tarea generada automáticamente por el motor de atención comercial.',
            'task_type' => $type,
            'related_type' => 'commercial_request',
            'related_id' => $requestId,
            'assigned_user_id' => $assignedUserId ?: null,
            'priority' => 'high',
            'status' => 'pending',
            'due_at' => $dueAt,
            'is_automatic' => 1,
            'escalation_level' => 0,
            'entry_user' => (string) (session('auth_user_email') ?: 'system'),
            'entry_date' => date('Y-m-d H:i:s'),
        ]);
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
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
