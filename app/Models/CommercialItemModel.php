<?php

namespace App\Models;

class CommercialItemModel extends BaseModel
{
    protected $table = 'commercial_items';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uuid', 'code', 'item_type', 'item_group_id', 'name', 'long_description',
        'default_unit_id', 'suggested_price', 'allows_price_override',
        'allows_unit_override', 'display_order', 'source_reference',
        'normalization_notes', 'status', 'entry_user', 'modify_user', 'delete_user',
    ];
}
