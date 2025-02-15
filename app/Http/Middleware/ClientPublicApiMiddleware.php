<?php

namespace App\Http\Middleware;

use App\Services\Interfaces\IClientService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientPublicApiMiddleware
{
    public function __construct(public IClientService $clientService) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $pbKey = $request->header('Public-Key');
        $scKey = $request->header('Secret-Key');

        if (! ($pbKey || $scKey)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorised to call this endpoint',
            ], 403);
        }

        // check if key is public key and verify
        $business = null;

        if ($pbKey) {
            $business = $this->clientService->verifyPublicKey($request->header('Public-Key'));
        }

        if ($scKey) {
            $business = $this->clientService->verifyPublicKey($request->header('Secret-Key'));
        }

        $request->merge([
            'source' => 'API',
            'type' => 'client',
            'business' => $business,
        ]);

        return $next($request);
    }
}
