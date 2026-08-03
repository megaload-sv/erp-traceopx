<?php

namespace App\Models;

class CustomerModel extends BaseModel
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid', 'code', 'customer_type', 'customer_taxpayer_type_id', 'economic_activity_id',
        'lifecycle_stage', 'relationship_tier', 'assigned_sales_user', 'next_follow_up_date',
        'business_name', 'trade_name', 'tax_id', 'registration_number', 'email', 'phone',
        'website', 'notes', 'status', 'entry_user', 'modify_user', 'delete_user',
    ];

    protected $validationRules = [
        'business_name' => 'required|max_length[190]',
        'customer_type' => 'required|in_list[company,person]',
        'customer_taxpayer_type_id' => 'permit_empty|is_natural_no_zero',
        'economic_activity_id' => 'permit_empty|is_natural_no_zero',
        'lifecycle_stage' => 'required|in_list[potential,active,inactive]',
        'relationship_tier' => 'required|in_list[standard,preferential,strategic]',
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
                ->orLike('assigned_sales_user', $term)
                ->groupEnd();
        }

        return $this->orderBy('business_name', 'ASC')->findAll();
    }

    public function dashboardMetrics(): array
    {
        return [
            'total' => $this->countAllResults(false),
            'active' => (new self())->where('lifecycle_stage', 'active')->countAllResults(),
            'potential' => (new self())->where('lifecycle_stage', 'potential')->countAllResults(),
            'inactive' => (new self())->where('lifecycle_stage', 'inactive')->countAllResults(),
            'strategic' => (new self())->where('relationship_tier', 'strategic')->countAllResults(),
        ];
    }

    public function nextCode(): string
    {
        $last = $this->select('id')->withDeleted()->orderBy('id', 'DESC')->first();
        $next = ((int) ($last['id'] ?? 0)) + 1;

        return 'CLI-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
