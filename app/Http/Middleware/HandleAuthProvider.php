<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class HandleAuthProvider
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Check if the user is already authenticated
            if (Auth::check()) {
                return $next($request);
            }

            $provider = $request->input('provider', 'credentials');
            $email = $request->input('email');

            if ($provider === 'google') {
                $accessToken = $request->bearerToken();

                // Mock response in local environment
                if (config('app.env') == 'local') {
                    $data = [
                        "id" => '104589841658088651577',
                        "name" => fake()->words(3, true),
                        "email" => $request->input('email'),
                        "image" => 'https://lh3.googleusercontent.com/a/ACg8ocKQzrqJEUdaq9348uAPTLahOiukt7hFsEQwj8opc-6N21XbopUF=s96-c'
                    ];
                } else {
                    // Call the Google API to verify the token
                    $response = Http::withHeaders([
                        'Authorization' => "Bearer $accessToken",
                    ])->get(env('GOOGLE_OAUTH2_URL'));

                    if ($response->failed()) {
                        return response()->json(['message' => 'Failed to authenticate with Google.'], Response::HTTP_UNAUTHORIZED);
                    }

                    $data = $response->json();
                }

                // Check if the user email matches the email from Google
                if ($data['email'] !== $email) {
                    return response()->json(['message' => 'Email does not match.'], Response::HTTP_FORBIDDEN);
                }

                // Find the user
                $user = User::firstWhere('email', $email);

                // Attach user to the request
                if($user){
                    $request->setUserResolver(fn () => $user);
                }

            } elseif ($provider === 'credentials') {
                // If using credentials, ensure the user is authenticated
                if (!Auth::check()) {
                    return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
                }
            } else {
                return response()->json(['message' => 'Invalid provider.'], Response::HTTP_BAD_REQUEST);
            }
            
            $request->merge(['name' => $data['name']]);

            return $next($request);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
