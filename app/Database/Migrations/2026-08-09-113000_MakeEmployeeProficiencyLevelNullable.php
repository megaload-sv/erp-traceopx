<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeEmployeeProficiencyLevelNullable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('employee_skill_assignments', [
            'proficiency_level' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'default' => null,
            ],
        ]);
    }

    public function down()
    {
        // Normaliza los NULL antes de restaurar la restricción anterior.
        $this->db->table('employee_skill_assignments')
            ->where('proficiency_level', null)
            ->update(['proficiency_level' => 'qualified']);

        $this->forge->modifyColumn('employee_skill_assignments', [
            'proficiency_level' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'default' => 'qualified',
            ],
        ]);
    }
}
