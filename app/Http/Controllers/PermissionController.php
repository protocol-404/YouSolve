<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Get all available roles
     *
     * @return \Illuminate\Http\Response
     */
    public function getRoles()
    {
        $roles = Role::all();
        
        return response()->json([
            'roles' => $roles
        ]);
    }

    /**
     * Check if user has specific role
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $role
     * @return \Illuminate\Http\Response
     */
    public function checkRole(Request $request, $role)
    {
        $hasRole = $request->user()->hasRole($role);
        
        return response()->json([
            'has_role' => $hasRole
        ]);
    }

    /**
     * Get permissions for current user based on role
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getUserPermissions(Request $request)
    {
        $user = $request->user();
        $role = $user->role;
        
        // Define permissions based on role
        $permissions = [];
        
        switch ($role->name) {
            case 'administrator':
                $permissions = [
                    'users' => ['view', 'create', 'edit', 'delete'],
                    'roles' => ['view', 'create', 'edit', 'delete'],
                    'evaluations' => ['view', 'create', 'edit', 'delete'],
                    'problems' => ['view', 'create', 'edit', 'delete'],
                    'submissions' => ['view', 'create', 'edit', 'delete'],
                    'results' => ['view', 'create', 'edit', 'delete'],
                ];
                break;
                
            case 'instructor':
                $permissions = [
                    'users' => ['view'],
                    'roles' => ['view'],
                    'evaluations' => ['view', 'create', 'edit'],
                    'problems' => ['view', 'create', 'edit'],
                    'submissions' => ['view'],
                    'results' => ['view', 'create'],
                ];
                break;
                
            case 'candidate':
                $permissions = [
                    'evaluations' => ['view'],
                    'problems' => ['view'],
                    'submissions' => ['view', 'create'],
                    'results' => ['view'],
                ];
                break;
        }
        
        return response()->json([
            'role' => $role->name,
            'permissions' => $permissions
        ]);
    }
}
