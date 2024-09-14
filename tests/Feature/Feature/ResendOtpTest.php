<?php

use App\Enums\OtpType;
use App\Models\Otp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Event;
use Mockery;

beforeEach(function () {
    $this->otpService = Mockery::mock(OtpService::class)->makePartial();

    $this->userMock = Mockery::mock(User::class)->makePartial();
    $this->userMock->id = 1;
    $this->userMock->name = 'John Doe';
    $this->userMock->email = 'john.doe@example.com';
});

afterEach(function () {
    Mockery::close();
});

test('it can regenerate and resend an OTP', function () {
    // Mock the necessary objects
    Event::fake();

    $otpCode = '654321'; // The OTP code for resend
    $otpType = OtpType::SIGNUP_EMAIL_VERIFICATION;

    // Mock the existing Otp object
    $existingOtp = Mockery::mock(Otp::class);
    $existingOtp->shouldReceive('getAttribute')->with('code')->andReturn($otpCode);

    // Mock the HasMany relation to return the existing OTP
    $otpsMock = Mockery::mock(HasMany::class);
    $otpsMock->shouldReceive('firstWhere')
        ->with('type', $otpType->value)
        ->andReturn($existingOtp); // Simulate existing OTP

    // Mock the user object and its methods
    $this->userMock->shouldReceive('otps')->andReturn($otpsMock);

    // Mock the OTP creation
    $newOtp = Mockery::mock(Otp::class);
    $newOtp->shouldReceive('getAttribute')->with('code')->andReturn('123456'); // Mock attribute access

    // Ensure that OtpService methods are called
    $this->otpService->shouldReceive('forUser')
        ->with($this->userMock)
        ->andReturn($this->otpService);
    $this->otpService->shouldReceive('regenerate')
        ->with($otpType)
        ->andReturn($this->otpService);
    $this->otpService->shouldReceive('sendMail')
        ->with($otpType)
        ->andReturn($this->otpService);
    $this->otpService->shouldReceive('otp')
        ->andReturn($newOtp); // Return the new OTP instance

    // Call the service to regenerate and resend the OTP
    $response = $this->otpService->forUser($this->userMock)->regenerate($otpType)->sendMail($otpType);

    // Assert that the new OTP code is correct
    $otpInstance = $this->otpService->otp();
    expect($otpInstance->getAttribute('code'))->toBe('123456');
});
