<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\Problem;
use App\Services\CodeSubmissionService;
use App\Services\CodeTestingService;
use App\Services\EvaluationScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CodeExecutionController extends Controller
{
    protected $submissionService;
    protected $testingService;
    protected $scoringService;
    
    public function __construct(
        CodeSubmissionService $submissionService,
        CodeTestingService $testingService,
        EvaluationScoringService $scoringService
    ) {
        $this->submissionService = $submissionService;
        $this->testingService = $testingService;
        $this->scoringService = $scoringService;
    }
    
    /**
     * Submit code for execution
     */
    public function submitCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'problem_id' => 'required|exists:problems,id',
            'code' => 'required|string',
            'language' => 'required|in:C,JavaScript,PHP',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if problem exists
        $problem = Problem::find($request->problem_id);
        if (!$problem) {
            return response()->json([
                'message' => 'Problem not found'
            ], 404);
        }

        // Create submission
        $submission = new Submission();
        $submission->user_id = Auth::id();
        $submission->problem_id = $request->problem_id;
        $submission->code = $request->code;
        $submission->language = $request->language;
        $submission->status = 'pending';
        $submission->save();
        
        // Process submission
        $success = $this->submissionService->submit($submission);
        
        if (!$success) {
            return response()->json([
                'message' => 'Failed to process submission',
                'submission' => $submission
            ], 500);
        }
        
        // Reload submission with result
        $submission->refresh();
        $submission->load('result');
        
        // Calculate score if submission was successful
        if ($submission->result && $submission->result->success) {
            $score = $this->scoringService->calculateScore($submission->result);
            
            // Update user's total score
            $this->scoringService->updateUserTotalScore(Auth::user());
        }
        
        return response()->json([
            'message' => 'Submission processed successfully',
            'submission' => $submission,
            'result' => $submission->result
        ], 200);
    }
    
    /**
     * Get submission details
     */
    public function getSubmission($id)
    {
        $submission = Submission::with('result', 'problem')->find($id);
        
        if (!$submission) {
            return response()->json([
                'message' => 'Submission not found'
            ], 404);
        }
        
        // Check if user is authorized to view this submission
        if ($submission->user_id !== Auth::id() && 
            !Auth::user()->hasRole('administrator') && 
            !Auth::user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        return response()->json([
            'submission' => $submission
        ], 200);
    }
    
    /**
     * Create test cases for a problem (admin/instructor only)
     */
    public function createTestCases(Request $request, $problemId)
    {
        // Check if user is authorized
        if (!Auth::user()->hasRole('administrator') && !Auth::user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'test_cases' => 'required|array',
            'test_cases.*.input' => 'required|string',
            'test_cases.*.output' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get the problem
        $problem = Problem::find($problemId);
        if (!$problem) {
            return response()->json([
                'message' => 'Problem not found'
            ], 404);
        }
        
        // Create test cases
        $success = $this->testingService->createTestCases($problem, $request->test_cases);
        
        if (!$success) {
            return response()->json([
                'message' => 'Failed to create test cases'
            ], 500);
        }
        
        return response()->json([
            'message' => 'Test cases created successfully',
            'problem' => $problem
        ], 200);
    }
    
    /**
     * Validate test cases with a sample solution (admin/instructor only)
     */
    public function validateTestCases(Request $request, $problemId)
    {
        // Check if user is authorized
        if (!Auth::user()->hasRole('administrator') && !Auth::user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'sample_solution' => 'required|string',
            'language' => 'required|in:C,JavaScript,PHP',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get the problem
        $problem = Problem::find($problemId);
        if (!$problem) {
            return response()->json([
                'message' => 'Problem not found'
            ], 404);
        }
        
        // Validate test cases
        $result = $this->testingService->validateTestCases(
            $problem,
            $request->sample_solution,
            $request->language
        );
        
        return response()->json([
            'message' => $result['valid'] ? 'Test cases are valid' : 'Test cases are invalid',
            'validation_result' => $result
        ], 200);
    }
    
    /**
     * Generate test cases from a sample solution (admin/instructor only)
     */
    public function generateTestCases(Request $request, $problemId)
    {
        // Check if user is authorized
        if (!Auth::user()->hasRole('administrator') && !Auth::user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'sample_solution' => 'required|string',
            'language' => 'required|in:C,JavaScript,PHP',
            'inputs' => 'required|array',
            'inputs.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get the problem
        $problem = Problem::find($problemId);
        if (!$problem) {
            return response()->json([
                'message' => 'Problem not found'
            ], 404);
        }
        
        // Generate test cases
        $testCases = $this->testingService->generateTestCases(
            $problem,
            $request->sample_solution,
            $request->language,
            $request->inputs
        );
        
        if (empty($testCases)) {
            return response()->json([
                'message' => 'Failed to generate test cases'
            ], 500);
        }
        
        return response()->json([
            'message' => 'Test cases generated successfully',
            'test_cases' => $testCases
        ], 200);
    }
}
