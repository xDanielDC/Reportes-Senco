<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LinksZeroOnePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionName = 'links-zeroOne';
        $guard = 'sanctum'; // siempre minúscula, consistente

        // 1️⃣ Crear el permiso
        Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => $guard,
        ]);
        $this->command->info("✅ Permiso '{$permissionName}' creado para guard {$guard}");

        // 2️⃣ Roles con acceso
        $rolesConAcceso = ['super-admin'];

        foreach ($rolesConAcceso as $roleName) {
            // Crear el rol si no existe
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);

            // 3️⃣ Asignar permiso al rol
            if (!$role->hasPermissionTo($permissionName, $guard)) {
                $role->givePermissionTo($permissionName);
                $this->command->info("✅ Permiso asignado al rol: {$roleName}");
            } else {
                $this->command->warn("⚠️ El rol {$roleName} ya tiene el permiso");
            }
        }

        $this->command->info('✅ Configuración de permisos completada');
    }
}
