<?php

namespace Database\Seeders;

use App\Models\DesignPriority;
use Illuminate\Database\Seeder;

class DesignPrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorities = [
            'ALTA', 'MEDIA', 'BAJA',
        ];

        foreach ($priorities as $priority) {
            DesignPriority::create([
                'name' => $priority,
                'created_id' => 1,
                'updated_id' => 1,
            ]);
        }
    }
}
