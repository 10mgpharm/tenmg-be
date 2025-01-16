<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $business = $user->ownerBusinessType ?: $user->businesses->first();

        Log::alert($user->ownerBusinessType);

        if (strtolower($business->type) !== strtolower($role)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: You do not have the required role.',
            ], 403);
        }

        return $next($request);
    }
}
