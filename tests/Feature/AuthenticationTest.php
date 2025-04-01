<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration.
     */
    public function test_user_can_register(): void
    {
        // Create a role first
        $role = Role::create([
            'name' => 'candidate',
            'description' => 'Applicant to YouCode training'
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $role->id
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'user',
                     'token'
                 ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role_id' => $role->id
        ]);
    }

    /**
     * Test user login.
     */
    public function test_user_can_login(): void
    {
        // Create a role
        $role = Role::create([
            'name' => 'candidate',
            'description' => 'Applicant to YouCode training'
        ]);

        // Create a user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'user',
                     'token'
                 ]);
    }

    /**
     * Test invalid login credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        // Create a role
        $role = Role::create([
            'name' => 'candidate',
            'description' => 'Applicant to YouCode training'
        ]);

        // Create a user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Invalid login credentials'
                 ]);
    }

    /**
     * Test user logout.
     */
    public function test_user_can_logout(): void
    {
        // Create a role
        $role = Role::create([
            'name' => 'candidate',
            'description' => 'Applicant to YouCode training'
        ]);

        // Create a user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id
        ]);

        // Login to get token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('token');

        // Logout with token
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Successfully logged out'
                 ]);
    }
}
