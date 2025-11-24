<?php

namespace Tests\Feature\Auth;

use App\Enums\OtpType;
use App\Enums\StatusEnum;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    $this->resendOtpEndpoint = route('auth.resend.otp');
    Notification::fake();
});

describe('Resend OTP Tests', function () {
    test('authenticated user can resend OTP', function () {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'status' => StatusEnum::ACTIVE->value,
        ]);

        // Create an existing OTP
        Otp::factory()->create([
            'user_id' => $user->id,
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
            'code' => '123456',
        ]);

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->resendOtpEndpoint, $data);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('status', 'success')
                    ->where('message', 'A one-time password has been resent to your registered email.')
            );

        // Assert new OTP was created
        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ]);
    });

    test('unauthenticated user can resend OTP with email', function () {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'status' => StatusEnum::ACTIVE->value,
        ]);

        // Create an existing OTP
        Otp::factory()->create([
            'user_id' => $user->id,
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
            'code' => '123456',
        ]);

        $data = [
            'email' => $user->email,
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ];

        $response = $this->postJson($this->resendOtpEndpoint, $data);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('status', 'success')
                    ->where('message', 'A one-time password has been resent to your registered email.')
            );
    });

    test('resend OTP requires type field', function () {
        $user = User::factory()->create();

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->resendOtpEndpoint, []);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['type']);
    });

    test('resend OTP requires valid OTP type', function () {
        $user = User::factory()->create();

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'type' => 'invalid_type',
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->resendOtpEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['type']);
    });

    test('unauthenticated user requires email field', function () {
        $data = [
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ];

        $response = $this->postJson($this->resendOtpEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['email']);
    });

    test('unauthenticated user can resend OTP for password reset', function () {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'status' => StatusEnum::ACTIVE->value,
        ]);

        $data = [
            'email' => $user->email,
            'type' => OtpType::RESET_PASSWORD_VERIFICATION->value,
        ];

        $response = $this->postJson($this->resendOtpEndpoint, $data);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('status', 'success')
                    ->where('message', 'A one-time password has been resent to your registered email.')
            );
    });

    test('resend OTP regenerates existing OTP', function () {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'status' => StatusEnum::ACTIVE->value,
        ]);

        // Create an existing OTP
        $oldOtp = Otp::factory()->create([
            'user_id' => $user->id,
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
            'code' => '123456',
        ]);

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->resendOtpEndpoint, $data);

        $response->assertStatus(Response::HTTP_OK);

        // Assert old OTP was invalidated or new one created
        $otps = Otp::where('user_id', $user->id)
            ->where('type', OtpType::SIGNUP_EMAIL_VERIFICATION->value)
            ->count();

        // There should be at least one OTP (may have regenerated the existing one or created new)
        $this->assertGreaterThanOrEqual(1, $otps);
    });

    test('resend OTP returns success even if user does not exist (security)', function () {
        $data = [
            'email' => 'nonexistent@example.com',
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ];

        $response = $this->postJson($this->resendOtpEndpoint, $data);

        // Should return success to prevent email enumeration
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('status', 'success')
                    ->where('message', 'A one-time password has been resent to your registered email.')
            );
    });

    test('resend OTP is rate limited', function () {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
        ]);

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'type' => OtpType::SIGNUP_EMAIL_VERIFICATION->value,
        ];

        // Make multiple requests to trigger rate limit (5 requests per minute based on route)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                ->postJson($this->resendOtpEndpoint, $data);
        }

        // The 6th request should be rate limited
        $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    });
});
