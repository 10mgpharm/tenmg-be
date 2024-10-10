<?php

namespace Tests\Feature\Auth;

use App\Enums\OtpType;
use App\Helpers\UtilityHelper;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Mockery;

beforeEach(function () {
    $this->forgot = 'api/v1/auth/forgot-password';
    $this->reset = 'api/v1/auth/reset-password';

    $this->email = fake()->email();
    $this->password = fake()->password(8);

    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => $this->email,
        'password' => Hash::make($this->password),
    ]);
});

test('forgot password with valid email', function () {
    Notification::fake();

    $response = $this->postJson($this->forgot, ['email' => $this->email]);

    $responseData = $response->json();

    expect($response->status())->toBe(Response::HTTP_OK);

    expect($responseData)
        ->toHaveKey('message', 'A one-time password has been sent to your registered email')
        ->toHaveKey('status', 'success');

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

    $responseData = $response->json();

    expect($response->status())->toBe(Response::HTTP_BAD_REQUEST);

    expect($responseData)
        ->toHaveKey('message', 'Invalid parameters supplied.')
        ->toHaveKey('errors')
        ->toHaveKey('errors.email.0', 'The selected email is invalid.');

    // Assert the database does not have the OTP
    $this->assertDatabaseMissing('otps', [
        'user_id' => $this->user->id,
        'type' => OtpType::RESET_PASSWORD_VERIFICATION->value,
    ]);
});

// TODO: fix password test dependencies
// test('reset password with valid data', function () {
//     $otp = $this->user->otps()->create([
//         'code' => UtilityHelper::generateOtp(),
//         'type' => OtpType::RESET_PASSWORD_VERIFICATION->value,
//     ]);

//     // Mock the createToken method to return a fake token
//    $this->user = Mockery::mock($this->user)->makePartial();

//     $mockedToken = 'fake-access-token';
//     $this->user->shouldReceive('createToken')->andReturn((object)[
//         'accessToken' => $mockedToken,
//     ]);
//     // Create a mock token object
//     $mockToken = Mockery::mock(Token::class);
//     $mockToken->id = 'mock-token-id'; // Provide a mock ID for the access token
//     // Mock the createToken method to return the mock token
//     $this->user->shouldReceive('createToken')->andReturn((object)[
//         'accessToken' => $mockedToken,
//         'token' => $mockToken, // Return the mock token with the ID
//     ]);

//     Passport::actingAs($this->user, ['full']);

//     $response = $this->withHeaders(['Authorization' => "Bearer $mockedToken"])
//         ->postJson($this->reset, [
//             'password' => 'newpassword123',
//             'passwordConfirmation' => 'newpassword123',
//         ]);

//     $responseData = $response->json();

//     expect($response->status())->toBe(Response::HTTP_OK);

//     expect($responseData)
//         ->toHaveKey('message', 'Your password has been reset.')
//         ->toHaveKey('status', 'success');

//     expect(Hash::check('newpassword123', $this->user->fresh()->password))->toBeTrue();
//     $this->assertDatabaseMissing('otps', ['code' => $otp->code, 'type' => OtpType::RESET_PASSWORD_VERIFICATION->value]);
// });

// test('reset password with invalid otp', function () {
//     $response = $this->postJson($this->reset, [
//         'email' => $this->email,
//         'otp' => 'invalidotp',
//         'password' => 'newpassword123',
//         'password_confirmation' => 'newpassword123',
//     ]);

//     $responseData = $response->json();

//     expect($response->status())->toBe(Response::HTTP_BAD_REQUEST);

//     expect($responseData)
//         ->toHaveKey('message', 'Invalid parameters supplied.')
//         ->toHaveKey('errors')
//         ->toHaveKey('errors.otp.0', 'The OTP provided is incorrect or has expired. Please try again.');
// });

// test('reset password with non-existent email', function () {
//     $response = $this->postJson($this->reset, [
//         'email' => 'nonexistent@example.com',
//         'otp' => 'anyotp',
//         'password' => 'newpassword123',
//         'password_confirmation' => 'newpassword123',
//     ]);

//     $responseData = $response->json();

//     expect($response->status())->toBe(Response::HTTP_BAD_REQUEST);

//     expect($responseData)
//         ->toHaveKey('message', 'Invalid parameters supplied.')
//         ->toHaveKey('errors')
//         ->toHaveKey('errors.otp.0', 'Invalid email or otp is incorrect. Please try again.');
// });

afterEach(function () {
    if ($this->user) {
        $this->user->delete();
    }
});
