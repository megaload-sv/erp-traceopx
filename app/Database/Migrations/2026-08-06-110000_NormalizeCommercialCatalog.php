<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeCommercialCatalog extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'code' => ['type'=>'VARCHAR','constraint'=>40],
            'name' => ['type'=>'VARCHAR','constraint'=>120],
            'description' => ['type'=>'TEXT','null'=>true],
            'display_order' => ['type'=>'INT','unsigned'=>true,'default'=>0],
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
        $this->forge->createTable('commercial_item_groups', true);

        $this->forge->addColumn('commercial_units', [
            'abbreviation' => ['type'=>'VARCHAR','constraint'=>20,'null'=>true,'after'=>'symbol'],
            'display_order' => ['type'=>'INT','unsigned'=>true,'default'=>0,'after'=>'abbreviation'],
        ]);

        $this->forge->addColumn('commercial_items', [
            'item_group_id' => ['type'=>'INT','unsigned'=>true,'null'=>true,'after'=>'item_type'],
            'allows_unit_override' => ['type'=>'TINYINT','constraint'=>1,'default'=>1,'after'=>'allows_price_override'],
            'display_order' => ['type'=>'INT','unsigned'=>true,'default'=>0,'after'=>'allows_unit_override'],
            'source_reference' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true,'after'=>'display_order'],
            'normalization_notes' => ['type'=>'TEXT','null'=>true,'after'=>'source_reference'],
        ]);

        $this->db->query('ALTER TABLE `commercial_items` ADD CONSTRAINT `fk_commercial_items_group` FOREIGN KEY (`item_group_id`) REFERENCES `commercial_item_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->db->query('CREATE INDEX `idx_commercial_items_group_status` ON `commercial_items` (`item_group_id`, `status`)');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE `commercial_items` DROP FOREIGN KEY `fk_commercial_items_group`');
        $this->db->query('DROP INDEX `idx_commercial_items_group_status` ON `commercial_items`');
        $this->forge->dropColumn('commercial_items', ['item_group_id','allows_unit_override','display_order','source_reference','normalization_notes']);
        $this->forge->dropColumn('commercial_units', ['abbreviation','display_order']);
        $this->forge->dropTable('commercial_item_groups', true);
    }
}
