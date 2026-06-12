<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class VisitasTecnicasPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resetear cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos de Visitas Técnicas
        $visitasTecnicasPermissions = [
            'visitastecnicas.ver',
            'visitastecnicas.ver-todos',
            'visitastecnicas.repuestos.ver',
            'visitastecnicas.repuestos.ver-todos',
            'visitastecnicas.repuestos.gestiona',
        ];

        // Permisos de Rutas Técnicas
        $rutasTecnicasPermissions = [
            'rutas-tecnicas.ver',
            'rutas-tecnicas.crear',
            'rutas-tecnicas.editar',
            'rutas-tecnicas.eliminar',
            'rutas-tecnicas.gestionar',
        ];

        // Permisos de Lista de Precios
        $listaPreciosPermissions = [
            'ver-lista-precios',
        ];

        $allPermissions = array_merge(
            $visitasTecnicasPermissions,
            $rutasTecnicasPermissions,
            $listaPreciosPermissions
        );

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'sanctum']
            );
        }

        $this->command->info('✅ Permisos de Visitas Técnicas, Rutas Técnicas y Lista de Precios creados exitosamente!');

        // Mostrar permisos creados
        $this->command->table(
            ['Permiso', 'Guard'],
            Permission::whereIn('name', $allPermissions)
                ->get(['name', 'guard_name'])
                ->toArray()
        );

        $this->command->info('');
        $this->command->info('📋 Resumen de permisos creados:');
        $this->command->info('   - Visitas Técnicas: ' . implode(', ', $visitasTecnicasPermissions));
        $this->command->info('   - Rutas Técnicas: ' . implode(', ', $rutasTecnicasPermissions));
        $this->command->info('   - Lista de Precios: ' . implode(', ', $listaPreciosPermissions));
    }
}
