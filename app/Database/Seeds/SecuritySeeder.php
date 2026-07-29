<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class SecuritySeeder extends Seeder
{
    private const ADMIN_NAME = 'Administrador TraceOPX';
    private const ADMIN_EMAIL = 'admin@traceopx.com';
    private const ADMIN_PASSWORD = 'TraceOPX@2026';

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $roles = [
            ['name' => 'Superadministrador', 'slug' => 'superadmin', 'description' => 'Acceso completo al ERP.', 'is_active' => 1],
            ['name' => 'Administrador', 'slug' => 'admin', 'description' => 'Administración general del sistema.', 'is_active' => 1],
            ['name' => 'Ventas', 'slug' => 'sales', 'description' => 'Clientes y cotizaciones.', 'is_active' => 1],
            ['name' => 'Operaciones', 'slug' => 'operations', 'description' => 'Órdenes y ejecución de trabajos.', 'is_active' => 1],
            ['name' => 'Contabilidad', 'slug' => 'accounting', 'description' => 'Facturación, cobros y reportes.', 'is_active' => 1],
            ['name' => 'Consulta', 'slug' => 'viewer', 'description' => 'Acceso de solo lectura.', 'is_active' => 1],
        ];

        foreach ($roles as $role) {
            $existing = $this->db->table('roles')->where('slug', $role['slug'])->get()->getRowArray();
            if ($existing === null) {
                $this->db->table('roles')->insert($role + ['created_at' => $now, 'updated_at' => $now]);
            }
        }

        $permissions = [
            ['name' => 'Ver dashboard', 'slug' => 'dashboard.view', 'module' => 'dashboard'],
            ['name' => 'Administrar usuarios', 'slug' => 'users.manage', 'module' => 'security'],
            ['name' => 'Administrar roles', 'slug' => 'roles.manage', 'module' => 'security'],
            ['name' => 'Ver clientes', 'slug' => 'customers.view', 'module' => 'customers'],
            ['name' => 'Gestionar cotizaciones', 'slug' => 'quotations.manage', 'module' => 'quotations'],
            ['name' => 'Gestionar órdenes', 'slug' => 'work_orders.manage', 'module' => 'operations'],
            ['name' => 'Gestionar facturación', 'slug' => 'invoices.manage', 'module' => 'billing'],
            ['name' => 'Gestionar cobros', 'slug' => 'collections.manage', 'module' => 'collections'],
        ];

        foreach ($permissions as $permission) {
            $existing = $this->db->table('permissions')->where('slug', $permission['slug'])->get()->getRowArray();
            if ($existing === null) {
                $this->db->table('permissions')->insert($permission + [
                    'description' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $user = $this->db->table('users')->where('email', self::ADMIN_EMAIL)->get()->getRowArray();

        if ($user === null) {
            $this->db->table('users')->insert([
                'name' => self::ADMIN_NAME,
                'email' => self::ADMIN_EMAIL,
                'password_hash' => password_hash(self::ADMIN_PASSWORD, PASSWORD_DEFAULT),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $userId = (int) $this->db->insertID();
        } else {
            $userId = (int) $user['id'];
        }

        $superadmin = $this->db->table('roles')->where('slug', 'superadmin')->get()->getRowArray();
        if ($superadmin === null) {
            throw new RuntimeException('No se encontró el rol superadmin.');
        }

        $roleId = (int) $superadmin['id'];
        $userRole = $this->db->table('user_roles')
            ->where(['user_id' => $userId, 'role_id' => $roleId])
            ->get()
            ->getRowArray();

        if ($userRole === null) {
            $this->db->table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
                'created_at' => $now,
            ]);
        }

        $allPermissions = $this->db->table('permissions')->select('id')->get()->getResultArray();
        foreach ($allPermissions as $permission) {
            $permissionId = (int) $permission['id'];
            $assigned = $this->db->table('role_permissions')
                ->where(['role_id' => $roleId, 'permission_id' => $permissionId])
                ->get()
                ->getRowArray();

            if ($assigned === null) {
                $this->db->table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                ]);
            }
        }
    }
}
