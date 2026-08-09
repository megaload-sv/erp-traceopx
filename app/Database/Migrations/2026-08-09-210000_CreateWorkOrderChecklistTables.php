<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkOrderChecklistTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'name' => ['type' => 'VARCHAR', 'constraint' => 160],
            'service_key' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'entry_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_user' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('work_order_checklist_templates', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'template_id' => ['type' => 'INT', 'unsigned' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'label' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'stage' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'execution'],
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'requires_note_on_no' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('template_id');
        $this->forge->addUniqueKey(['template_id', 'code']);
        $this->forge->addForeignKey('template_id', 'work_order_checklist_templates', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('work_order_checklist_template_items', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'work_order_id' => ['type' => 'INT', 'unsigned' => true],
            'service_case_id' => ['type' => 'INT', 'unsigned' => true],
            'template_id' => ['type' => 'INT', 'unsigned' => true],
            'template_code_snapshot' => ['type' => 'VARCHAR', 'constraint' => 60],
            'template_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 160],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_by' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entry_date' => ['type' => 'DATETIME', 'null' => true],
            'modify_date' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('work_order_id');
        $this->forge->addUniqueKey('work_order_id');
        $this->forge->addForeignKey('work_order_id', 'work_orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('service_case_id', 'service_cases', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('work_order_checklists', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'checklist_id' => ['type' => 'INT', 'unsigned' => true],
            'template_item_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'item_code_snapshot' => ['type' => 'VARCHAR', 'constraint' => 60],
            'item_label_snapshot' => ['type' => 'VARCHAR', 'constraint' => 255],
            'item_description_snapshot' => ['type' => 'TEXT', 'null' => true],
            'stage_snapshot' => ['type' => 'VARCHAR', 'constraint' => 30],
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'response' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'answered_at' => ['type' => 'DATETIME', 'null' => true],
            'answered_by' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('checklist_id');
        $this->forge->addUniqueKey(['checklist_id', 'item_code_snapshot']);
        $this->forge->addForeignKey('checklist_id', 'work_order_checklists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('work_order_checklist_items', true);

        $now = date('Y-m-d H:i:s');
        $this->db->table('work_order_checklist_templates')->insert([
            'code' => 'GENERAL_OPERATION',
            'name' => 'Checklist operativo general',
            'service_key' => null,
            'description' => 'Verificaciones base aplicables a una misión operativa cuando no existe un checklist específico por tipo de servicio.',
            'status' => 1,
            'entry_user' => 'migration',
            'entry_date' => $now,
        ]);
        $templateId = (int) $this->db->insertID();
        $items = [
            ['SITE_SAFE', 'Área de trabajo verificada y segura', 'Confirmar condiciones básicas del sitio antes o durante la maniobra.', 1],
            ['EQUIPMENT_READY', 'Maquinaria y equipo verificados para la operación', 'Confirmar que el equipo asignado se encuentra apto y corresponde a la OT.', 1],
            ['TEAM_COMPLETE', 'Equipo operativo presente y conforme a la asignación', 'Confirmar presencia del personal requerido para la misión.', 1],
            ['CLIENT_ALIGNMENT', 'Alcance y condiciones coordinadas con el cliente', 'Confirmar que el responsable en sitio conoce el alcance de la actividad.', 1],
            ['DOCUMENTS_READY', 'Documentación operativa disponible', 'Confirmar permisos, OT u otros documentos requeridos para la misión.', 0],
        ];
        foreach ($items as $index => [$code, $label, $description, $required]) {
            $this->db->table('work_order_checklist_template_items')->insert([
                'template_id' => $templateId,
                'code' => $code,
                'label' => $label,
                'description' => $description,
                'stage' => 'execution',
                'is_required' => $required,
                'requires_note_on_no' => 1,
                'sort_order' => ($index + 1) * 10,
                'status' => 1,
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('work_order_checklist_items', true);
        $this->forge->dropTable('work_order_checklists', true);
        $this->forge->dropTable('work_order_checklist_template_items', true);
        $this->forge->dropTable('work_order_checklist_templates', true);
    }
}
