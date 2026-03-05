<?php
// Run this script with: php backend/scripts/assign_space_codes.php

use App\Models\Space;

require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$usedCodes = [];
$spaces = Space::all();
foreach ($spaces as $space) {
    do {
        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    } while (in_array($code, $usedCodes) || Space::where('code', $code)->exists());
    $usedCodes[] = $code;
    $space->code = $code;
    $space->save();
    echo "Assigned code $code to space ID {$space->id}\n";
}
echo "All spaces now have unique 4-digit codes.\n";
