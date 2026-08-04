<?php

namespace App\Models;

class CustomerAddressModel extends BaseModel
{
    protected $table = 'customer_addresses';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'customer_id', 'address_type', 'label', 'address_line',
        'country_id', 'department_id', 'municipality_id', 'district_id',
        'foreign_state', 'foreign_city', 'is_primary', 'status',
        'entry_user', 'modify_user', 'delete_user',
    ];

    protected $validationRules = [
        'customer_id' => 'required|is_natural_no_zero',
        'address_type' => 'required|in_list[fiscal,operational,other]',
        'label' => 'permit_empty|max_length[120]',
        'address_line' => 'required|max_length[255]',
        'country_id' => 'required|is_natural_no_zero',
        'status' => 'required|in_list[0,1]',
    ];

    public function forCustomer(int $customerId): array
    {
        return $this->select('customer_addresses.*, '
                . 'c.code AS country_code, c.name AS country_name, '
                . 'd.code AS department_code, d.name AS department_name, '
                . 'm.code AS municipality_code, m.name AS municipality_name, '
                . 'di.code AS district_code, di.name AS district_name')
            ->join('mh_countries c', 'c.id = customer_addresses.country_id', 'left')
            ->join('mh_departments d', 'd.id = customer_addresses.department_id', 'left')
            ->join('mh_municipalities m', 'm.id = customer_addresses.municipality_id', 'left')
            ->join('mh_districts di', 'di.id = customer_addresses.district_id', 'left')
            ->where('customer_addresses.customer_id', $customerId)
            ->orderBy('customer_addresses.is_primary', 'DESC')
            ->orderBy('customer_addresses.label', 'ASC')
            ->findAll();
    }

    public function customerHasAddresses(int $customerId): bool
    {
        return $this->where('customer_id', $customerId)->countAllResults() > 0;
    }

    public function makePrimary(int $customerId, int $addressId): bool
    {
        $address = $this->where('customer_id', $customerId)->find($addressId);
        if ($address === null || (int) $address['status'] !== 1) {
            return false;
        }

        $db = db_connect();
        $db->transStart();
        $this->where('customer_id', $customerId)->set(['is_primary' => 0])->update();
        $updated = $this->update($addressId, ['is_primary' => 1]);
        $db->transComplete();

        return $db->transStatus() && $updated;
    }
}
