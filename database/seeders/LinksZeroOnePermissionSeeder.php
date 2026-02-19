<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LinksZeroOnePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionName = 'links-zeroOne';

        // Crear el permiso para el guard web (UI)
        Permission::firstOrCreate(
            ['name' => $permissionName, 'guard_name' => 'web']
        );
        $this->command->info('✅ Permiso "links-zeroOne" creado para guard web');

        // Roles con acceso (ajusta segun tus roles)
        $rolesConAcceso = ['super-admin'];

        foreach ($rolesConAcceso as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role) {
                // Asegurar permiso para el guard del rol (sanctum/web, etc.)
                Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => $role->guard_name]
                );

                if (!$role->hasPermissionTo($permissionName, $role->guard_name)) {
                    $role->givePermissionTo($permissionName);
                    $this->command->info("✅ Permiso asignado al rol: {$roleName}");
                } else {
                    $this->command->warn("⚠️  El rol {$roleName} ya tiene el permiso");
                }
            } else {
                $this->command->error("❌ Rol no encontrado: {$roleName}");
            }
        }

        $this->command->info('');
        $this->command->info('✅ Configuracion de permisos completada');
    }
}
