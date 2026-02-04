<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RutasTecnicasPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resetear cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos para rutas técnicas
        $permissions = [
            'rutas-tecnicas.ver',
            'rutas-tecnicas.crear',
            'rutas-tecnicas.editar',
            'rutas-tecnicas.eliminar',
            'rutas-tecnicas.gestionar', // Permiso especial para gestión completa
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('✓ Permisos de rutas técnicas creados exitosamente!');

        // Mostrar permisos creados
        $this->command->table(
            ['Permiso', 'Guard'],
            Permission::where('name', 'LIKE', 'rutas-tecnicas.%')
                ->get(['name', 'guard_name'])
                ->toArray()
        );

        // Opcional: Asignar permisos automáticamente a roles
        // Descomenta y ajusta según tus roles
        
        
        $this->command->info('');
        $this->command->info('Asignando permisos a roles...');
        
        // Administrador - Todos los permisos
        if ($adminRole = Role::where('name', 'super-admin')->first()) {
            $adminRole->givePermissionTo([
                'rutas-tecnicas.ver',
                'rutas-tecnicas.crear',
                'rutas-tecnicas.editar',
                'rutas-tecnicas.eliminar',
                'rutas-tecnicas.gestionar'
            ]);
            $this->command->info('✓ Permisos asignados al rol: Administrador');
        }

        // Técnico - Ver, crear y editar
        if ($tecnicoRole = Role::where('name', 'Asesor')->first()) {
            $tecnicoRole->givePermissionTo([
                'rutas-tecnicas.ver',
                'rutas-tecnicas.crear',
            ]);
            $this->command->info('✓ Permisos asignados al rol: Asesor');
        }
    }
}