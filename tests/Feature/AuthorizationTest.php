<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup roles for testing
     */
    private function setupRoles()
    {
        // Create roles
        $candidateRole = Role::create([
            'name' => 'candidate',
            'description' => 'Applicant to YouCode training'
        ]);
        
        $instructorRole = Role::create([
            'name' => 'instructor',
            'description' => 'Teaching staff for evaluation'
        ]);
        
        $adminRole = Role::create([
            'name' => 'administrator',
            'description' => 'Platform management personnel'
        ]);
        
        return [
            'candidate' => $candidateRole,
            'instructor' => $instructorRole,
            'administrator' => $adminRole
        ];
    }
    
    /**
     * Create a user with the given role
     */
    private function createUserWithRole($role)
    {
        return User::create([
            'name' => 'Test ' . ucfirst($role->name),
            'email' => $role->name . '@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id
        ]);
    }
    
    /**
     * Get auth token for a user
     */
    private function getAuthToken($user)
    {
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);
        
        return $response->json('token');
    }

    /**
     * Test role-based access control for administrators
     */
    public function test_administrator_can_access_admin_routes(): void
    {
        $roles = $this->setupRoles();
        $admin = $this->createUserWithRole($roles['administrator']);
        $token = $this->getAuthToken($admin);
        
        // Test access to admin-only route
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/users');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['users']);
    }
    
    /**
     * Test role-based access control for instructors
     */
    public function test_instructor_can_access_instructor_routes(): void
    {
        $roles = $this->setupRoles();
        $instructor = $this->createUserWithRole($roles['instructor']);
        $token = $this->getAuthToken($instructor);
        
        // Test access to instructor route
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/users');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['users']);
    }
    
    /**
     * Test role-based access control for candidates
     */
    public function test_candidate_cannot_access_admin_routes(): void
    {
        $roles = $this->setupRoles();
        $candidate = $this->createUserWithRole($roles['candidate']);
        $token = $this->getAuthToken($candidate);
        
        // Test access to admin-only route
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/users');
        
        $response->assertStatus(403);
    }
    
    /**
     * Test permission checking
     */
    public function test_user_permissions_endpoint(): void
    {
        $roles = $this->setupRoles();
        
        // Test admin permissions
        $admin = $this->createUserWithRole($roles['administrator']);
        $adminToken = $this->getAuthToken($admin);
        
        $adminResponse = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                              ->getJson('/api/permissions');
        
        $adminResponse->assertStatus(200)
                      ->assertJson([
                          'role' => 'administrator'
                      ])
                      ->assertJsonStructure([
                          'role',
                          'permissions' => [
                              'users',
                              'roles',
                              'evaluations',
                              'problems',
                              'submissions',
                              'results'
                          ]
                      ]);
        
        // Test candidate permissions
        $candidate = $this->createUserWithRole($roles['candidate']);
        $candidateToken = $this->getAuthToken($candidate);
        
        $candidateResponse = $this->withHeader('Authorization', 'Bearer ' . $candidateToken)
                                  ->getJson('/api/permissions');
        
        $candidateResponse->assertStatus(200)
                          ->assertJson([
                              'role' => 'candidate'
                          ])
                          ->assertJsonStructure([
                              'role',
                              'permissions' => [
                                  'evaluations',
                                  'problems',
                                  'submissions',
                                  'results'
                              ]
                          ]);
    }
    
    /**
     * Test role checking endpoint
     */
    public function test_check_role_endpoint(): void
    {
        $roles = $this->setupRoles();
        $admin = $this->createUserWithRole($roles['administrator']);
        $token = $this->getAuthToken($admin);
        
        // Check admin role
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/check-role/administrator');
        
        $response->assertStatus(200)
                 ->assertJson([
                     'has_role' => true
                 ]);
        
        // Check non-admin role
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/check-role/candidate');
        
        $response->assertStatus(200)
                 ->assertJson([
                     'has_role' => false
                 ]);
    }
}
