<?php

namespace App\Http\Middleware;

use App\Models\ApiCallLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiCall
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = null;
        $status = 'success';
        $exceptionMessage = null;

        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

            $response = $next($request);
            $content = $response->getContent();

            // Decode JSON response into an array
            $data = json_decode($content, true);

            if(isset($data['success'])) {
                $status = 'failed';
            }

            ApiCallLog::create([
                'business_id' => $business_id,
                'route' => $request->path(),
                'status' => $status,
                'response' => $content,
            ]);

        return $response;

    }
}
