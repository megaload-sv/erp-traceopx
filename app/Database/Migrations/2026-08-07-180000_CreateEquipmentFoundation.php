<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEquipmentFoundation extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'code' => ['type'=>'VARCHAR','constraint'=>30],
            'name' => ['type'=>'VARCHAR','constraint'=>120],
            'description' => ['type'=>'TEXT','null'=>true],
            'display_order' => ['type'=>'INT','default'=>0],
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
        $this->forge->createTable('equipment_categories', true);

        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'code' => ['type'=>'VARCHAR','constraint'=>30],
            'name' => ['type'=>'VARCHAR','constraint'=>120],
            'description' => ['type'=>'TEXT','null'=>true],
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
        $this->forge->createTable('resource_roles', true);

        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'uuid' => ['type'=>'CHAR','constraint'=>36],
            'code' => ['type'=>'VARCHAR','constraint'=>40],
            'category_id' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'name' => ['type'=>'VARCHAR','constraint'=>190],
            'brand' => ['type'=>'VARCHAR','constraint'=>120,'null'=>true],
            'model' => ['type'=>'VARCHAR','constraint'=>120,'null'=>true],
            'serial_number' => ['type'=>'VARCHAR','constraint'=>120,'null'=>true],
            'plate_number' => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'year' => ['type'=>'SMALLINT','unsigned'=>true,'null'=>true],
            'operational_status' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'available'],
            'maintenance_status' => ['type'=>'VARCHAR','constraint'=>30,'default'=>'ok'],
            'meter_type' => ['type'=>'VARCHAR','constraint'=>20,'null'=>true],
            'current_meter' => ['type'=>'DECIMAL','constraint'=>'12,2','null'=>true],
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
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('category_id');
        $this->forge->addForeignKey('category_id', 'equipment_categories', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('equipment', true);

        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'equipment_id' => ['type'=>'INT','unsigned'=>true],
            'resource_role_id' => ['type'=>'INT','unsigned'=>true],
            'requirement_type' => ['type'=>'VARCHAR','constraint'=>20,'default'=>'required'],
            'min_quantity' => ['type'=>'INT','unsigned'=>true,'default'=>1],
            'max_quantity' => ['type'=>'INT','unsigned'=>true,'default'=>1],
            'notes' => ['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'status' => ['type'=>'TINYINT','constraint'=>1,'default'=>1],
            'entry_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'entry_date' => ['type'=>'DATETIME','null'=>true],
            'modify_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'modify_date' => ['type'=>'DATETIME','null'=>true],
            'delete_user' => ['type'=>'VARCHAR','constraint'=>190,'null'=>true],
            'delete_date' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['equipment_id','resource_role_id']);
        $this->forge->addForeignKey('equipment_id', 'equipment', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('resource_role_id', 'resource_roles', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('equipment_role_requirements', true);
    }

    public function down()
    {
        $this->forge->dropTable('equipment_role_requirements', true);
        $this->forge->dropTable('equipment', true);
        $this->forge->dropTable('resource_roles', true);
        $this->forge->dropTable('equipment_categories', true);
    }
}
