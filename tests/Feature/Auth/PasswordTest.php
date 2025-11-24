<?php

namespace Tests\Feature\Auth;

use App\Enums\OtpType;
use App\Enums\StatusEnum;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    $this->forgotPasswordEndpoint = route('auth.password.forgot');
    $this->resetPasswordEndpoint = route('auth.password.reset');
    Notification::fake();
});

describe('Forgot Password Tests', function () {
    test('user can request password reset with valid email', function () {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'status' => StatusEnum::ACTIVE->value,
        ]);

        $data = [
            'email' => $user->email,
        ];

        $response = $this->postJson($this->forgotPasswordEndpoint, $data);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('status', 'success')
                    ->where('message', 'A one-time password has been sent to your registered email')
                    ->has('accessToken', fn ($token) => $token
                        ->has('token')
                        ->where('tokenType', 'bearer')
                        ->has('expiresAt')
                    )
                    ->has('data', fn ($data) => $data
                        ->where('email', $user->email)
                        ->has('id')
                    )
            );

        // Assert OTP was created
        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'type' => OtpType::RESET_PASSWORD_VERIFICATION->value,
        ]);
    });

    test('forgot password requires email to exist in database', function () {
        $data = [
            'email' => 'nonexistent@example.com',
        ];

        $response = $this->postJson($this->forgotPasswordEndpoint, $data);

        // The validation rule requires the email to exist
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['email']);
    });

    test('forgot password requires email field', function () {
        $response = $this->postJson($this->forgotPasswordEndpoint, []);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['email']);
    });

    test('forgot password requires valid email format', function () {
        $data = [
            'email' => 'invalid-email',
        ];

        $response = $this->postJson($this->forgotPasswordEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['email']);
    });
});

describe('Reset Password Tests', function () {
    test('authenticated user can reset password with valid OTP', function () {
        $oldPassword = 'OldPassword123!';
        $newPassword = 'NewPassword123!';

        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => Hash::make($oldPassword),
            'status' => StatusEnum::ACTIVE->value,
        ]);

        // Create OTP for password reset
        $otp = Otp::factory()->create([
            'user_id' => $user->id,
            'type' => OtpType::RESET_PASSWORD_VERIFICATION->value,
            'code' => '123456',
        ]);

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'otp' => '123456',
            'password' => $newPassword,
            'passwordConfirmation' => $newPassword,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->resetPasswordEndpoint, $data);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->has('status')
                    ->has('message')
            );

        // Assert password was changed
        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Hash::check($oldPassword, $user->password));

        // Assert token was revoked after password reset
        $this->assertDatabaseMissing('oauth_access_tokens', [
            'id' => $tokenResult->token->id,
            'revoked' => false,
        ]);
    });

    test('reset password requires authentication', function () {
        $data = [
            'otp' => '123456',
            'password' => 'NewPassword123!',
            'passwordConfirmation' => 'NewPassword123!',
        ];

        $response = $this->postJson($this->resetPasswordEndpoint, $data);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

    test('reset password requires valid password', function () {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'otp' => '123456',
            'password' => 'weak', // Too weak
            'passwordConfirmation' => 'weak',
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->resetPasswordEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['password']);
    });

    test('reset password requires password confirmation match', function () {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'otp' => '123456',
            'password' => 'NewPassword123!',
            'passwordConfirmation' => 'DifferentPassword123!',
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->resetPasswordEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['passwordConfirmation']); // Already camelCase in rule
    });

    test('reset password requires all fields', function () {
        $user = User::factory()->create();

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->resetPasswordEndpoint, []);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['password', 'passwordConfirmation']);
    });
});
