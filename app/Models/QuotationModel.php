<?php

namespace App\Models;

class QuotationModel extends BaseModel
{
    protected $table = 'quotations';
    protected $primaryKey = 'id';
    protected $allowedFields = ['uuid', 'code', 'commercial_request_id', 'customer_id', 'assigned_user_id', 'payment_term_id', 'origin_type', 'subject', 'quotation_date', 'valid_until', 'validity_days', 'status', 'customer_notes', 'internal_notes', 'terms_and_conditions', 'show_tax_breakdown', 'subtotal', 'discount', 'adjustment', 'tax_amount', 'total', 'agent_name_snapshot', 'agent_email_snapshot', 'agent_phone_snapshot', 'entry_user', 'modify_user', 'delete_user'];

    public function nextCode(): string
    {
        $last = $this->select('id')->withDeleted()->orderBy('id', 'DESC')->first();

        return 'COT-' . date('Y') . '-' . str_pad((string) (((int) ($last['id'] ?? 0)) + 1), 6, '0', STR_PAD_LEFT);
    }
}
