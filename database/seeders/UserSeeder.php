<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = new User([
            'name' => 'Super Administrator',
            'username' => 'sa',
            'email' => 'sa@example.com',
            'password' => Hash::make('As142536*')
        ]);

        $user->save();
        $user->syncRoles(['super-admin']);
    }
}
