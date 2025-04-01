<?php

namespace App\Services;

use App\Models\Problem;
use App\Models\Submission;
use App\Models\Result;
use Illuminate\Support\Facades\Log;

class CodeTestingService
{
    protected $executionService;
    
    public function __construct(CodeExecutionService $executionService)
    {
        $this->executionService = $executionService;
    }
    
    /**
     * Create test cases for a problem
     *
     * @param Problem $problem
     * @param array $testCases
     * @return bool
     */
    public function createTestCases(Problem $problem, array $testCases)
    {
        try {
            // Validate test cases format
            foreach ($testCases as $testCase) {
                if (!isset($testCase['input']) || !isset($testCase['output'])) {
                    return false;
                }
            }
            
            // Update problem with test cases
            $problem->test_cases = json_encode($testCases);
            $problem->save();
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error creating test cases: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Run tests on a submission
     *
     * @param Submission $submission
     * @return Result|null
     */
    public function runTests(Submission $submission)
    {
        try {
            // Get the problem
            $problem = Problem::find($submission->problem_id);
            if (!$problem) {
                return null;
            }
            
            // Process the submission
            return $this->executionService->processSubmission($submission);
        } catch (\Exception $e) {
            Log::error('Error running tests: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Validate test cases for a problem
     *
     * @param Problem $problem
     * @param string $sampleSolution
     * @param string $language
     * @return array
     */
    public function validateTestCases(Problem $problem, string $sampleSolution, string $language)
    {
        try {
            // Create a temporary submission
            $submission = new Submission();
            $submission->user_id = 0; // System user
            $submission->problem_id = $problem->id;
            $submission->code = $sampleSolution;
            $submission->language = $language;
            $submission->status = 'pending';
            $submission->save();
            
            // Run the tests
            $result = $this->runTests($submission);
            
            // Check if all tests passed
            $allTestsPassed = $result && $result->success;
            
            // Get test results
            $testResults = [];
            if ($result && $result->test_results) {
                $testResults = json_decode($result->test_results, true);
            }
            
            // Clean up the temporary submission
            $submission->delete();
            if ($result) {
                $result->delete();
            }
            
            return [
                'valid' => $allTestsPassed,
                'test_results' => $testResults
            ];
        } catch (\Exception $e) {
            Log::error('Error validating test cases: ' . $e->getMessage());
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate test cases for a problem based on sample solution
     *
     * @param Problem $problem
     * @param string $sampleSolution
     * @param string $language
     * @param array $inputs
     * @return array
     */
    public function generateTestCases(Problem $problem, string $sampleSolution, string $language, array $inputs)
    {
        try {
            $testCases = [];
            
            // Create a temporary submission
            $submission = new Submission();
            $submission->user_id = 0; // System user
            $submission->problem_id = $problem->id;
            $submission->code = $sampleSolution;
            $submission->language = $language;
            $submission->status = 'pending';
            $submission->save();
            
            // For each input, run the sample solution to get expected output
            foreach ($inputs as $input) {
                // Create a temporary file with the input
                $tempDir = storage_path('app/temp/' . $submission->id);
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }
                
                $inputFile = $tempDir . '/input.txt';
                file_put_contents($inputFile, $input);
                
                // Get the file extension
                $extension = $this->getFileExtension($language);
                $codeFile = $tempDir . '/code.' . $extension;
                file_put_contents($codeFile, $sampleSolution);
                
                // Execute the code
                $output = '';
                $error = '';
                
                switch ($language) {
                    case 'C':
                        $executableFile = $tempDir . '/program';
                        $compileCommand = "gcc {$codeFile} -o {$executableFile} 2>&1";
                        exec($compileCommand, $compileOutput, $compileReturnVar);
                        
                        if ($compileReturnVar === 0) {
                            $command = "{$executableFile} < {$inputFile} 2>&1";
                            exec($command, $execOutput, $execReturnVar);
                            if ($execReturnVar === 0) {
                                $output = implode("\n", $execOutput);
                            } else {
                                $error = 'Execution error';
                            }
                        } else {
                            $error = 'Compilation error';
                        }
                        break;
                        
                    case 'JavaScript':
                        $command = "node {$codeFile} < {$inputFile} 2>&1";
                        exec($command, $execOutput, $execReturnVar);
                        if ($execReturnVar === 0) {
                            $output = implode("\n", $execOutput);
                        } else {
                            $error = 'Execution error';
                        }
                        break;
                        
                    case 'PHP':
                        $command = "php {$codeFile} < {$inputFile} 2>&1";
                        exec($command, $execOutput, $execReturnVar);
                        if ($execReturnVar === 0) {
                            $output = implode("\n", $execOutput);
                        } else {
                            $error = 'Execution error';
                        }
                        break;
                }
                
                // Add to test cases if successful
                if (empty($error)) {
                    $testCases[] = [
                        'input' => $input,
                        'output' => $output
                    ];
                }
            }
            
            // Clean up
            $this->cleanUp($tempDir);
            $submission->delete();
            
            return $testCases;
        } catch (\Exception $e) {
            Log::error('Error generating test cases: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get the file extension for a language
     *
     * @param string $language
     * @return string
     */
    protected function getFileExtension($language)
    {
        switch ($language) {
            case 'C':
                return 'c';
            case 'JavaScript':
                return 'js';
            case 'PHP':
                return 'php';
            default:
                return 'txt';
        }
    }
    
    /**
     * Clean up temporary files
     *
     * @param string $dir
     * @return void
     */
    protected function cleanUp($dir)
    {
        // In a real system, you would remove temporary files
        // For development, we'll keep them for debugging
    }
}
