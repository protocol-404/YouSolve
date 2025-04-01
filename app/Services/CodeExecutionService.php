<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\Result;
use App\Models\Problem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CodeExecutionService
{
    protected $supportedLanguages = ['C', 'JavaScript', 'PHP'];
    protected $tempDir;
    
    public function __construct()
    {
        $this->tempDir = storage_path('app/temp');
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }
    
    /**
     * Process a code submission
     *
     * @param Submission $submission
     * @return Result
     */
    public function processSubmission(Submission $submission)
    {
        // Update submission status
        $submission->status = 'running';
        $submission->save();
        
        // Get the problem
        $problem = Problem::find($submission->problem_id);
        if (!$problem) {
            return $this->createFailedResult($submission, 'Problem not found');
        }
        
        // Check if language is supported
        if (!in_array($submission->language, $this->supportedLanguages)) {
            return $this->createFailedResult($submission, 'Unsupported language');
        }
        
        try {
            // Create a unique directory for this submission
            $submissionDir = $this->tempDir . '/' . $submission->id;
            if (!file_exists($submissionDir)) {
                mkdir($submissionDir, 0755, true);
            }
            
            // Save the code to a file
            $codeFile = $this->saveCodeToFile($submission, $submissionDir);
            
            // Parse test cases
            $testCases = $this->parseTestCases($problem->test_cases);
            
            // Execute the code
            $executionResults = $this->executeCode($submission, $codeFile, $testCases, $problem);
            
            // Clean up
            $this->cleanUp($submissionDir);
            
            // Create and return the result
            return $this->createResult($submission, $executionResults);
        } catch (\Exception $e) {
            Log::error('Code execution error: ' . $e->getMessage());
            return $this->createFailedResult($submission, 'Execution error: ' . $e->getMessage());
        }
    }
    
    /**
     * Save the code to a file
     *
     * @param Submission $submission
     * @param string $dir
     * @return string
     */
    protected function saveCodeToFile(Submission $submission, $dir)
    {
        $extension = $this->getFileExtension($submission->language);
        $filename = $dir . '/code.' . $extension;
        
        file_put_contents($filename, $submission->code);
        
        return $filename;
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
     * Parse test cases from JSON
     *
     * @param string $testCasesJson
     * @return array
     */
    protected function parseTestCases($testCasesJson)
    {
        if (empty($testCasesJson)) {
            // Default test case if none provided
            return [
                [
                    'input' => '',
                    'output' => ''
                ]
            ];
        }
        
        return json_decode($testCasesJson, true) ?: [];
    }
    
    /**
     * Execute the code
     *
     * @param Submission $submission
     * @param string $codeFile
     * @param array $testCases
     * @param Problem $problem
     * @return array
     */
    protected function executeCode(Submission $submission, $codeFile, $testCases, $problem)
    {
        $results = [
            'success' => true,
            'execution_time' => 0,
            'memory_usage' => 0,
            'output' => '',
            'error_message' => null,
            'test_results' => []
        ];
        
        $timeLimit = $problem->time_limit ?: 1000; // Default 1000ms
        $memoryLimit = $problem->memory_limit ?: 128; // Default 128MB
        
        foreach ($testCases as $index => $testCase) {
            $testResult = $this->executeTestCase(
                $submission->language,
                $codeFile,
                $testCase['input'],
                $testCase['output'],
                $timeLimit,
                $memoryLimit
            );
            
            $results['test_results'][] = array_merge($testResult, [
                'test_case' => $index + 1
            ]);
            
            // Update overall results
            $results['execution_time'] = max($results['execution_time'], $testResult['execution_time']);
            $results['memory_usage'] = max($results['memory_usage'], $testResult['memory_usage']);
            
            if (!$testResult['success']) {
                $results['success'] = false;
                if (empty($results['error_message']) && !empty($testResult['error_message'])) {
                    $results['error_message'] = $testResult['error_message'];
                }
            }
            
            // Append output
            if (!empty($testResult['output'])) {
                if (!empty($results['output'])) {
                    $results['output'] .= "\n";
                }
                $results['output'] .= "Test Case " . ($index + 1) . ":\n" . $testResult['output'];
            }
        }
        
        return $results;
    }
    
    /**
     * Execute a single test case
     *
     * @param string $language
     * @param string $codeFile
     * @param string $input
     * @param string $expectedOutput
     * @param int $timeLimit
     * @param int $memoryLimit
     * @return array
     */
    protected function executeTestCase($language, $codeFile, $input, $expectedOutput, $timeLimit, $memoryLimit)
    {
        $result = [
            'success' => false,
            'execution_time' => 0,
            'memory_usage' => 0,
            'output' => '',
            'error_message' => null
        ];
        
        $dir = dirname($codeFile);
        $inputFile = $dir . '/input.txt';
        file_put_contents($inputFile, $input);
        
        $startTime = microtime(true);
        
        switch ($language) {
            case 'C':
                $result = $this->executeC($codeFile, $inputFile, $dir, $timeLimit, $memoryLimit);
                break;
            case 'JavaScript':
                $result = $this->executeJavaScript($codeFile, $inputFile, $timeLimit, $memoryLimit);
                break;
            case 'PHP':
                $result = $this->executePHP($codeFile, $inputFile, $timeLimit, $memoryLimit);
                break;
        }
        
        $endTime = microtime(true);
        $result['execution_time'] = round(($endTime - $startTime) * 1000); // Convert to ms
        
        // Check if output matches expected output
        if (empty($result['error_message'])) {
            $normalizedOutput = $this->normalizeOutput($result['output']);
            $normalizedExpected = $this->normalizeOutput($expectedOutput);
            
            $result['success'] = ($normalizedOutput === $normalizedExpected);
            
            if (!$result['success'] && empty($result['error_message'])) {
                $result['error_message'] = 'Output does not match expected output';
            }
        }
        
        return $result;
    }
    
    /**
     * Execute C code
     *
     * @param string $codeFile
     * @param string $inputFile
     * @param string $dir
     * @param int $timeLimit
     * @param int $memoryLimit
     * @return array
     */
    protected function executeC($codeFile, $inputFile, $dir, $timeLimit, $memoryLimit)
    {
        $result = [
            'success' => false,
            'execution_time' => 0,
            'memory_usage' => 0,
            'output' => '',
            'error_message' => null
        ];
        
        // Compile the code
        $executableFile = $dir . '/program';
        $compileCommand = "gcc {$codeFile} -o {$executableFile} 2>&1";
        
        exec($compileCommand, $compileOutput, $compileReturnVar);
        
        if ($compileReturnVar !== 0) {
            $result['error_message'] = 'Compilation error: ' . implode("\n", $compileOutput);
            return $result;
        }
        
        // Execute the compiled program
        $command = "ulimit -t " . ceil($timeLimit / 1000) . "; " .
                   "ulimit -v " . ($memoryLimit * 1024) . "; " .
                   "{$executableFile} < {$inputFile} 2>&1";
        
        exec($command, $output, $returnVar);
        
        $result['output'] = implode("\n", $output);
        $result['success'] = ($returnVar === 0);
        
        if ($returnVar !== 0) {
            $result['error_message'] = 'Runtime error (exit code: ' . $returnVar . ')';
        }
        
        // Get memory usage (approximate)
        $result['memory_usage'] = $this->getMemoryUsage($executableFile);
        
        return $result;
    }
    
    /**
     * Execute JavaScript code
     *
     * @param string $codeFile
     * @param string $inputFile
     * @param int $timeLimit
     * @param int $memoryLimit
     * @return array
     */
    protected function executeJavaScript($codeFile, $inputFile, $timeLimit, $memoryLimit)
    {
        $result = [
            'success' => false,
            'execution_time' => 0,
            'memory_usage' => 0,
            'output' => '',
            'error_message' => null
        ];
        
        // Execute with Node.js
        $command = "ulimit -t " . ceil($timeLimit / 1000) . "; " .
                   "ulimit -v " . ($memoryLimit * 1024) . "; " .
                   "node {$codeFile} < {$inputFile} 2>&1";
        
        exec($command, $output, $returnVar);
        
        $result['output'] = implode("\n", $output);
        $result['success'] = ($returnVar === 0);
        
        if ($returnVar !== 0) {
            $result['error_message'] = 'Runtime error (exit code: ' . $returnVar . ')';
        }
        
        // Get memory usage (approximate)
        $result['memory_usage'] = 0; // Not easily measurable for Node.js
        
        return $result;
    }
    
    /**
     * Execute PHP code
     *
     * @param string $codeFile
     * @param string $inputFile
     * @param int $timeLimit
     * @param int $memoryLimit
     * @return array
     */
    protected function executePHP($codeFile, $inputFile, $timeLimit, $memoryLimit)
    {
        $result = [
            'success' => false,
            'execution_time' => 0,
            'memory_usage' => 0,
            'output' => '',
            'error_message' => null
        ];
        
        // Execute with PHP CLI
        $command = "ulimit -t " . ceil($timeLimit / 1000) . "; " .
                   "ulimit -v " . ($memoryLimit * 1024) . "; " .
                   "php {$codeFile} < {$inputFile} 2>&1";
        
        exec($command, $output, $returnVar);
        
        $result['output'] = implode("\n", $output);
        $result['success'] = ($returnVar === 0);
        
        if ($returnVar !== 0) {
            $result['error_message'] = 'Runtime error (exit code: ' . $returnVar . ')';
        }
        
        // Get memory usage (approximate)
        $result['memory_usage'] = 0; // Not easily measurable for PHP CLI
        
        return $result;
    }
    
    /**
     * Get memory usage of a process
     *
     * @param string $executableFile
     * @return int
     */
    protected function getMemoryUsage($executableFile)
    {
        // This is a simplified approach, in a real system you would use more sophisticated methods
        return 0; // Placeholder
    }
    
    /**
     * Normalize output for comparison
     *
     * @param string $output
     * @return string
     */
    protected function normalizeOutput($output)
    {
        // Trim whitespace, normalize line endings
        $output = trim($output);
        $output = str_replace("\r\n", "\n", $output);
        $output = str_replace("\r", "\n", $output);
        
        return $output;
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
    
    /**
     * Create a result record for a successful execution
     *
     * @param Submission $submission
     * @param array $executionResults
     * @return Result
     */
    protected function createResult(Submission $submission, $executionResults)
    {
        // Update submission status
        $submission->status = $executionResults['success'] ? 'completed' : 'failed';
        $submission->save();
        
        // Create result
        $result = new Result();
        $result->submission_id = $submission->id;
        $result->success = $executionResults['success'];
        $result->execution_time = $executionResults['execution_time'];
        $result->memory_usage = $executionResults['memory_usage'];
        $result->output = $executionResults['output'];
        $result->error_message = $executionResults['error_message'];
        $result->test_results = json_encode($executionResults['test_results']);
        $result->save();
        
        return $result;
    }
    
    /**
     * Create a result record for a failed execution
     *
     * @param Submission $submission
     * @param string $errorMessage
     * @return Result
     */
    protected function createFailedResult(Submission $submission, $errorMessage)
    {
        // Update submission status
        $submission->status = 'failed';
        $submission->save();
        
        // Create result
        $result = new Result();
        $result->submission_id = $submission->id;
        $result->success = false;
        $result->execution_time = 0;
        $result->memory_usage = 0;
        $result->output = '';
        $result->error_message = $errorMessage;
        $result->test_results = json_encode([]);
        $result->save();
        
        return $result;
    }
}
