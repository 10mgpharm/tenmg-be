<?php

namespace Tests\Feature\Auth;

use App\Enums\BusinessType;
use App\Enums\StatusEnum;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    $this->googleAuthEndpoint = route('auth.google.signin');
});

describe('Google OAuth Tests', function () {
    test('existing user can sign in with Google', function () {
        $user = User::factory()->create([
            'email' => 'googleuser@example.com',
            'status' => StatusEnum::ACTIVE->value,
            'email_verified_at' => now(),
        ]);

        $role = Role::where('name', 'vendor')->first();
        $user->assignRole($role);

        $business = Business::factory()->create([
            'owner_id' => $user->id,
            'type' => BusinessType::VENDOR->value,
        ]);

        $data = [
            'email' => $user->email,
            'name' => 'Google User',
            'provider' => 'google',
            'picture' => 'https://example.com/avatar.jpg',
        ];

        $response = $this->postJson($this->googleAuthEndpoint, $data);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('status', 'success')
                    ->has('accessToken')
                    ->has('data')
            );
    });

    test('new user can sign up with Google', function () {
        $data = [
            'email' => 'newgoogleuser@example.com',
            'name' => 'New Google User',
            'provider' => 'google',
            'picture' => 'https://example.com/avatar.jpg',
        ];

        $response = $this->postJson($this->googleAuthEndpoint, $data);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('status', 'success')
                    ->has('accessToken')
                    ->has('data')
            );

        // Assert user was created
        $this->assertDatabaseHas('users', [
            'email' => 'newgoogleuser@example.com',
            'name' => 'New Google User',
        ]);

        $user = User::where('email', 'newgoogleuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at); // Google users are auto-verified
    });

    test('Google auth requires email field', function () {
        $data = [
            'name' => 'Test User',
            'provider' => 'google',
        ];

        $response = $this->postJson($this->googleAuthEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['email']);
    });

    test('Google auth requires name field', function () {
        $data = [
            'email' => 'test@example.com',
            'provider' => 'google',
        ];

        $response = $this->postJson($this->googleAuthEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['name']);
    });

    test('Google auth requires provider field', function () {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        $response = $this->postJson($this->googleAuthEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['provider']);
    });

    test('Google auth picture is optional', function () {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'provider' => 'google',
        ];

        $response = $this->postJson($this->googleAuthEndpoint, $data);

        $response->assertStatus(Response::HTTP_CREATED);

        // Assert user was created without picture
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    });

    test('Google auth saves picture URL when provided', function () {
        $pictureUrl = 'https://example.com/avatar.jpg';

        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'provider' => 'google',
            'picture' => $pictureUrl,
        ];

        $response = $this->postJson($this->googleAuthEndpoint, $data);

        $response->assertStatus(Response::HTTP_CREATED);

        // Assert user was created with picture
        $user = User::where('email', 'test@example.com')->first();
        $this->assertEquals($pictureUrl, $user->google_picture_url);
    });
});
