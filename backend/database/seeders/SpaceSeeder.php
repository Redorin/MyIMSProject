<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Space;

class SpaceSeeder extends Seeder
{
    public function run()
    {
        $spaces = [
            ['name' => 'University Library', 'occupancy' => 99, 'capacity' => 100, 'status' => 'low'],
            ['name' => 'Main Canteen', 'occupancy' => 85, 'capacity' => 100, 'status' => 'high'],
            ['name' => 'Student Center', 'occupancy' => 45, 'capacity' => 100, 'status' => 'medium'],
            ['name' => 'Study Park', 'occupancy' => 5, 'capacity' => 50, 'status' => 'low'],
        ];

        $usedCodes = [];
        foreach ($spaces as $space) {
            do {
                $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            } while (in_array($code, $usedCodes));
            $usedCodes[] = $code;
            Space::create(array_merge($space, ['code' => $code]));
        }
    }
}
