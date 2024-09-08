<?php

namespace Tests\Feature\Auth;

use App\Enums\OtpType;
use App\Helpers\UtilityHelper;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    private $forgot = 'api/v1/auth/forgot-password';

    private $reset = 'api/v1/auth/reset-password';

    private $user = null;

    private $email = null;

    private $password = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->email = fake()->email();
        $this->password = fake()->password(8);

        $this->user = User::factory()->create([
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);
    }

    /**
     * Test sending a password reset link with a valid email.
     */
    public function test_forgot_password_with_valid_email(): void
    {
        Notification::fake();

        $response = $this->postJson($this->forgot, ['email' => $this->email]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('status', __('passwords.sent'))
            );

        Notification::assertSentTo(
            $this->user,
            \App\Notifications\Auth\ResetPasswordNotification::class
        );

        $this->assertDatabaseHas('otps', [
            'user_id' => $this->user->id,
            'type' => OtpType::RESET_PASSWORD_VERIFICATION,
        ]);
    }

    /**
     * Test sending a password reset link with an invalid email.
     */
    public function test_forgot_password_with_invalid_email(): void
    {
        $response = $this->postJson($this->forgot, ['email' => fake()->email()]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('status', __('passwords.sent'))
            );

        $this->assertDatabaseMissing('otps', [
            'user_id' => $this->user->id,
            'type' => OtpType::RESET_PASSWORD_VERIFICATION,
        ]);
    }

    /**
     * Test resetting password with a valid OTP and email.
     */
    public function test_reset_password_with_valid_data(): void
    {
        $otp = $this->user->otps()->create([
            'code' => UtilityHelper::generateOtp(),
            'type' => OtpType::RESET_PASSWORD_VERIFICATION,
        ]);

        $response = $this->postJson($this->reset, [
            'email' => $this->email,
            'otp' => $otp->code,
            'password' => 'newpassword123',
            'passwordConfirmation' => 'newpassword123',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('status', __('passwords.reset'))
            );

        $this->assertTrue(Hash::check('newpassword123', $this->user->fresh()->password));
        $this->assertDatabaseMissing('otps', ['code' => $otp->code, 'type' => OtpType::RESET_PASSWORD_VERIFICATION]);
    }

    /**
     * Test resetting password with an invalid OTP.
     */
    public function test_reset_password_with_invalid_otp(): void
    {
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
    }

    /**
     * Test resetting password with a non-existent email.
     */
    public function test_reset_password_with_non_existent_email(): void
    {
        $response = $this->postJson($this->reset, [
            'email' => 'nonexistent@example.com',
            'otp' => 'anyotp',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', __('The OTP provided is incorrect or has expired. Please try again.'))
                    ->has('errors')
                    ->where('errors.otp.0', __('The OTP provided is incorrect or has expired. Please try again.'))
            );
    }

    protected function tearDown(): void
    {
        if ($this->user) {
            $this->user->delete();
        }

        parent::tearDown();
    }
}
