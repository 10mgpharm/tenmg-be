<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class HandleAuthProvider
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Check if the user is already authenticated
            if (Auth::check()) {
                return $next($request);
            }

            $provider = $request->input('provider');
            $email = $request->input('email');

            if ($provider === 'google') {
                $accessToken = $request->bearerToken();

                // Mock response in local environment
                if (config('app.env') == 'local') {
                    $data = [
                        "id" => '104589841658088651577',
                        "name" => fake()->words(3, true),
                        "email" => $request->input('email'),
                        "picture" => 'https://lh3.googleusercontent.com/a/ACg8ocKQzrqJEUdaq9348uAPTLahOiukt7hFsEQwj8opc-6N21XbopUF=s96-c'
                    ];
                } else {
                    // Call the Google API to verify the token
                    $response = Http::withHeaders([
                        'Authorization' => "Bearer $accessToken",
                    ])->get(config('services.google.oauth_url'));

                    if ($response->failed()) {
                        return response()->json(['message' => 'Failed to authenticate with Google. Please try again'], Response::HTTP_UNAUTHORIZED);
                    }

                    $data = $response->json();
                }
                // Check if the request email matches the email from Google
                if ($data['email'] !== $email) {
                    return response()->json(['message' => 'Provider Email does not match.'], Response::HTTP_FORBIDDEN);
                }
            } else {
                return response()->json(['message' => 'Invalid provider.'], Response::HTTP_BAD_REQUEST);
            }

            // merge google response to request
            $request->merge([
                'name' => $data['name'],
                'picture' => $data['picture']
            ]);
            
            return $next($request);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
