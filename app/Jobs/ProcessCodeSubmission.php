<?php

namespace App\Jobs;

use App\Models\Submission;
use App\Services\CodeExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCodeSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $submission;

    /**
     * Create a new job instance.
     *
     * @param Submission $submission
     * @return void
     */
    public function __construct(Submission $submission)
    {
        $this->submission = $submission;
    }

    /**
     * Execute the job.
     *
     * @param CodeExecutionService $executionService
     * @return void
     */
    public function handle(CodeExecutionService $executionService)
    {
        try {
            // Process the submission
            $executionService->processSubmission($this->submission);
        } catch (\Exception $e) {
            Log::error('Code execution job failed: ' . $e->getMessage());
            
            // Update submission status
            $this->submission->status = 'failed';
            $this->submission->save();
            
            // Create a failed result
            $this->createFailedResult($this->submission, 'Job processing error: ' . $e->getMessage());
            
            // Fail the job
            $this->fail($e);
        }
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
        $result = new \App\Models\Result();
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
