<?php

namespace App\Models;

class BillingCaseModel extends BaseModel
{
    protected $table = 'billing_cases';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid','code','origin_type','service_case_id','work_order_id','quotation_id','customer_id','payment_term_id',
        'document_type','status','currency_code','quotation_total_snapshot','invoiceable_amount','paid_amount',
        'balance_amount','payment_term_code_snapshot','payment_term_name_snapshot','notes','prepared_by_user_id',
        'prepared_at','entry_user','modify_user','delete_user'
    ];

    public function nextCode(): string
    {
        $last = $this->select('id')->withDeleted()->orderBy('id','DESC')->first();
        return 'FAC-' . date('Y') . '-' . str_pad((string)(((int)($last['id'] ?? 0)) + 1), 6, '0', STR_PAD_LEFT);
    }
}
