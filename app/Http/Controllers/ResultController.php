<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResultController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Result::query();
        
        // Filter by submission if provided
        if ($request->has('submission_id')) {
            $query->where('submission_id', $request->submission_id);
        }
        
        // Get results for current user's submissions if requested
        if ($request->has('my_results') && $request->my_results) {
            $query->whereHas('submission', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }
        
        $results = $query->with('submission')->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'results' => $results
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Only administrators and instructors can create results directly
        if (!auth()->user()->hasRole('administrator') && !auth()->user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'submission_id' => 'required|exists:submissions,id',
            'success' => 'required|boolean',
            'execution_time' => 'nullable|integer',
            'memory_usage' => 'nullable|integer',
            'output' => 'nullable|string',
            'error_message' => 'nullable|string',
            'test_results' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if submission exists
        $submission = Submission::find($request->submission_id);
        if (!$submission) {
            return response()->json([
                'message' => 'Submission not found'
            ], 404);
        }

        // Check if result already exists for this submission
        $existingResult = Result::where('submission_id', $request->submission_id)->first();
        if ($existingResult) {
            return response()->json([
                'message' => 'Result already exists for this submission',
                'result' => $existingResult
            ], 409);
        }

        $result = Result::create($request->all());
        
        // Update submission status
        $submission->status = $request->success ? 'completed' : 'failed';
        $submission->save();
        
        return response()->json([
            'message' => 'Result created successfully',
            'result' => $result
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Result $result)
    {
        // Check if user is authorized to view this result
        $submission = Submission::find($result->submission_id);
        if (!$submission) {
            return response()->json([
                'message' => 'Submission not found'
            ], 404);
        }
        
        if ($submission->user_id !== auth()->id() && 
            !auth()->user()->hasRole('administrator') && 
            !auth()->user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        return response()->json([
            'result' => $result
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Result $result)
    {
        // Only administrators and instructors can update results
        if (!auth()->user()->hasRole('administrator') && !auth()->user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'success' => 'sometimes|required|boolean',
            'execution_time' => 'nullable|integer',
            'memory_usage' => 'nullable|integer',
            'output' => 'nullable|string',
            'error_message' => 'nullable|string',
            'test_results' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result->update($request->all());
        
        // Update submission status if success status changed
        if ($request->has('success')) {
            $submission = Submission::find($result->submission_id);
            if ($submission) {
                $submission->status = $request->success ? 'completed' : 'failed';
                $submission->save();
            }
        }
        
        return response()->json([
            'message' => 'Result updated successfully',
            'result' => $result
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Result $result)
    {
        // Only administrators can delete results
        if (!auth()->user()->hasRole('administrator')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $result->delete();
        
        return response()->json([
            'message' => 'Result deleted successfully'
        ]);
    }
}
