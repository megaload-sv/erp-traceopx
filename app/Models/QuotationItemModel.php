<?php

namespace App\Models;

class QuotationItemModel extends BaseModel
{
    protected $table = 'quotation_items';
    protected $primaryKey = 'id';
    protected $allowedFields = ['quotation_id', 'commercial_item_id', 'source_type', 'description', 'long_description', 'unit_id', 'quantity', 'unit_price', 'line_total', 'sort_order', 'entry_user', 'modify_user', 'delete_user'];
}
