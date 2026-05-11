<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RemoveSaPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-all-permissions-to-sa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign all permissions and super-admin role to the sa user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::where('username', 'sa')->first();

        if (!$user) {
            $this->error('User with username "sa" not found.');
            return Command::FAILURE;
        }

        $this->info("Found user: {$user->name} ({$user->email})");

        // Get all existing permissions in the system
        $allPermissions = Permission::all();
        $this->info("Total permissions in system: " . $allPermissions->count());

        // Assign all permissions directly to the user
        $user->syncPermissions($allPermissions);
        $this->info('All permissions assigned to user "sa".');

        // Assign super-admin role
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $user->syncRoles(['super-admin']);
            $this->info('Super-admin role assigned to user "sa".');
        } else {
            $this->warn('Super-admin role not found in the system.');
        }

        $this->info('All permissions and super-admin role have been assigned to user "sa".');

        return Command::SUCCESS;
    }
}
