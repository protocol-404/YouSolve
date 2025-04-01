<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureSessionIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Session expired or invalid'
            ], 401);
        }

        // Check if token is still valid (not expired)
        if ($request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->doesntExist()) {
            return response()->json([
                'message' => 'Session expired or invalid'
            ], 401);
        }

        return $next($request);
    }
}
