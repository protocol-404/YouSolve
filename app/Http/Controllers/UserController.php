<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Only administrators and instructors can list all users
        if (!auth()->user()->hasRole('administrator') && !auth()->user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $query = User::query();
        
        // Filter by role if provided
        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }
        
        $users = $query->with('role')->get();
        
        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Only administrators can create users
        if (!auth()->user()->hasRole('administrator')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);
        
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        // Users can view their own profile, administrators and instructors can view any profile
        if ($user->id !== auth()->id() && 
            !auth()->user()->hasRole('administrator') && 
            !auth()->user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $user->load('role');
        
        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Users can update their own profile, administrators can update any profile
        if ($user->id !== auth()->id() && !auth()->user()->hasRole('administrator')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:8',
            'role_id' => 'sometimes|required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Only administrators can change roles
        if ($request->has('role_id') && !auth()->user()->hasRole('administrator')) {
            return response()->json([
                'message' => 'Unauthorized to change role'
            ], 403);
        }

        // Update user data
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        
        if ($request->has('role_id') && auth()->user()->hasRole('administrator')) {
            $user->role_id = $request->role_id;
        }
        
        $user->save();
        
        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Only administrators can delete users
        if (!auth()->user()->hasRole('administrator')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Cannot delete your own account'
            ], 400);
        }
        
        $user->delete();
        
        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
    
    /**
     * Get user progress and statistics
     */
    public function progress(Request $request, User $user = null)
    {
        // If no user is specified, use the authenticated user
        if (!$user) {
            $user = $request->user();
        } else {
            // Check if authorized to view this user's progress
            if ($user->id !== auth()->id() && 
                !auth()->user()->hasRole('administrator') && 
                !auth()->user()->hasRole('instructor')) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }
        }
        
        // Get user submissions with results
        $submissions = $user->submissions()->with('result', 'problem')->get();
        
        // Calculate statistics
        $totalSubmissions = $submissions->count();
        $successfulSubmissions = $submissions->filter(function ($submission) {
            return $submission->result && $submission->result->success;
        })->count();
        
        // Group submissions by problem language
        $submissionsByLanguage = $submissions->groupBy('problem.language')->map(function ($items) {
            return $items->count();
        });
        
        // Group submissions by problem difficulty
        $submissionsByDifficulty = $submissions->groupBy('problem.difficulty')->map(function ($items) {
            return $items->count();
        });
        
        return response()->json([
            'user_id' => $user->id,
            'total_submissions' => $totalSubmissions,
            'successful_submissions' => $successfulSubmissions,
            'success_rate' => $totalSubmissions > 0 ? ($successfulSubmissions / $totalSubmissions) * 100 : 0,
            'submissions_by_language' => $submissionsByLanguage,
            'submissions_by_difficulty' => $submissionsByDifficulty,
            'recent_submissions' => $submissions->take(5)
        ]);
    }
}
