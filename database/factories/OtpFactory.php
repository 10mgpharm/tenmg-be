<?php

namespace Database\Factories;

use App\Enums\OtpType;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OtpFactory extends Factory
{
    protected $model = Otp::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => $this->faker->numerify('######'), // 6-digit code
            'type' => $this->faker->randomElement([
                OtpType::SIGNUP_EMAIL_VERIFICATION->value,
                OtpType::RESET_PASSWORD_VERIFICATION->value,
            ]),
        ];
    }
}
