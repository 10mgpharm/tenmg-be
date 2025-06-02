<?php

namespace App\Http\Middleware\Integration;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorEcommerceTransactionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-Public-Key') !== config('services.tenmg.public')) {
            return response()->json(['error' => 'Unauthorized. Invalid public key.'], 401);
        }

        return $next($request);
    }
}
