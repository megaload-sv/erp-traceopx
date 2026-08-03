<?php

namespace App\Models;

class CustomerTaxpayerTypeModel extends BaseModel
{
    protected $table = 'customer_taxpayer_types';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'code', 'name', 'status', 'entry_user', 'modify_user', 'delete_user',
    ];

    public function activeOptions(): array
    {
        return $this->where('status', 1)->orderBy('name', 'ASC')->findAll();
    }
}
