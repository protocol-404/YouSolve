<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JWTService;

class JWTAuthentication
{
    protected $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {

        $token = $request->bearerToken();


        if (!$token) {
            return response()->json(['message' => 'Unauthorized - No token provided'], 401);
        }


        $user = $this->jwtService->getUserFromToken($token);


        if (!$user) {
            return response()->json(['message' => 'Unauthorized - Invalid token'], 401);
        }


        if (!$user->is_active) {
            return response()->json(['message' => 'Your account is deactivated. Please contact administrator.'], 403);
        }


        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
