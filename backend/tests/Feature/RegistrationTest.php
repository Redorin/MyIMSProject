<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function student_can_register_with_valid_student_id_and_remains_pending()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
            'student_id' => '12-3456-789',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['message' => 'Account created successfully. Your account is pending approval by an administrator.']);

        $this->assertDatabaseHas('users', [
            'email' => 'newstudent@example.com',
            'student_id' => '12-3456-789',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function login_is_forbidden_until_account_is_approved()
    {
        $user = User::factory()->create([
            'status' => 'pending',
            'email' => 'p@example.com',
            'password' => bcrypt('password123'),
        ]);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $login->assertStatus(403);
        $login->assertJson(['message' => 'Your account is still pending approval.']);

        // approve and verify
        $user->status = 'approved';
        $user->save();

        $login2 = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        $login2->assertStatus(200);
    }

    /** @test */
    public function registration_requires_student_id_and_matches_pattern()
    {
        $bad = $this->postJson('/api/register', [
            'name' => 'Bad ID',
            'email' => 'bad@example.com',
            'student_id' => '123',
            'password' => 'password123',
        ]);
        $bad->assertStatus(422);
        $bad->assertJsonValidationErrors(['student_id']);
    }
}
