<?php

namespace Tests\Feature\Auth;

use App\Enums\BusinessType;
use App\Enums\OtpType;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery;

beforeEach(function () {
    $this->signupEndpoint = route('auth.signup');
    Notification::fake();
});

describe('Successful Signup Tests', function () {
    test('user can sign up successfully as VENDOR with valid data', function () {
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
                fn (AssertableJson $json) => $json
                    ->where('status', 'success')
                    ->where('message', 'Sign up successful. Please verify your email using the OTP sent.')
                    ->has('accessToken', fn ($token) => $token
                        ->has('token')
                        ->where('tokenType', 'bearer')
                        ->has('expiresAt')
                    )
                    ->has('data', fn ($data) => $data
                        ->where('name', $requestData['fullname'])
                        ->where('businessName', $requestData['name'])
                        ->where('email', $requestData['email'])
                        ->where('emailVerifiedAt', null)
                        ->whereType('id', 'integer')
                        ->whereType('active', 'boolean')
                        ->whereType('useTwoFactor', 'string')
                        ->has('avatar')
                        ->has('owner')
                        ->has('entityType')
                        ->has('role')
                        ->has('businessStatus')
                        ->has('completeProfile')
                    )
            );

        // Assert user was created in database
        $this->assertDatabaseHas('users', [
            'email' => $requestData['email'],
            'name' => $requestData['fullname'],
        ]);

        // Assert business was created
        $user = User::where('email', $requestData['email'])->first();
        $this->assertDatabaseHas('businesses', [
            'name' => $requestData['name'],
            'owner_id' => $user->id,
            'type' => BusinessType::VENDOR->value,
        ]);

        // Assert business_user relationship was created
        $business = Business::where('name', $requestData['name'])->first();
        $this->assertDatabaseHas('business_users', [
            'user_id' => $user->id,
            'business_id' => $business->id,
        ]);

        // Assert user has vendor role
        expect($user->hasRole('vendor'))->toBeTrue();

        // Assert OTP was created for email verification
        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ]);
    });

    test('user can sign up successfully as SUPPLIER', function () {
        $requestData = [
            'fullname' => fake()->name(),
            'businessType' => strtolower(BusinessType::SUPPLIER->value),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'termsAndConditions' => true,
        ];

        $response = $this->postJson($this->signupEndpoint, $requestData);

        $response->assertStatus(Response::HTTP_CREATED);

        $user = User::where('email', $requestData['email'])->first();
        expect($user->hasRole('supplier'))->toBeTrue();

        $business = Business::where('owner_id', $user->id)->first();
        expect($business->type)->toBe(BusinessType::SUPPLIER->value);
    });

    test('user can sign up successfully as LENDER', function () {
        $requestData = [
            'fullname' => fake()->name(),
            'businessType' => strtolower(BusinessType::LENDER->value),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'termsAndConditions' => true,
        ];

        $response = $this->postJson($this->signupEndpoint, $requestData);

        $response->assertStatus(Response::HTTP_CREATED);

        $user = User::where('email', $requestData['email'])->first();
        expect($user->hasRole('lender'))->toBeTrue();

        $business = Business::where('owner_id', $user->id)->first();
        expect($business->type)->toBe(BusinessType::LENDER->value);
    });

    test('user can sign up successfully as CUSTOMER_PHARMACY', function () {
        $requestData = [
            'fullname' => fake()->name(),
            'businessType' => strtolower(BusinessType::CUSTOMER_PHARMACY->value),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'termsAndConditions' => true,
        ];

        $response = $this->postJson($this->signupEndpoint, $requestData);

        $response->assertStatus(Response::HTTP_CREATED);

        $user = User::where('email', $requestData['email'])->first();
        expect($user->hasRole('customer'))->toBeTrue();

        $business = Business::where('owner_id', $user->id)->first();
        expect($business->type)->toBe(BusinessType::CUSTOMER_PHARMACY->value);
    });

    test('signup creates access token that can be used for authentication', function () {
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
        $responseData = $response->json();

        expect($responseData)->toHaveKey('accessToken.token');
        expect($responseData['accessToken']['token'])->not()->toBeEmpty();
        expect($responseData['accessToken']['tokenType'])->toBe('bearer');
    });
});

describe('Validation Error Tests', function () {
    test('signup fails with missing required fields', function () {
        $response = $this->postJson($this->signupEndpoint, []);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors([
                'fullname',
                'businessType',
                'name',
                'email',
                'password',
                'passwordConfirmation',
                'termsAndConditions',
            ]);
    });

    test('signup fails with invalid business type', function () {
        $requestData = [
            'fullname' => fake()->name(),
            'businessType' => 'invalid-type',
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'termsAndConditions' => true,
        ];

        $response = $this->postJson($this->signupEndpoint, $requestData);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['businessType']);
    });

    test('signup fails with invalid email format', function () {
        $requestData = [
            'fullname' => fake()->name(),
            'businessType' => strtolower(BusinessType::VENDOR->value),
            'name' => fake()->company(),
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'termsAndConditions' => true,
        ];

        $response = $this->postJson($this->signupEndpoint, $requestData);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['email']);
    });

    test('signup fails with weak password', function () {
        $requestData = [
            'fullname' => fake()->name(),
            'businessType' => strtolower(BusinessType::VENDOR->value),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'short',
            'passwordConfirmation' => 'short',
            'termsAndConditions' => true,
        ];

        $response = $this->postJson($this->signupEndpoint, $requestData);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['password']);
    });

    test('signup fails when password confirmation does not match', function () {
        $requestData = [
            'fullname' => fake()->name(),
            'businessType' => strtolower(BusinessType::VENDOR->value),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
            'passwordConfirmation' => 'DifferentPassword123!',
            'termsAndConditions' => true,
        ];

        $response = $this->postJson($this->signupEndpoint, $requestData);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['passwordConfirmation']);
    });

    test('signup fails when terms and conditions not accepted', function () {
        $requestData = [
            'fullname' => fake()->name(),
            'businessType' => strtolower(BusinessType::VENDOR->value),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'termsAndConditions' => false,
        ];

        $response = $this->postJson($this->signupEndpoint, $requestData);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['termsAndConditions']);
    });

    test('signup fails if email already exists', function () {
        $existingUser = User::factory()->create([
            'email' => 'existinguser@example.com',
        ]);

        $requestData = [
            'fullname' => fake()->name(),
            'businessType' => strtolower(BusinessType::VENDOR->value),
            'name' => fake()->company(),
            'email' => 'existinguser@example.com',
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'termsAndConditions' => true,
        ];

        $response = $this->postJson($this->signupEndpoint, $requestData);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['email']);
    });

    test('signup fails if business name already exists', function () {
        $existingUser = User::factory()->create();
        $existingBusiness = Business::create([
            'owner_id' => $existingUser->id,
            'name' => 'Existing Business',
            'short_name' => 'EXIST',
            'code' => 'EXIST',
            'type' => BusinessType::VENDOR->value,
            'status' => 'PENDING_VERIFICATION',
        ]);

        $requestData = [
            'fullname' => fake()->name(),
            'businessType' => strtolower(BusinessType::VENDOR->value),
            'name' => 'Existing Business',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
            'passwordConfirmation' => 'Password123!',
            'termsAndConditions' => true,
        ];

        $response = $this->postJson($this->signupEndpoint, $requestData);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['name']);
    });
});

afterEach(function () {
    Mockery::close();
});
