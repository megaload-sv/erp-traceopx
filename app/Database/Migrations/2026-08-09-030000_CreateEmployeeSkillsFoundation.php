<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeeSkillsFoundation extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'uuid' => ['type'=>'CHAR','constraint'=>36],
            'user_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'employee_code' => ['type'=>'VARCHAR','constraint'=>30],
            'name' => ['type'=>'VARCHAR','constraint'=>190],
            'email' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'phone' => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'employment_status' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'active'],
            'availability_status' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'available'],
            'notes' => ['type'=>'TEXT','null'=>true],
            'status' => ['type'=>'TINYINT','constraint'=>1,'default'=>1],
            'entry_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
            'modify_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'modify_date' => ['type'=>'DATETIME','null'=>true],
            'delete_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'delete_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('employee_code');
        $this->forge->addKey('user_id');
        $this->forge->createTable('employees', true);

        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'code' => ['type'=>'VARCHAR','constraint'=>40],
            'name' => ['type'=>'VARCHAR','constraint'=>150],
            'description' => ['type'=>'TEXT','null'=>true],
            'requires_certification' => ['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'status' => ['type'=>'TINYINT','constraint'=>1,'default'=>1],
            'entry_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
            'modify_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'modify_date' => ['type'=>'DATETIME','null'=>true],
            'delete_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'delete_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('employee_skills', true);

        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'employee_id' => ['type'=>'INT','unsigned'=>true],
            'skill_id' => ['type'=>'INT','unsigned'=>true],
            'proficiency_level' => ['type'=>'VARCHAR','constraint'=>20,'default'=>'qualified'],
            'certification_number' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'valid_from' => ['type'=>'DATE','null'=>true],
            'valid_until' => ['type'=>'DATE','null'=>true],
            'status' => ['type'=>'TINYINT','constraint'=>1,'default'=>1],
            'notes' => ['type'=>'TEXT','null'=>true],
            'entry_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
            'modify_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'modify_date' => ['type'=>'DATETIME','null'=>true],
            'delete_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'delete_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['employee_id','skill_id']);
        $this->forge->addKey('employee_id');
        $this->forge->addKey('skill_id');
        $this->forge->createTable('employee_skill_assignments', true);
    }

    public function down()
    {
        $this->forge->dropTable('employee_skill_assignments', true);
        $this->forge->dropTable('employee_skills', true);
        $this->forge->dropTable('employees', true);
    }
}
