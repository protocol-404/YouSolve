<?php

namespace App\Services;

use App\Models\Submission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class CodeSubmissionService
{
    protected $executionService;
    
    public function __construct(CodeExecutionService $executionService)
    {
        $this->executionService = $executionService;
    }
    
    /**
     * Submit code for execution
     *
     * @param Submission $submission
     * @return void
     */
    public function submit(Submission $submission)
    {
        try {
            // Set initial status
            $submission->status = 'pending';
            $submission->save();
            
            // In a production environment, we would dispatch this to a queue
            // Queue::dispatch(new ProcessCodeSubmission($submission));
            
            // For development, we'll process it synchronously
            $this->process($submission);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Code submission error: ' . $e->getMessage());
            
            // Update submission status
            $submission->status = 'failed';
            $submission->save();
            
            return false;
        }
    }
    
    /**
     * Process a code submission
     *
     * @param Submission $submission
     * @return void
     */
    public function process(Submission $submission)
    {
        // Execute the code
        $this->executionService->processSubmission($submission);
    }
}
