<?php

namespace App\Models;

class CustomerContactModel extends BaseModel
{
    protected $table = 'customer_contacts';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'customer_id', 'name', 'position', 'contact_role', 'email', 'phone',
        'is_primary', 'status', 'entry_user', 'modify_user', 'delete_user',
    ];

    protected $validationRules = [
        'customer_id' => 'required|is_natural_no_zero',
        'name' => 'required|max_length[190]',
        'contact_role' => 'required|in_list[commercial,technical,billing,other]',
        'email' => 'permit_empty|valid_email|max_length[190]',
        'status' => 'required|in_list[0,1]',
    ];

    public function forCustomer(int $customerId): array
    {
        return $this->where('customer_id', $customerId)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    public function makePrimary(int $customerId, int $contactId): bool
    {
        $this->builder()->where('customer_id', $customerId)->update(['is_primary' => 0]);

        return $this->update($contactId, ['is_primary' => 1]);
    }
}
