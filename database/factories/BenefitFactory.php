<?php

namespace Database\Factories;

use App\Models\BenefitType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BenefitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'benefit_type_id' => BenefitType::factory(),
            'name' => $this->faker->words(2, true),
            'percentage' => $this->faker->randomFloat(2, 1, 15),
            'is_active' => true,
        ];
    }
}
