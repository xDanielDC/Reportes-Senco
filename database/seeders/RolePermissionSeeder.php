<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                "name" => "role.index",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "role.create",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "role.edit",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "role.destroy",
                "guard_name" => "sanctum"
            ],

            /**
             * Users
             */
            [
                "name" => "user.index",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "user.create",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "user.edit",
                "guard_name" => "sanctum"
            ],

            [
                "name" => "user.destroy",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "user.update-reports",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "user.report.update-filters",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "user.report.set-default",
                "guard_name" => "sanctum"
            ],
            /**
             * Reports
             */
            [
                "name" => "report.index",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "report.create",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "report.edit",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "report.destroy",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "report.view",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "report.import",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "report.filter.index",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "report.filter.store",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "report.filter.update",
                "guard_name" => "sanctum"
            ],
            [
                "name" => "report.filter.destroy",
                "guard_name" => "sanctum"
            ]
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        $role = Role::create([
            "name" => "super-admin",
            "guard_name" => "sanctum"
        ]);

        $role->syncPermissions(array_column($permissions, 'name'));
    }
}
