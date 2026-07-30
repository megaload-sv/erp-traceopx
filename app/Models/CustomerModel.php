<?php

namespace App\Models;

class CustomerModel extends BaseModel
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid', 'code', 'customer_type', 'business_name', 'trade_name',
        'tax_id', 'registration_number', 'email', 'phone', 'website',
        'notes', 'status', 'entry_user', 'modify_user', 'delete_user',
    ];

    protected $validationRules = [
        'business_name' => 'required|max_length[190]',
        'customer_type' => 'required|in_list[company,person]',
        'email' => 'permit_empty|valid_email|max_length[190]',
        'status' => 'required|in_list[0,1]',
    ];

    public function searchList(?string $term = null): array
    {
        if ($term !== null && trim($term) !== '') {
            $term = trim($term);
            $this->groupStart()
                ->like('code', $term)
                ->orLike('business_name', $term)
                ->orLike('trade_name', $term)
                ->orLike('tax_id', $term)
                ->orLike('email', $term)
                ->orLike('phone', $term)
                ->groupEnd();
        }

        return $this->orderBy('business_name', 'ASC')->findAll();
    }

    public function nextCode(): string
    {
        $last = $this->select('id')->withDeleted()->orderBy('id', 'DESC')->first();
        $next = ((int) ($last['id'] ?? 0)) + 1;

        return 'CLI-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
