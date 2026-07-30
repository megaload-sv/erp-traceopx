<?php

namespace App\Models;

class CustomerContactModel extends BaseModel
{
    protected $table = 'customer_contacts';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'customer_id', 'name', 'position', 'email', 'phone', 'is_primary',
        'status', 'entry_user', 'modify_user', 'delete_user',
    ];

    protected $validationRules = [
        'customer_id' => 'required|is_natural_no_zero',
        'name' => 'required|max_length[190]',
        'email' => 'permit_empty|valid_email|max_length[190]',
    ];
}
