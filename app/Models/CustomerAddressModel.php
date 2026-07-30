<?php

namespace App\Models;

class CustomerAddressModel extends BaseModel
{
    protected $table = 'customer_addresses';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'customer_id', 'address_type', 'address_line', 'municipality',
        'department', 'country', 'is_primary', 'status',
        'entry_user', 'modify_user', 'delete_user',
    ];

    protected $validationRules = [
        'customer_id' => 'required|is_natural_no_zero',
        'address_type' => 'required|in_list[fiscal,operational,other]',
        'address_line' => 'required|max_length[255]',
    ];
}
