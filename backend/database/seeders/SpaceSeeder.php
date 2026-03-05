<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Space;

class SpaceSeeder extends Seeder
{
    public function run()
    {
        // initial spaces; occupancy/status will default to 0/low if omitted
        $spaces = [
            ['name' => 'University Library', 'capacity' => 100],
            ['name' => 'Main Canteen', 'capacity' => 100],
            ['name' => 'Student Center', 'capacity' => 100],
            ['name' => 'Study Park', 'capacity' => 50],
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
