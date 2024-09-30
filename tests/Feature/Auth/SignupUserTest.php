<?php

namespace Tests\Feature\Auth;

use App\Enums\BusinessType;
use App\Models\User;
use Illuminate\Http\Response;
use Mockery;

beforeEach(function () {
    $this->signupEndpoint = 'api/v1/auth/signup';
});

test('user can sign up successfully with valid data', function () {
    $requestData = [
        'fullname' => fake()->name(),
        'businessType' => strtolower(BusinessType::VENDOR->value),
        'name' => fake()->company(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Password123!',
        'passwordConfirmation' => 'Password123!',
        'termsAndConditions' => true,
    ];

    $response = $this->postJson($this->signupEndpoint, $requestData);

    $response->assertStatus(Response::HTTP_CREATED)
        ->assertJson(
            fn ($json) => $json->where('status', 'success')
                ->where('message', 'Sign up successful. Please verify your email using the OTP sent.')
                ->where('data.name', $requestData['fullname'])
                ->where('data.businessName', $requestData['name'])
                ->where('data.email', $requestData['email'])
                ->has('accessToken')
        );
});

test('signup fails with missing or invalid data', function () {
    $requestData = [
        'fullname' => fake()->name(),
        'businessType' => 'invalid-type',
        'name' => '',
        'email' => 'invalid-email',
        'password' => 'short',
        'passwordConfirmation' => 'different',
        'termsAndConditions' => false,
    ];

    $response = $this->postJson($this->signupEndpoint, $requestData);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['businessType', 'name', 'email', 'password', 'passwordConfirmation', 'termsAndConditions']);
});

test('signup fails if user already exists', function () {
    $existingUser = User::factory()->create([
        'email' => 'existinguser@example.com',
    ]);

    $requestData = [
        'fullname' => fake()->name(),
        'businessType' => BusinessType::allowedForRegistration()[0],
        'name' => fake()->name(),
        'email' => 'existinguser@example.com',
        'password' => 'Password123!',
        'passwordConfirmation' => 'Password123!',
        'termsAndConditions' => true,
    ];

    $response = $this->postJson($this->signupEndpoint, $requestData);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['email']);
});

afterEach(function () {
    Mockery::close();
});
