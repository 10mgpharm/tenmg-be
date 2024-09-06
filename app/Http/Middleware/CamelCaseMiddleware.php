<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CamelCaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true); // Get the response data as an array
            $data = $this->convertKeysToCamelCase($data);
            $response->setData($data); // Set the transformed data back to the response
        }

        return $response;
    }

    /**
     * Recursively convert array keys to camelCase.
     */
    private function convertKeysToCamelCase(array $array): array
    {
        $camelCasedArray = [];

        foreach ($array as $key => $value) {
            $newKey = Str::camel($key);

            // If the value is an array, recursively apply camel case
            if (is_array($value)) {
                $value = $this->convertKeysToCamelCase($value);
            }

            $camelCasedArray[$newKey] = $value;
        }

        return $camelCasedArray;
    }
}
