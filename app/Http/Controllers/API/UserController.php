<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin() && !$request->user()->isTrainer()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = User::with('role');


        if ($request->has('role')) {
            $role = Role::where('name', $request->role)->first();
            if ($role) {
                $query->where('role_id', $role->id);
            }
        }


        if ($request->user()->isTrainer()) {
            $trainerClasses = $request->user()->trainedClasses()->pluck('id');
            $candidateRole = Role::where('name', 'candidate')->first();

            if ($candidateRole) {
                $query->where('role_id', $candidateRole->id)
                      ->whereHas('classes', function($q) use ($trainerClasses) {
                          $q->whereIn('classes.id', $trainerClasses);
                      });
            }
        }

        $users = $query->paginate(15);

        return response()->json($users);
    }

    /**
     * Only administrators
     */
    public function store(Request $request)
    {

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'role' => 'required|string|in:trainer,administrator,candidate',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $role = Role::where('name', $request->role)->first();

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Display the specified resource
     */
    public function show(Request $request, string $id)
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }


        if (!$this->canViewUser($request->user(), $user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($user);
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }


        if (!$this->canUpdateUser($request->user(), $user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'role' => 'sometimes|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('first_name')) $user->first_name = $request->first_name;
        if ($request->has('last_name')) $user->last_name = $request->last_name;


        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }


        if ($request->user()->isAdmin()) {
            if ($request->has('is_active')) $user->is_active = $request->is_active;

            if ($request->has('role')) {
                $role = Role::where('name', $request->role)->first();
                if ($role) {
                    $user->role_id = $role->id;
                }
            }
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Remove the specified resource
     */
    public function destroy(Request $request, string $id)
    {

        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }


        if ($request->user()->id == $user->id) {
            return response()->json(['message' => 'Cannot delete your own account'], 400);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Check if the authenticated user can view the target user
     */
    private function canViewUser(User $authUser, User $targetUser): bool
    {

        if ($authUser->isAdmin()) {
            return true;
        }


        if ($authUser->isTrainer()) {
            if ($authUser->id == $targetUser->id) {
                return true;
            }

            if ($targetUser->isCandidate()) {
                $trainerClasses = $authUser->trainedClasses()->pluck('id');
                $candidateClasses = $targetUser->classes()->pluck('id');

                return $candidateClasses->intersect($trainerClasses)->isNotEmpty();
            }

            return false;
        }


        return $authUser->id == $targetUser->id;
    }

    /**
     * Check if the authenticated user can update
     */
    private function canUpdateUser(User $authUser, User $targetUser): bool
    {

        if ($authUser->isAdmin()) {
            return true;
        }


        return $authUser->id == $targetUser->id;
    }
}
