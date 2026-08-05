<?php

namespace App\Models;

class CommercialItemModel extends BaseModel
{
    protected $table = 'commercial_items';
    protected $primaryKey = 'id';
    protected $allowedFields = ['uuid', 'code', 'item_type', 'name', 'long_description', 'default_unit_id', 'suggested_price', 'allows_price_override', 'status', 'entry_user', 'modify_user', 'delete_user'];
}
