<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$unapproved = DB::table('users')->where('is_approved', false)->get();
echo 'Unapproved users count: ' . count($unapproved) . PHP_EOL;
echo 'Total users: ' . DB::table('users')->count() . PHP_EOL;

if (count($unapproved) > 0) {
    echo 'Sample unapproved users:' . PHP_EOL;
    foreach (array_slice($unapproved->toArray(), 0, 3) as $user) {
        echo '  - ' . $user->name . ' (' . $user->email . ')' . PHP_EOL;
    }
}
?>