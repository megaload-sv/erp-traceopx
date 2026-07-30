<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAuditFieldsToSecurityTables extends Migration
{
    private array $tables = [
        'users',
        'roles',
        'permissions',
        'user_roles',
        'role_permissions',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $this->addFieldIfMissing($table, 'entry_user', [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => true,
            ]);

            $this->addFieldIfMissing($table, 'entry_date', [
                'type' => 'DATETIME',
                'null' => true,
            ]);

            $this->addFieldIfMissing($table, 'modify_user', [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => true,
            ]);

            $this->addFieldIfMissing($table, 'modify_date', [
                'type' => 'DATETIME',
                'null' => true,
            ]);

            $this->addFieldIfMissing($table, 'delete_user', [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => true,
            ]);

            $this->addFieldIfMissing($table, 'delete_date', [
                'type' => 'DATETIME',
                'null' => true,
            ]);

            $this->backfillAuditData($table);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            foreach ([
                'delete_date',
                'delete_user',
                'modify_date',
                'modify_user',
                'entry_date',
                'entry_user',
            ] as $field) {
                if ($this->db->fieldExists($field, $table)) {
                    $this->forge->dropColumn($table, $field);
                }
            }
        }
    }

    private function addFieldIfMissing(string $table, string $field, array $definition): void
    {
        if (! $this->db->fieldExists($field, $table)) {
            $this->forge->addColumn($table, [$field => $definition]);
        }
    }

    private function backfillAuditData(string $table): void
    {
        $builder = $this->db->table($table);

        if ($this->db->fieldExists('created_at', $table)) {
            $builder
                ->where('entry_date IS NULL', null, false)
                ->set('entry_date', 'created_at', false)
                ->update();
        }

        $builder
            ->where('entry_date IS NULL', null, false)
            ->set('entry_date', date('Y-m-d H:i:s'))
            ->update();

        $builder
            ->where('entry_user IS NULL', null, false)
            ->set('entry_user', 'system')
            ->update();
    }
}
