<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // If no specific roles are required, just proceed
        if (empty($roles)) {
            return $next($request);
        }

        // Check if the user has any of the required roles
        foreach ($roles as $role) {
            if ($request->user()->hasRole($role)) {
                return $next($request);
            }
        }

        // If user doesn't have any of the required roles
        return response()->json([
            'message' => 'Unauthorized. You do not have the necessary permissions.'
        ], 403);
    }
}
