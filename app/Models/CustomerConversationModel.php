<?php

namespace App\Models;

class CustomerConversationModel extends BaseModel
{
    protected $table = 'customer_conversations';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid', 'code', 'primary_channel', 'customer_id', 'contact_id', 'assigned_user_id',
        'subject', 'summary', 'attention_status', 'priority', 'started_at',
        'first_response_due_at', 'first_responded_at', 'next_follow_up_at',
        'qualified_at', 'closed_at', 'commercial_request_id', 'status',
        'entry_user', 'modify_user', 'delete_user',
    ];

    protected $validationRules = [
        'code' => 'required|max_length[24]',
        'primary_channel' => 'required|in_list[whatsapp,email,manual,phone,visit]',
        'subject' => 'required|max_length[190]',
        'attention_status' => 'required|in_list[new,in_attention,waiting_customer,information_complete,converted,discarded]',
        'priority' => 'required|in_list[low,normal,high,urgent]',
        'started_at' => 'required|valid_date[Y-m-d H:i:s]',
    ];

    public function nextCode(): string
    {
        $year = date('Y');
        $prefix = "ATN-{$year}-";
        $last = $this->select('code')->like('code', $prefix, 'after')->orderBy('id', 'DESC')->first();
        $sequence = $last ? ((int) substr($last['code'], -6)) + 1 : 1;
        return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    public function workspaceList(): array
    {
        return $this->select('customer_conversations.*, customers.business_name, customer_contacts.name AS contact_name, users.name AS assigned_user_name')
            ->join('customers', 'customers.id = customer_conversations.customer_id', 'left')
            ->join('customer_contacts', 'customer_contacts.id = customer_conversations.contact_id', 'left')
            ->join('users', 'users.id = customer_conversations.assigned_user_id', 'left')
            ->orderBy('customer_conversations.started_at', 'DESC')
            ->findAll();
    }

    public function detail(int $id): ?array
    {
        $row = $this->select('customer_conversations.*, customers.business_name, customer_contacts.name AS contact_name, users.name AS assigned_user_name')
            ->join('customers', 'customers.id = customer_conversations.customer_id', 'left')
            ->join('customer_contacts', 'customer_contacts.id = customer_conversations.contact_id', 'left')
            ->join('users', 'users.id = customer_conversations.assigned_user_id', 'left')
            ->where('customer_conversations.id', $id)
            ->first();
        return is_array($row) ? $row : null;
    }
}
