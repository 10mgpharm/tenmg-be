<?php

namespace Tests\Unit\Services;

use App\Enums\BusinessType;
use App\Http\Requests\Auth\SignupUserRequest;
use App\Models\Otp;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\PersonalAccessTokenResult;
use Mockery;

beforeEach(function () {
    $this->authService = Mockery::mock(AuthService::class)->makePartial();
    $this->authService->shouldReceive('getUser')->andReturn(Mockery::mock(User::class)->makePartial());

    $this->userMock = Mockery::mock(User::class)->makePartial();
    $this->userMock->id = 1;
    $this->userMock->name = 'John Doe';
    $this->userMock->email = 'john.doe@example.com';
    $this->userMock->email_verified_at = now();

    $this->tokenResultMock = Mockery::mock(PersonalAccessTokenResult::class);
    $this->tokenResultMock->accessToken = 'token';
    $this->tokenResultMock->token = (object) ['expires_at' => now()->addHour()];
});

afterEach(function () {
    Mockery::close();
});

test('it can sign up a user', function () {
    $request = Mockery::mock(SignupUserRequest::class)->makePartial();
    $request->shouldReceive('offsetGet')
        ->with('name')->andReturn('John Doe')
        ->with('email')->andReturn('john.doe@example.com')
        ->with('password')->andReturn('password')
        ->with('businessType')->andReturn('supplier');

    $this->authService->shouldReceive('signUp')->with($request)->andReturn($this->userMock);

    $this->userMock->shouldReceive('createToken')->andReturn($this->tokenResultMock);
    $this->userMock->shouldReceive('sendEmailVerification')->with(Mockery::type('string'));

    $user = $this->authService->signUp($request);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->email)->toBe('john.doe@example.com');
});

test('it can verify a user email', function () {
    Event::fake();

    $otpCode = '123456';
    $otp = Mockery::mock(Otp::class)->makePartial();
    $otp->shouldReceive('created_at')->andReturn(now()->subMinutes(20));
    $otp->updated_at = now()->subMinutes(10);

    $otpsMock = Mockery::mock(HasMany::class);
    $otpsMock->shouldReceive('firstWhere')->with(['code' => $otpCode, 'type' => 'SIGNUP_EMAIL_VERIFICATION'])->andReturn($otp);

    $this->userMock->shouldReceive('otps')->andReturn($otpsMock);
    $this->userMock->shouldReceive('hasVerifiedEmail')->andReturn(false);
    $this->userMock->shouldReceive('markEmailAsVerified')->andReturn(true);

    $user = $this->authService->verifyUserEmail($this->userMock, $otpCode, 'SIGNUP_EMAIL_VERIFICATION');

    Event::assertDispatched(Verified::class);

    expect($user->email_verified_at)->toBe($this->userMock->email_verified_at);
});

test('it throws exception if OTP is invalid or expired', function () {
    Event::fake();

    $otpCode = '123456';
    $otpsMock = Mockery::mock(HasMany::class);
    $otpsMock->shouldReceive('firstWhere')->with(['code' => $otpCode, 'type' => 'SIGNUP_EMAIL_VERIFICATION'])->andReturn(null);

    $this->userMock->shouldReceive('otps')->andReturn($otpsMock);

    $this->expectException(ValidationException::class);

    $this->authService->verifyUserEmail($this->userMock, $otpCode, 'SIGNUP_EMAIL_VERIFICATION');
});

test('it can resolve the signup role based on business type', function () {
    $role = Mockery::mock(Role::class)->makePartial();

    $this->authService->shouldReceive('resolveSignupRole')->with(BusinessType::SUPPLIER)->andReturn($role);
    $resolvedRole = $this->authService->resolveSignupRole(BusinessType::SUPPLIER);

    expect($resolvedRole)->toBeInstanceOf(Role::class);
});
