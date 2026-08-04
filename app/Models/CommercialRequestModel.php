<?php

namespace App\Models;

class CommercialRequestModel extends BaseModel
{
    protected $table = 'commercial_requests';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid','code','channel','source_detail','customer_id','contact_id','source_conversation_id','sla_policy_id','assigned_user_id',
        'subject','description','requested_services','priority','status','sla_status','escalation_level',
        'received_at','first_response_due_at','first_responded_at','quotation_due_at','next_follow_up_at',
        'waiting_reason','closed_at','entry_user','modify_user','delete_user',
    ];

    protected $validationRules = [
        'channel' => 'required|in_list[whatsapp,email,manual]',
        'sla_policy_id' => 'required|is_natural_no_zero',
        'subject' => 'required|max_length[190]',
        'description' => 'required',
        'priority' => 'required|in_list[low,normal,high,urgent]',
        'status' => 'required|in_list[new,assigned,in_progress,waiting_customer,ready_to_quote,quotation_preparation,quotation_sent,converted,discarded]',
    ];

    public function workspaceList(): array
    {
        return $this->select('commercial_requests.*, customers.business_name, users.name AS assigned_user_name')
            ->join('customers', 'customers.id = commercial_requests.customer_id', 'left')
            ->join('users', 'users.id = commercial_requests.assigned_user_id', 'left')
            ->orderBy('commercial_requests.received_at', 'DESC')
            ->findAll();
    }

    public function nextCode(): string
    {
        $last = $this->select('id')->withDeleted()->orderBy('id', 'DESC')->first();
        return 'SOL-' . date('Y') . '-' . str_pad((string) (((int) ($last['id'] ?? 0)) + 1), 6, '0', STR_PAD_LEFT);
    }
}
