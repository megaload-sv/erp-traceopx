<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorkOrderIssuanceFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('work_orders', [
            'issued_by_user_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'issued_at',
            ],
            'issued_to_employee_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'issued_by_user_id',
            ],
            'issuance_notes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'issued_to_employee_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('work_orders', ['issued_by_user_id', 'issued_to_employee_id', 'issuance_notes']);
    }
}
