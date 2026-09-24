<?php

namespace Database\Factories;

use App\Models\CostType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IndirectCostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cost_type_id' => CostType::factory(),
            'name' => $this->faker->words(2, true),
            'monthly_amount' => $this->faker->randomFloat(2, 50000, 500000),
            'is_active' => true,
        ];
    }
}
