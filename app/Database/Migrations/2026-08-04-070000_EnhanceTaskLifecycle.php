<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnhanceTaskLifecycle extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('tasks', [
            'started_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'due_at'],
            'completion_note' => ['type' => 'TEXT', 'null' => true, 'after' => 'completed_at'],
            'reschedule_reason' => ['type' => 'TEXT', 'null' => true, 'after' => 'completion_note'],
            'active_key' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true, 'after' => 'reschedule_reason'],
        ]);

        $db = db_connect();

        $duplicates = $db->query(
            "SELECT related_type, related_id, task_type, MIN(id) AS keep_id
             FROM tasks
             WHERE status IN ('pending', 'in_progress')
             GROUP BY related_type, related_id, task_type
             HAVING COUNT(*) > 1"
        )->getResultArray();

        foreach ($duplicates as $duplicate) {
            $db->table('tasks')
                ->where('related_type', $duplicate['related_type'])
                ->where('related_id', $duplicate['related_id'])
                ->where('task_type', $duplicate['task_type'])
                ->whereIn('status', ['pending', 'in_progress'])
                ->where('id !=', $duplicate['keep_id'])
                ->update([
                    'status' => 'cancelled',
                    'completed_at' => date('Y-m-d H:i:s'),
                    'completion_note' => 'Cancelada automáticamente durante la estabilización del Motor de Próxima Acción.',
                    'modify_user' => 'system',
                    'modify_date' => date('Y-m-d H:i:s'),
                ]);
        }

        $db->query(
            "UPDATE tasks
             SET active_key = CASE
                 WHEN status IN ('pending', 'in_progress') THEN CONCAT(related_type, ':', related_id, ':', task_type)
                 ELSE NULL
             END"
        );

        $this->forge->addUniqueKey('active_key', 'uq_tasks_active_key');
        $this->forge->processIndexes('tasks');

        $db->query('DROP TRIGGER IF EXISTS trg_tasks_active_key_insert');
        $db->query('DROP TRIGGER IF EXISTS trg_tasks_active_key_update');
        $db->query(
            "CREATE TRIGGER trg_tasks_active_key_insert
             BEFORE INSERT ON tasks
             FOR EACH ROW
             SET NEW.active_key = CASE
                 WHEN NEW.status IN ('pending', 'in_progress') THEN CONCAT(NEW.related_type, ':', NEW.related_id, ':', NEW.task_type)
                 ELSE NULL
             END"
        );
        $db->query(
            "CREATE TRIGGER trg_tasks_active_key_update
             BEFORE UPDATE ON tasks
             FOR EACH ROW
             SET NEW.active_key = CASE
                 WHEN NEW.status IN ('pending', 'in_progress') THEN CONCAT(NEW.related_type, ':', NEW.related_id, ':', NEW.task_type)
                 ELSE NULL
             END"
        );
    }

    public function down(): void
    {
        $db = db_connect();
        $db->query('DROP TRIGGER IF EXISTS trg_tasks_active_key_insert');
        $db->query('DROP TRIGGER IF EXISTS trg_tasks_active_key_update');
        $this->forge->dropKey('tasks', 'uq_tasks_active_key');
        $this->forge->dropColumn('tasks', ['started_at', 'completion_note', 'reschedule_reason', 'active_key']);
    }
}
