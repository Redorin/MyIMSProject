<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run()
{
    // Create a Student
    User::create([
        'name' => 'John Student',
        'email' => 'student@campus.edu',
        'password' => Hash::make('password123'), // Securely hashed
    ]);

    // Create an Admin
    User::create([
        'name' => 'Admin Staff',
        'email' => 'admin@campus.edu',
        'password' => Hash::make('admin123'),
    ]);

    $this->call(SpaceSeeder::class);
}
}
