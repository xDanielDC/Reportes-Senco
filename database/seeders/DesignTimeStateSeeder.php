<?php

namespace Database\Seeders;

use App\Models\DesignTimeState;
use Illuminate\Database\Seeder;

class DesignTimeStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            'A TIEMPO', 'JUSTO A TIEMPO', 'RETRASADO',
        ];

        foreach ($states as $state) {
            DesignTimeState::create([
                'name' => $state,
                'created_id' => 1,
                'updated_id' => 1,
            ]);
        }
    }
}
