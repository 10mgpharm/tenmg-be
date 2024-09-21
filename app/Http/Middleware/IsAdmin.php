<?php

namespace App\Http\Middleware;

use App\Services\Interfaces\IAuthService;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function __construct(
        private IAuthService $authService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $business = $this->authService->getBusiness();

        if ($business->type != 'ADMIN') {
            throw new Exception('You are not authorized to perform this action', 401);
        }

        return $next($request);
    }
}
