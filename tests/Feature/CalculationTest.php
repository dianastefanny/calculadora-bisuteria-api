<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Design;
use App\Models\IndirectCost;
use App\Models\CostType;
use App\Models\Benefit;
use App\Models\BenefitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculation_formula_produces_expected_price(): void
    {
        $user = User::factory()->create();
        $user->configuration()->create([
            'monthly_salary' => 1_200_000,
            'monthly_working_hours' => 200, // → 12,000 minutos productivos
            'default_margin' => 20,
        ]);

        // Prestaciones: 10% total sobre el salario
        $benefitType = BenefitType::factory()->for($user)->create();
        Benefit::factory()->for($user)->create([
            'benefit_type_id' => $benefitType->id,
            'percentage' => 10,
        ]);

        // Costo indirecto: $200,000/mes ÷ 12,000 minutos productivos = $16.6667/min
        $costType = CostType::factory()->for($user)->create();
        IndirectCost::factory()->for($user)->create([
            'cost_type_id' => $costType->id,
            'monthly_amount' => 200_000,
        ]);

        $category = MaterialCategory::factory()->for($user)->create();
        $material = Material::factory()->for($user)->create([
            'material_category_id' => $category->id,
            'unit_cost' => 100,
        ]);

        $design = Design::factory()->for($user)->create();
        $design->details()->create([
            'material_id' => $material->id,
            'quantity' => 10,
            'subtotal' => 1000, // 10 × 100
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/calculations', [
            'design_id' => $design->id,
            'production_time_minutes' => 60,
            'margin' => 20,
        ]);

        $response->assertStatus(201);

        // Verificación manual de cada componente:
        // materials_cost = 1000
        // labor_cost = (1,200,000 / 12,000) × 60 = 6,000
        // benefits_cost = (1,200,000 × 10%) / 12,000 × 60 = 600
        // indirect_cost = (200,000 / 12,000) × 60 = 1,000
        // total_cost = 1000 + 6000 + 600 + 1000 = 8600
        // sale_price = 8600 / (1 - 0.20) = 10,750

        $response->assertJson([
            'materials_cost' => '1000.00',
            'labor_cost' => '6000.00',
            'benefits_cost' => '600.00',
            'indirect_cost' => '1000.00',
            'total_cost' => '8600.00',
            'sale_price' => '10750.00',
        ]);
    }

    public function test_user_cannot_calculate_with_another_users_design(): void
    {
        $owner = User::factory()->create();
        $owner->configuration()->create([]);

        $intruder = User::factory()->create();
        $intruder->configuration()->create([]);

        $design = Design::factory()->for($owner)->create();

        $response = $this->actingAs($intruder, 'sanctum')->postJson('/api/calculations', [
            'design_id' => $design->id,
            'production_time_minutes' => 30,
        ]);

        $response->assertStatus(403);
    }
}
