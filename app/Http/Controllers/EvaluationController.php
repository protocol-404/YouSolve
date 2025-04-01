<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvaluationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $evaluations = Evaluation::all();
        
        return response()->json([
            'evaluations' => $evaluations
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'language' => 'required|in:C,JavaScript,PHP',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $evaluation = Evaluation::create($request->all());
        
        return response()->json([
            'message' => 'Evaluation created successfully',
            'evaluation' => $evaluation
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Evaluation $evaluation)
    {
        return response()->json([
            'evaluation' => $evaluation
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Evaluation $evaluation)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'language' => 'sometimes|required|in:C,JavaScript,PHP',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $evaluation->update($request->all());
        
        return response()->json([
            'message' => 'Evaluation updated successfully',
            'evaluation' => $evaluation
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Evaluation $evaluation)
    {
        $evaluation->delete();
        
        return response()->json([
            'message' => 'Evaluation deleted successfully'
        ]);
    }
}
