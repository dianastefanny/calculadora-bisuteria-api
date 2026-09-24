<?php

namespace Database\Factories;

use App\Enums\MaterialUnit;
use App\Models\MaterialCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'material_category_id' => MaterialCategory::factory(),
            'name' => $this->faker->words(2, true),
            'unit' => MaterialUnit::Unit->value,
            'unit_cost' => $this->faker->randomFloat(2, 1, 1000),
            'stock' => $this->faker->randomFloat(2, 0, 500),
            'is_active' => true,
        ];
    }
}
