<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProblemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Problem::query();
        
        // Filter by evaluation if provided
        if ($request->has('evaluation_id')) {
            $query->where('evaluation_id', $request->evaluation_id);
        }
        
        // Filter by difficulty if provided
        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }
        
        // Filter by active status if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        $problems = $query->get();
        
        return response()->json([
            'problems' => $problems
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'evaluation_id' => 'required|exists:evaluations,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'example_input' => 'nullable|string',
            'example_output' => 'nullable|string',
            'constraints' => 'nullable|string',
            'difficulty' => 'required|in:easy,medium,hard',
            'time_limit' => 'nullable|integer',
            'memory_limit' => 'nullable|integer',
            'test_cases' => 'nullable|json',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if evaluation exists
        $evaluation = Evaluation::find($request->evaluation_id);
        if (!$evaluation) {
            return response()->json([
                'message' => 'Evaluation not found'
            ], 404);
        }

        $problem = Problem::create($request->all());
        
        return response()->json([
            'message' => 'Problem created successfully',
            'problem' => $problem
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Problem $problem)
    {
        return response()->json([
            'problem' => $problem
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Problem $problem)
    {
        $validator = Validator::make($request->all(), [
            'evaluation_id' => 'sometimes|required|exists:evaluations,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'example_input' => 'nullable|string',
            'example_output' => 'nullable|string',
            'constraints' => 'nullable|string',
            'difficulty' => 'sometimes|required|in:easy,medium,hard',
            'time_limit' => 'nullable|integer',
            'memory_limit' => 'nullable|integer',
            'test_cases' => 'nullable|json',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $problem->update($request->all());
        
        return response()->json([
            'message' => 'Problem updated successfully',
            'problem' => $problem
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Problem $problem)
    {
        $problem->delete();
        
        return response()->json([
            'message' => 'Problem deleted successfully'
        ]);
    }
}
