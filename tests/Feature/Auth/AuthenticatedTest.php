<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AuthenticatedTest extends TestCase
{

    private $url = 'api/v1/auth/signin';
    private $user;
    private $password = 'password';
    private $email = 'admin@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::firstWhere([
            'email' => $this->email,
        ]);

    }

    /**
     * Test user sign in with valid credentials.
     */
    public function test_user_sign_in_with_valid_credentials(): void
    {
        $data = [
            'email' => $this->email,
            'password' => $this->password,
        ];

        $response = $this->postJson($this->url, $data);
        // dd($response);
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('tokenType', 'Bearer')
                    ->whereType('fullAccessToken', 'string')
                    ->whereType('expiresAt', 'string')
                    ->where('message', 'Sign in successful.')
                    ->has('data', fn ($json) =>
                        $json->where('user.email', $this->user['email'])
                            ->where('user.name', $this->user['name'])
                            ->whereType('user.createdAt', 'string')
                            ->whereType('user.updatedAt', 'string')
                            ->whereType('user.id', 'integer')
                    )
            );
    }

    /**
     * Test user sign in with invalid credentials.
     */
    public function test_user_sign_in_with_invalid_credentials(): void
    {
        $data = [
            'email' => $this->user['email'],
            'password' => 'wrongpassword',
        ];

        $this->postJson($this->url, $data)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('error', 'Unauthorized')
            );
    }

    /**
     * Test user logout.
     */
    public function test_user_logout(): void
    {
        $token = $this->user->createToken('Full Access Token', ['full'])->accessToken;

        $this->actingAs($this->user)->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('api/v1/auth/signout', [])
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Logged out successfully']);

        $this->assertDatabaseMissing('oauth_access_tokens', [
            'tokenable_id' => $this->user['id'],
            'tokenable_type' => 'App\Models\User',
            'revoked' => false,
        ]);
    }

    protected function tearDown(): void
    {
        if($this->user){
            $this->user->tokens()->delete();
        }
        parent::tearDown();
    }
}
