<?php

namespace Database\Factories;

use App\Enums\BusinessStatus;
use App\Enums\BusinessType;
use App\Helpers\UtilityHelper;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'short_name' => UtilityHelper::generateBusinessCode($name),
            'code' => UtilityHelper::generateBusinessCode($name),
            'type' => fake()->randomElement(BusinessType::allowedForRegistration())->value,
            'status' => BusinessStatus::PENDING_VERIFICATION->value,
            'active' => true,
        ];
    }

    /**
     * Indicate that the business is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BusinessStatus::VERIFIED->value,
        ]);
    }
}
