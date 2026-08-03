<?php

namespace App\Models;

class CustomerAddressModel extends BaseModel
{
    protected $table = 'customer_addresses';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'customer_id', 'address_type', 'label', 'address_line', 'municipality',
        'department', 'country', 'is_primary', 'status',
        'entry_user', 'modify_user', 'delete_user',
    ];

    protected $validationRules = [
        'customer_id' => 'required|is_natural_no_zero',
        'address_type' => 'required|in_list[fiscal,operational,other]',
        'label' => 'permit_empty|max_length[120]',
        'address_line' => 'required|max_length[255]',
        'status' => 'required|in_list[0,1]',
    ];

    public function forCustomer(int $customerId): array
    {
        return $this->where('customer_id', $customerId)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('label', 'ASC')
            ->findAll();
    }

    public function customerHasAddresses(int $customerId): bool
    {
        return $this->where('customer_id', $customerId)->countAllResults() > 0;
    }

    public function makePrimary(int $customerId, int $addressId): bool
    {
        $db = db_connect();
        $db->transStart();

        $this->where('customer_id', $customerId)->set(['is_primary' => 0])->update();
        $updated = $this->update($addressId, ['is_primary' => 1, 'status' => 1]);

        $db->transComplete();

        return $db->transStatus() && $updated;
    }
}
