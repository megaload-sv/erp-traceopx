<?php

namespace App\Models;

class PaymentTermModel extends BaseModel
{
    protected $table = 'payment_terms';
    protected $primaryKey = 'id';
    protected $allowedFields = ['code', 'name', 'description', 'term_type', 'requires_advance', 'minimum_advance_percentage', 'coordination_release_rule', 'status', 'entry_user', 'modify_user', 'delete_user'];
}
