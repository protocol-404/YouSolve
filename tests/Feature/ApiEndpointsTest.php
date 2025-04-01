<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Evaluation;
use App\Models\Problem;
use App\Models\Submission;
use App\Models\Result;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private $adminToken;
    private $instructorToken;
    private $candidateToken;
    private $admin;
    private $instructor;
    private $candidate;
    private $roles;

    /**
     * Setup test environment
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $this->roles = [
            'candidate' => Role::create([
                'name' => 'candidate',
                'description' => 'Applicant to YouCode training'
            ]),
            'instructor' => Role::create([
                'name' => 'instructor',
                'description' => 'Teaching staff for evaluation'
            ]),
            'administrator' => Role::create([
                'name' => 'administrator',
                'description' => 'Platform management personnel'
            ])
        ];
        
        // Create users
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->roles['administrator']->id
        ]);
        
        $this->instructor = User::create([
            'name' => 'Instructor User',
            'email' => 'instructor@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->roles['instructor']->id
        ]);
        
        $this->candidate = User::create([
            'name' => 'Candidate User',
            'email' => 'candidate@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $this->roles['candidate']->id
        ]);
        
        // Get tokens
        $this->adminToken = $this->getAuthToken($this->admin);
        $this->instructorToken = $this->getAuthToken($this->instructor);
        $this->candidateToken = $this->getAuthToken($this->candidate);
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
     * Test evaluation endpoints
     */
    public function test_evaluation_endpoints(): void
    {
        // Test creating evaluation (admin only)
        $evaluationData = [
            'name' => 'C Programming Basics',
            'description' => 'Basic C programming concepts and syntax',
            'language' => 'C',
            'is_active' => true
        ];
        
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                               ->postJson('/api/evaluations', $evaluationData);
        
        $createResponse->assertStatus(201)
                       ->assertJsonStructure([
                           'message',
                           'evaluation' => [
                               'id', 'name', 'description', 'language', 'is_active'
                           ]
                       ]);
        
        $evaluationId = $createResponse->json('evaluation.id');
        
        // Test listing evaluations (all authenticated users)
        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                             ->getJson('/api/evaluations');
        
        $listResponse->assertStatus(200)
                     ->assertJsonStructure([
                         'evaluations'
                     ]);
        
        // Test getting single evaluation (all authenticated users)
        $showResponse = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                             ->getJson('/api/evaluations/' . $evaluationId);
        
        $showResponse->assertStatus(200)
                     ->assertJsonStructure([
                         'evaluation' => [
                             'id', 'name', 'description', 'language', 'is_active'
                         ]
                     ]);
        
        // Test updating evaluation (admin only)
        $updateData = [
            'name' => 'Updated C Programming Basics',
            'is_active' => false
        ];
        
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                               ->putJson('/api/evaluations/' . $evaluationId, $updateData);
        
        $updateResponse->assertStatus(200)
                       ->assertJson([
                           'evaluation' => [
                               'name' => 'Updated C Programming Basics',
                               'is_active' => false
                           ]
                       ]);
        
        // Test candidate cannot update evaluation
        $candidateUpdateResponse = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                                        ->putJson('/api/evaluations/' . $evaluationId, $updateData);
        
        $candidateUpdateResponse->assertStatus(403);
    }

    /**
     * Test problem endpoints
     */
    public function test_problem_endpoints(): void
    {
        // Create an evaluation first
        $evaluation = Evaluation::create([
            'name' => 'JavaScript Basics',
            'description' => 'Basic JavaScript programming concepts',
            'language' => 'JavaScript',
            'is_active' => true
        ]);
        
        // Test creating problem (admin only)
        $problemData = [
            'evaluation_id' => $evaluation->id,
            'title' => 'Hello World',
            'description' => 'Write a function that returns "Hello, World!"',
            'example_input' => 'None',
            'example_output' => 'Hello, World!',
            'difficulty' => 'easy',
            'time_limit' => 1000,
            'memory_limit' => 128,
            'is_active' => true
        ];
        
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                               ->postJson('/api/problems', $problemData);
        
        $createResponse->assertStatus(201)
                       ->assertJsonStructure([
                           'message',
                           'problem' => [
                               'id', 'evaluation_id', 'title', 'description', 'difficulty'
                           ]
                       ]);
        
        $problemId = $createResponse->json('problem.id');
        
        // Test listing problems (all authenticated users)
        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                             ->getJson('/api/problems');
        
        $listResponse->assertStatus(200)
                     ->assertJsonStructure([
                         'problems'
                     ]);
        
        // Test filtering problems by evaluation
        $filterResponse = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                               ->getJson('/api/problems?evaluation_id=' . $evaluation->id);
        
        $filterResponse->assertStatus(200)
                       ->assertJsonStructure([
                           'problems'
                       ]);
        
        // Test getting single problem (all authenticated users)
        $showResponse = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                             ->getJson('/api/problems/' . $problemId);
        
        $showResponse->assertStatus(200)
                     ->assertJsonStructure([
                         'problem' => [
                             'id', 'evaluation_id', 'title', 'description', 'difficulty'
                         ]
                     ]);
    }

    /**
     * Test submission endpoints
     */
    public function test_submission_endpoints(): void
    {
        // Create evaluation and problem first
        $evaluation = Evaluation::create([
            'name' => 'PHP Basics',
            'description' => 'Basic PHP programming concepts',
            'language' => 'PHP',
            'is_active' => true
        ]);
        
        $problem = Problem::create([
            'evaluation_id' => $evaluation->id,
            'title' => 'Sum Two Numbers',
            'description' => 'Write a function that returns the sum of two numbers',
            'example_input' => '2, 3',
            'example_output' => '5',
            'difficulty' => 'easy',
            'time_limit' => 1000,
            'memory_limit' => 128,
            'is_active' => true
        ]);
        
        // Test creating submission (candidate)
        $submissionData = [
            'problem_id' => $problem->id,
            'code' => 'function sum($a, $b) { return $a + $b; }',
            'language' => 'PHP'
        ];
        
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                               ->postJson('/api/submissions', $submissionData);
        
        $createResponse->assertStatus(201)
                       ->assertJsonStructure([
                           'message',
                           'submission' => [
                               'id', 'user_id', 'problem_id', 'code', 'language', 'status'
                           ],
                           'result'
                       ]);
        
        $submissionId = $createResponse->json('submission.id');
        
        // Test listing submissions (all authenticated users, but filtered by permissions)
        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                             ->getJson('/api/submissions?my_submissions=true');
        
        $listResponse->assertStatus(200)
                     ->assertJsonStructure([
                         'submissions'
                     ]);
        
        // Test getting single submission
        $showResponse = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                             ->getJson('/api/submissions/' . $submissionId);
        
        $showResponse->assertStatus(200)
                     ->assertJsonStructure([
                         'submission' => [
                             'id', 'user_id', 'problem_id', 'code', 'language', 'status'
                         ]
                     ]);
    }

    /**
     * Test user management endpoints
     */
    public function test_user_management_endpoints(): void
    {
        // Test listing users (admin and instructor only)
        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                             ->getJson('/api/users');
        
        $listResponse->assertStatus(200)
                     ->assertJsonStructure([
                         'users'
                     ]);
        
        // Test candidate cannot list users
        $candidateListResponse = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                                      ->getJson('/api/users');
        
        $candidateListResponse->assertStatus(403);
        
        // Test creating user (admin only)
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role_id' => $this->roles['candidate']->id
        ];
        
        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                               ->postJson('/api/users', $userData);
        
        $createResponse->assertStatus(201)
                       ->assertJsonStructure([
                           'message',
                           'user' => [
                               'id', 'name', 'email', 'role_id'
                           ]
                       ]);
        
        $userId = $createResponse->json('user.id');
        
        // Test getting user progress
        $progressResponse = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                                 ->getJson('/api/my-progress');
        
        $progressResponse->assertStatus(200)
                         ->assertJsonStructure([
                             'user_id',
                             'total_submissions',
                             'successful_submissions',
                             'success_rate'
                         ]);
    }
}
