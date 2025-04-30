<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\JWTService;

class AuthController extends Controller
{
    protected $jwtService;

    /**
     * Create a new AuthController instance
     */
    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $candidateRole = Role::where('name', 'candidate')->first();

        if (!$candidateRole) {
            return response()->json(['message' => 'Candidate role not found'], 500);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'role_id' => $candidateRole->id,
            'is_active' => true,
        ]);


        $token = $this->jwtService->generateToken($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    /**
     * Login user && create token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $user = User::where('email', $request->email)->first();


        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }


        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account is deactivated. Please contact administrator.'
            ], 403);
        }


        $user->last_login_at = now();
        $user->save();


        $token = $this->jwtService->generateToken($user);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {



        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get the authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Refresh a token
     */
    public function refresh(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $newToken = $this->jwtService->refreshToken($token);

        if (!$newToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        return response()->json([
            'message' => 'Token refreshed successfully',
            'token' => $newToken,
            'token_type' => 'Bearer'
        ]);
    }
}
