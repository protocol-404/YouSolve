<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassModel;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;

class ClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ClassModel::with(['trainer', 'candidates']);

        if ($request->user()->isTrainer()) {
            $query->where('trainer_id', $request->user()->id);
        } elseif ($request->user()->isCandidate()) {
            $query->whereHas('candidates', function($q) use ($request) {
                $q->where('users.id', $request->user()->id);
            });
        }


        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $classes = $query->paginate(15);

        return response()->json($classes);
    }

    /**
     * Store a newly created resource
     */
    public function store(Request $request)
    {

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trainer_id' => 'required|exists:users,id',
            'is_active' => 'boolean',
            'candidate_ids' => 'array',
            'candidate_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $trainer = User::find($request->trainer_id);
        if (!$trainer || !$trainer->isTrainer()) {
            return response()->json(['message' => 'Invalid trainer ID'], 422);
        }


        if ($request->has('candidate_ids') && !empty($request->candidate_ids)) {
            $candidateRole = Role::where('name', 'candidate')->first();
            $invalidCandidates = User::whereIn('id', $request->candidate_ids)
                                    ->where('role_id', '!=', $candidateRole->id)
                                    ->count();

            if ($invalidCandidates > 0) {
                return response()->json(['message' => 'Some user IDs are not candidates'], 422);
            }
        }

        $class = ClassModel::create([
            'name' => $request->name,
            'description' => $request->description,
            'trainer_id' => $request->trainer_id,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);


        if ($request->has('candidate_ids') && !empty($request->candidate_ids)) {
            $class->candidates()->attach($request->candidate_ids);
        }

        return response()->json([
            'message' => 'Class created successfully',
            'class' => $class->load(['trainer', 'candidates'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $class = ClassModel::with(['trainer', 'candidates'])->find($id);

        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }


        if (!$this->canViewClass($request->user(), $class)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($class);
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, string $id)
    {
        $class = ClassModel::find($id);

        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }


        if (!$this->canUpdateClass($request->user(), $class)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'trainer_id' => 'sometimes|exists:users,id',
            'is_active' => 'sometimes|boolean',
            'candidate_ids' => 'sometimes|array',
            'candidate_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        if ($request->has('trainer_id')) {
            $trainer = User::find($request->trainer_id);
            if (!$trainer || !$trainer->isTrainer()) {
                return response()->json(['message' => 'Invalid trainer ID'], 422);
            }
        }


        if ($request->has('candidate_ids') && !empty($request->candidate_ids)) {
            $candidateRole = Role::where('name', 'candidate')->first();
            $invalidCandidates = User::whereIn('id', $request->candidate_ids)
                                    ->where('role_id', '!=', $candidateRole->id)
                                    ->count();

            if ($invalidCandidates > 0) {
                return response()->json(['message' => 'Some user IDs are not candidates'], 422);
            }
        }


        if ($request->has('name')) $class->name = $request->name;
        if ($request->has('description')) $class->description = $request->description;


        if ($request->user()->isAdmin()) {
            if ($request->has('trainer_id')) $class->trainer_id = $request->trainer_id;
            if ($request->has('is_active')) $class->is_active = $request->is_active;
        }

        $class->save();


        if ($request->has('candidate_ids')) {
            $class->candidates()->sync($request->candidate_ids);
        }

        return response()->json([
            'message' => 'Class updated successfully',
            'class' => $class->load(['trainer', 'candidates'])
        ]);
    }

    /**
     * Remove the specified resource from
     */
    public function destroy(Request $request, string $id)
    {

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $class = ClassModel::find($id);

        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }


        $class->delete();

        return response()->json([
            'message' => 'Class deleted successfully'
        ]);
    }

    /**
     * Check if the authenticated user can view the class
     */
    private function canViewClass($user, $class): bool
    {

        if ($user->isAdmin()) {
            return true;
        }


        if ($user->isTrainer() && $class->trainer_id == $user->id) {
            return true;
        }


        if ($user->isCandidate()) {
            return $class->candidates()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Check if the authenticated user can update the class
     */
    private function canUpdateClass($user, $class): bool
    {

        if ($user->isAdmin()) {
            return true;
        }


        if ($user->isTrainer() && $class->trainer_id == $user->id) {
            return true;
        }

        return false;
    }
}
