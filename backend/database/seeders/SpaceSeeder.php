<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Space;

class SpaceSeeder extends Seeder
{
    public function run()
    {
        Space::create(['name' => 'University Library', 'occupancy' => 99, 'capacity' => 100, 'status' => 'low']);
        Space::create(['name' => 'Main Canteen', 'occupancy' => 85, 'capacity' => 100, 'status' => 'high']);
        Space::create(['name' => 'Student Center', 'occupancy' => 45, 'capacity' => 100, 'status' => 'medium']);
        Space::create(['name' => 'Study Park', 'occupancy' => 5, 'capacity' => 50, 'status' => 'low']);
    }
}
