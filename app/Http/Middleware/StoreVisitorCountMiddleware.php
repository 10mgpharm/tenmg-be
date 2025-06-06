<?php

namespace App\Http\Middleware;

use App\Models\StoreVisitorCount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreVisitorCountMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $user_id = $request->user() ? $request->user()->id : null;
        $unique_key = $user_id ? "user_{$user_id}_ip_{$ip}" : "ip_{$ip}";

        $date = now()->toDateString();
        $cache_key = "visitor_recorded_{$date}_{$unique_key}";

        if (!cache()->has($cache_key)) {

            $expiresAt = now()->endOfDay();
            cache()->put($cache_key, true, $expiresAt);

            $visitorRecord = StoreVisitorCount::firstOrCreate(
                ['date' => $date],
                ['count' => 0]
            );

            $visitorRecord->increment('count');
        }

        return $next($request);
    }
}
