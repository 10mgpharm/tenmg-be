<?php

namespace Tests\Feature\Auth;

use App\Enums\OtpType;
use App\Enums\StatusEnum;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    $this->verifyEmailEndpoint = route('auth.verification.verify');
});

describe('Email Verification Tests', function () {
    test('authenticated user can verify email with valid OTP', function () {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'email_verified_at' => null,
            'status' => StatusEnum::ACTIVE->value,
        ]);

        // Create OTP for email verification
        $otp = Otp::factory()->create([
            'user_id' => $user->id,
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
            'code' => '123456',
        ]);

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'otp' => '123456',
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->verifyEmailEndpoint, $data);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->has('status')
                    ->where('message', 'User verified')
                    ->has('data', fn ($data) => $data
                        ->has('emailVerifiedAt')
                    )
            );

        // Assert email was verified
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    });

    test('verify email requires authentication', function () {
        $data = [
            'otp' => '123456',
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ];

        $response = $this->postJson($this->verifyEmailEndpoint, $data);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

    test('verify email fails with invalid OTP', function () {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'email_verified_at' => null,
        ]);

        // Create OTP for email verification
        Otp::factory()->create([
            'user_id' => $user->id,
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
            'code' => '123456',
        ]);

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'otp' => '999999', // Invalid OTP
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->verifyEmailEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['otp']);

        // Assert email was not verified
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    });

    test('verify email fails with OTP for different user', function () {
        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
            'email_verified_at' => null,
        ]);

        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
            'email_verified_at' => null,
        ]);

        // Create OTP for user2
        Otp::factory()->create([
            'user_id' => $user2->id,
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
            'code' => '123456',
        ]);

        $tokenResult = $user1->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'otp' => '123456', // OTP belongs to user2
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->verifyEmailEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['otp']);
    });

    test('verify email requires OTP field', function () {
        $user = User::factory()->create();

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->verifyEmailEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['otp']);
    });

    test('verify email requires type field', function () {
        $user = User::factory()->create();

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'otp' => '123456',
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->verifyEmailEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['type']);
    });

    test('verify email requires valid OTP type', function () {
        $user = User::factory()->create();

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'otp' => '123456',
            'type' => 'invalid_type',
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->verifyEmailEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['type']);
    });

    test('verify email requires 6-digit OTP', function () {
        $user = User::factory()->create();

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'otp' => '12345', // 5 digits
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->verifyEmailEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['otp']);
    });

    test('user can verify email using password reset OTP type', function () {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'email_verified_at' => null,
            'status' => StatusEnum::ACTIVE->value,
        ]);

        // Create OTP for password reset (which can also verify email)
        $otp = Otp::factory()->create([
            'user_id' => $user->id,
            'type' => OtpType::RESET_PASSWORD_VERIFICATION->value,
            'code' => '654321',
        ]);

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'otp' => '654321',
            'type' => OtpType::RESET_PASSWORD_VERIFICATION->value,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->verifyEmailEndpoint, $data);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->has('status')
                    ->has('message')
                    ->has('data')
            );

        // Assert email was verified
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    });
});
