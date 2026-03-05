<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Space;

class SpaceSeeder extends Seeder
{
    public function run()
    {
        // real campus spaces list with default capacities
        // remove old placeholder entries if they exist
        \App\Models\Space::whereIn('name', [
            'University Library',
            'Main Canteen',
            'Student Center',
            'Study Park',
        ])->delete();

        $spaces = [
            ['name' => 'V Building - Canteen', 'capacity' => 100],
            ['name' => 'V Building - Library', 'capacity' => 100],
            ['name' => 'A Building - Registrar Area', 'capacity' => 50],
            ['name' => 'A Building - Student Lounge', 'capacity' => 80],
            ['name' => 'L Building - Kwago', 'capacity' => 60],
            ['name' => 'L Building - Cisco', 'capacity' => 60],
            ['name' => 'L Building - 3rd Floor', 'capacity' => 120],
            ['name' => 'L Building - 4th Floor', 'capacity' => 120],
            ['name' => 'L Building - 5th Floor', 'capacity' => 120],
            ['name' => 'L Building - AVT', 'capacity' => 80],
            ['name' => 'F Building - 1st Floor', 'capacity' => 100],
            ['name' => 'F Building - 2nd Floor', 'capacity' => 100],
            ['name' => 'F Building - 3rd Floor', 'capacity' => 100],
            ['name' => 'F Building - 4th Floor', 'capacity' => 100],
            ['name' => 'LCR - Main Hall', 'capacity' => 200],
            ['name' => 'LCR - Left Bleachers', 'capacity' => 150],
            ['name' => 'LCR - Right Bleachers', 'capacity' => 150],
            ['name' => 'LCR - Back Bleachers', 'capacity' => 150],
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
