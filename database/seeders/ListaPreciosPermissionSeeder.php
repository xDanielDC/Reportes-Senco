<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ListaPreciosPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear el permiso
        $permission = Permission::firstOrCreate(
            ['name' => 'ver-lista-precios'],
            ['guard_name' => 'web']
        );

        $this->command->info('✅ Permiso "ver-lista-precios" creado');

        // Roles que deben tener acceso
        $rolesConAcceso = ['Asesor', 'super-admin'];

        foreach ($rolesConAcceso as $roleName) {
            $role = Role::where('name', $roleName)->first();
            
            if ($role) {
                if (!$role->hasPermissionTo('ver-lista-precios')) {
                    $role->givePermissionTo('ver-lista-precios');
                    $this->command->info("✅ Permiso asignado al rol: {$roleName}");
                } else {
                    $this->command->warn("⚠️  El rol {$roleName} ya tiene el permiso");
                }
            } else {
                $this->command->error("❌ Rol no encontrado: {$roleName}");
            }
        }

        $this->command->info('');
        $this->command->info('✅ Configuración de permisos completada');
        $this->command->info('');
        $this->command->info('💡 Para asignar el permiso a otros roles, usa:');
        $this->command->info('   php artisan tinker');
        $this->command->info('   Role::where(\'name\', \'nombre-rol\')->first()->givePermissionTo(\'ver-lista-precios\');');
    }
}