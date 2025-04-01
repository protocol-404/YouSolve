<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Problem;
use App\Models\Evaluation;
use App\Models\Submission;
use App\Models\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $adminToken;
    protected $instructorToken;
    protected $candidateToken;
    protected $admin;
    protected $instructor;
    protected $candidate;
    protected $roles;
    protected $evaluation;
    protected $problem;

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
        
        // Create evaluation
        $this->evaluation = Evaluation::create([
            'name' => 'PHP Basics',
            'description' => 'Basic PHP programming concepts',
            'language' => 'PHP',
            'is_active' => true
        ]);
        
        // Create problem
        $this->problem = Problem::create([
            'evaluation_id' => $this->evaluation->id,
            'title' => 'Hello World',
            'description' => 'Write a function that returns "Hello, World!"',
            'example_input' => '',
            'example_output' => 'Hello, World!',
            'difficulty' => 'easy',
            'time_limit' => 1000,
            'memory_limit' => 128,
            'test_cases' => json_encode([
                [
                    'input' => '',
                    'output' => 'Hello, World!'
                ]
            ]),
            'is_active' => true
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
     * Test the complete flow from authentication to code submission and evaluation
     */
    public function test_complete_flow()
    {
        // Step 1: Verify authentication
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                         ->getJson('/api/user');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id', 'name', 'email', 'role_id'
                 ]);
        
        // Step 2: Get available evaluations
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                         ->getJson('/api/evaluations');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'evaluations'
                 ]);
        
        // Step 3: Get problems for the evaluation
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                         ->getJson('/api/problems?evaluation_id=' . $this->evaluation->id);
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'problems'
                 ]);
        
        // Step 4: Get problem details
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                         ->getJson('/api/problems/' . $this->problem->id);
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'problem' => [
                         'id', 'evaluation_id', 'title', 'description'
                     ]
                 ]);
        
        // Step 5: Submit code solution
        $phpCode = '<?php echo "Hello, World!";';
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                         ->postJson('/api/submissions', [
                             'problem_id' => $this->problem->id,
                             'code' => $phpCode,
                             'language' => 'PHP'
                         ]);
        
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'submission' => [
                         'id', 'user_id', 'problem_id', 'code', 'language', 'status'
                     ],
                     'result'
                 ]);
        
        $submissionId = $response->json('submission.id');
        
        // Step 6: Get submission details
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                         ->getJson('/api/submissions/' . $submissionId);
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'submission' => [
                         'id', 'user_id', 'problem_id', 'code', 'language', 'status'
                     ]
                 ]);
        
        // Step 7: Check user progress
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                         ->getJson('/api/my-progress');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'user_id',
                     'total_submissions',
                     'successful_submissions',
                     'success_rate'
                 ]);
        
        // Step 8: Check evaluation progress
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                         ->getJson('/api/users/' . $this->candidate->id . '/evaluations/' . $this->evaluation->id . '/progress');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'user_id',
                     'evaluation_id',
                     'total_problems',
                     'solved_problems',
                     'completion_percentage'
                 ]);
    }
    
    /**
     * Test instructor creating and validating test cases
     */
    public function test_instructor_test_case_management()
    {
        // Step 1: Create test cases
        $testCases = [
            [
                'input' => '',
                'output' => 'Hello, World!'
            ],
            [
                'input' => 'name=John',
                'output' => 'Hello, John!'
            ]
        ];
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->instructorToken)
                         ->postJson('/api/problems/' . $this->problem->id . '/test-cases', [
                             'test_cases' => $testCases
                         ]);
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'problem'
                 ]);
        
        // Step 2: Validate test cases with sample solution
        $phpCode = '<?php
            $name = "World";
            if (isset($_GET["name"])) {
                parse_str(file_get_contents("php://stdin"), $input);
                if (isset($input["name"])) {
                    $name = $input["name"];
                }
            }
            echo "Hello, " . $name . "!";
        ';
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->instructorToken)
                         ->postJson('/api/problems/' . $this->problem->id . '/validate-test-cases', [
                             'sample_solution' => $phpCode,
                             'language' => 'PHP'
                         ]);
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'validation_result' => [
                         'valid',
                         'test_results'
                     ]
                 ]);
        
        // Step 3: Generate test cases
        $inputs = [
            '',
            'name=John',
            'name=Alice'
        ];
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->instructorToken)
                         ->postJson('/api/problems/' . $this->problem->id . '/generate-test-cases', [
                             'sample_solution' => $phpCode,
                             'language' => 'PHP',
                             'inputs' => $inputs
                         ]);
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'test_cases'
                 ]);
    }
    
    /**
     * Test admin user management and permissions
     */
    public function test_admin_user_management()
    {
        // Step 1: Create a new user
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role_id' => $this->roles['candidate']->id
        ];
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                         ->postJson('/api/users', $userData);
        
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'user' => [
                         'id', 'name', 'email', 'role_id'
                     ]
                 ]);
        
        $userId = $response->json('user.id');
        
        // Step 2: Update user role
        $updateData = [
            'role_id' => $this->roles['instructor']->id
        ];
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                         ->putJson('/api/users/' . $userId, $updateData);
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'user'
                 ])
                 ->assertJson([
                     'user' => [
                         'role_id' => $this->roles['instructor']->id
                     ]
                 ]);
        
        // Step 3: Verify candidate cannot update roles
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                         ->putJson('/api/users/' . $userId, $updateData);
        
        $response->assertStatus(403);
        
        // Step 4: Delete user
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
                         ->deleteJson('/api/users/' . $userId);
        
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'User deleted successfully'
                 ]);
    }
    
    /**
     * Test evaluation scoring and leaderboard
     */
    public function test_evaluation_scoring_and_leaderboard()
    {
        // Step 1: Submit a successful solution
        $phpCode = '<?php echo "Hello, World!";';
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                         ->postJson('/api/submissions', [
                             'problem_id' => $this->problem->id,
                             'code' => $phpCode,
                             'language' => 'PHP'
                         ]);
        
        $response->assertStatus(201);
        
        // Step 2: Check user's score
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                         ->getJson('/api/users/' . $this->candidate->id . '/score');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'user_id',
                     'total_score',
                     'ranking'
                 ]);
        
        // Step 3: Check evaluation leaderboard
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->candidateToken)
                         ->getJson('/api/evaluations/' . $this->evaluation->id . '/leaderboard');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'evaluation_id',
                     'top_performers' => [
                         '*' => [
                             'user_id',
                             'name',
                             'score',
                             'problems_solved'
                         ]
                     ]
                 ]);
    }
}
