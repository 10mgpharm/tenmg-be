<?php

namespace Tests\Feature\Auth;

use App\Enums\OtpType;
use App\Helpers\UtilityHelper;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    $this->forgot = 'api/v1/auth/forgot-password';
    $this->reset = 'api/v1/auth/reset-password';

    $this->email = fake()->email();
    $this->password = fake()->password(8);

    $this->user = User::factory()->create([
        'email' => $this->email,
        'password' => Hash::make($this->password),
    ]);
});

test('forgot password with valid email', function () {
    Notification::fake();

    $response = $this->postJson($this->forgot, ['email' => $this->email]);

    $response->assertStatus(Response::HTTP_OK)
        ->assertJson(
            fn (AssertableJson $json) => $json->where('message', 'A one-time password has been sent to your registered email')
                ->where('status', 'success')
                ->where('data', null)
        );

    Notification::assertSentTo(
        $this->user,
        \App\Notifications\Auth\ResetPasswordNotification::class
    );

    $this->assertDatabaseHas('otps', [
        'user_id' => $this->user->id,
        'type' => OtpType::RESET_PASSWORD_VERIFICATION->value,
    ]);
});

test('forgot password with invalid email', function () {
    $response = $this->postJson($this->forgot, ['email' => fake()->email()]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJson(
            fn (AssertableJson $json) => $json->where('message', 'The selected email is invalid.')
                ->has('errors')
                ->where('errors.email.0', 'The selected email is invalid.')
        );

    $this->assertDatabaseMissing('otps', [
        'user_id' => $this->user->id,
        'type' => OtpType::RESET_PASSWORD_VERIFICATION->value,
    ]);
});

test('reset password with valid data', function () {
    $otp = $this->user->otps()->create([
        'code' => UtilityHelper::generateOtp(),
        'type' => OtpType::RESET_PASSWORD_VERIFICATION->value,
    ]);

    $response = $this->postJson($this->reset, [
        'email' => $this->email,
        'otp' => $otp->code,
        'password' => 'newpassword123',
        'passwordConfirmation' => 'newpassword123',
    ]);

    $response->assertStatus(Response::HTTP_OK)
        ->assertJson(
            fn (AssertableJson $json) => $json->where('message', 'Your password has been reset.')
                ->where('status', 'success')
                ->has('data')
        );

    expect(Hash::check('newpassword123', $this->user->fresh()->password))->toBeTrue();
    $this->assertDatabaseMissing('otps', ['code' => $otp->code, 'type' => OtpType::RESET_PASSWORD_VERIFICATION->value]);
});

test('reset password with invalid otp', function () {
    $response = $this->postJson($this->reset, [
        'email' => $this->email,
        'otp' => 'invalidotp',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJson(
            fn (AssertableJson $json) => $json->where('message', __('The OTP provided is incorrect or has expired. Please try again.'))
                ->has('errors')
                ->where('errors.otp.0', __('The OTP provided is incorrect or has expired. Please try again.'))
        );
});

test('reset password with non-existent email', function () {
    $response = $this->postJson($this->reset, [
        'email' => 'nonexistent@example.com',
        'otp' => 'anyotp',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJson(
            fn (AssertableJson $json) => $json->where('message', __('Invalid email or otp is incorrect. Please try again.'))
                ->has('errors')
                ->where('errors.otp.0', __('Invalid email or otp is incorrect. Please try again.'))
        );
});

afterEach(function () {
    if ($this->user) {
        $this->user->delete();
    }
});
