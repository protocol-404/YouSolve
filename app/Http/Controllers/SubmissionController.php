<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\Problem;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Submission::query();
        
        // Filter by user if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        // Filter by problem if provided
        if ($request->has('problem_id')) {
            $query->where('problem_id', $request->problem_id);
        }
        
        // Filter by language if provided
        if ($request->has('language')) {
            $query->where('language', $request->language);
        }
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Get current user's submissions if requested
        if ($request->has('my_submissions') && $request->my_submissions) {
            $query->where('user_id', $request->user()->id);
        }
        
        $submissions = $query->with('result')->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'submissions' => $submissions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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
        $submission->user_id = $request->user()->id;
        $submission->problem_id = $request->problem_id;
        $submission->code = $request->code;
        $submission->language = $request->language;
        $submission->status = 'pending';
        $submission->save();
        
        // In a real implementation, we would queue the submission for processing
        // For now, we'll just create a placeholder result
        $result = new Result();
        $result->submission_id = $submission->id;
        $result->success = false;
        $result->output = 'Submission queued for processing';
        $result->save();
        
        // Update submission status
        $submission->status = 'completed';
        $submission->save();
        
        return response()->json([
            'message' => 'Submission created successfully',
            'submission' => $submission,
            'result' => $result
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Submission $submission)
    {
        // Check if user is authorized to view this submission
        if ($submission->user_id !== auth()->id() && 
            !auth()->user()->hasRole('administrator') && 
            !auth()->user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $submission->load('result');
        
        return response()->json([
            'submission' => $submission
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Submission $submission)
    {
        // Only administrators can update submissions
        if (!auth()->user()->hasRole('administrator')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|in:pending,running,completed,failed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $submission->update($request->only('status'));
        
        return response()->json([
            'message' => 'Submission updated successfully',
            'submission' => $submission
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Submission $submission)
    {
        // Only administrators or the submission owner can delete submissions
        if ($submission->user_id !== auth()->id() && !auth()->user()->hasRole('administrator')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $submission->delete();
        
        return response()->json([
            'message' => 'Submission deleted successfully'
        ]);
    }
}
