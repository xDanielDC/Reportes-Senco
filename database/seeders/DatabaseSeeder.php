<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            LinksZeroOnePermissionSeeder::class,
            DesignPrioritySeeder::class,
            DesignStateSeeder::class,
            DesignTimeStateSeeder::class,
            RutasTecnicasPermissionsSeeder::class,
            ListaPreciosPermissionSeeder::class,
            VisitasTecnicasPermissionsSeeder::class,
        ]);
    }
}
