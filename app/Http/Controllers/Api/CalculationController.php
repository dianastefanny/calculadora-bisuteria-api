<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Calculation;
use App\Models\Design;
use App\Models\Packaging;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalculationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Limpieza automática del historial: borra de una vez los cálculos
        // con más de 30 días desde que se crearon, para no acumular
        // historial indefinidamente. Se revisa en cada consulta en vez de
        // con una tarea programada, porque en desarrollo local no hay un
        // cron corriendo solo (en un servidor real convendría moverlo a un
        // comando programado).
        $user->calculations()
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $calculations = $user->calculations()
            ->with('design.details.material')
            ->latest()
            ->get();

        return response()->json($calculations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'design_id' => 'required|exists:designs,id',
            'production_time_minutes' => 'required|integer|min:1',
            'packaging_id' => 'nullable|exists:packagings,id',
            'packaging_quantity' => 'nullable|numeric|min:0',
            'margin' => 'nullable|numeric|min:0|max:99.99',
            'include_indirect_costs' => 'sometimes|boolean',
            'include_benefits' => 'sometimes|boolean',
        ]);

        $includeIndirectCosts = $validated['include_indirect_costs'] ?? true;
        $includeBenefits = $validated['include_benefits'] ?? true;

        $user = $request->user();

        $design = Design::findOrFail($validated['design_id']);
        abort_if($design->user_id !== $user->id, 403);

        $configuration = $user->configuration;
        abort_if(! $configuration, 422, 'You must set up your configuration before calculating.');

        // 1. Costo de materiales
        $materialsCost = (float) $design->details()->sum('subtotal');

        // 2. Costo de mano de obra
        $laborCost = round($configuration->cost_per_minute * $validated['production_time_minutes'], 2);

        // 3. Costo de prestaciones (solo si el usuario decide incluirlas)
        $totalBenefitsPercentage = $includeBenefits
            ? (float) $user->benefits()->where('is_active', true)->sum('percentage')
            : 0.0;
        $monthlyBenefitsCost = (float) $configuration->monthly_salary * ($totalBenefitsPercentage / 100);
        $benefitsCostPerMinute = $configuration->monthly_productive_minutes > 0
            ? $monthlyBenefitsCost / $configuration->monthly_productive_minutes
            : 0;
        $benefitsCost = round($benefitsCostPerMinute * $validated['production_time_minutes'], 2);

        // 4. Costo indirecto (solo si el usuario decide incluirlo). Dos formas
        // de repartirlo, según si el usuario configuró cuántas piezas produce
        // al mes:
        // - Si SÍ la configuró: cada pieza carga la misma porción fija del
        //   gasto indirecto mensual (costeo por unidades producidas).
        // - Si NO (queda en null): se reparte por minuto de mano de obra —
        //   costeo por absorción con base en horas de mano de obra directa,
        //   más preciso para producción artesanal donde cada pieza toma un
        //   tiempo distinto y no requiere que el usuario adivine cuántas
        //   piezas hará al mes.
        $totalMonthlyIndirect = $includeIndirectCosts
            ? (float) $user->indirectCosts()->where('is_active', true)->sum('monthly_amount')
            : 0.0;

        if ($configuration->monthly_production > 0) {
            $indirectCost = round($totalMonthlyIndirect / $configuration->monthly_production, 2);
        } else {
            $indirectCostPerMinute = $configuration->monthly_productive_minutes > 0
                ? $totalMonthlyIndirect / $configuration->monthly_productive_minutes
                : 0;
            $indirectCost = round($indirectCostPerMinute * $validated['production_time_minutes'], 2);
        }

        // 5. Empaque: viene del catálogo de packagings
        $packagingId = $validated['packaging_id'] ?? null;
        $packagingQuantity = (float) ($validated['packaging_quantity'] ?? 1);
        $packagingCost = 0;

        if ($packagingId) {
            $packaging = Packaging::findOrFail($packagingId);
            abort_if($packaging->user_id !== $user->id, 403);

            $packagingCost = round((float) $packaging->unit_cost * $packagingQuantity, 2);
        }

        // 6. Costo total
        $totalCost = round($materialsCost + $laborCost + $benefitsCost + $indirectCost + $packagingCost, 2);

        // 7. Margen real y precio de venta
        $margin = (float) ($validated['margin'] ?? $configuration->default_margin);
        $salePrice = round($totalCost / (1 - $margin / 100), 2);

        $calculation = $user->calculations()->create([
            'design_id' => $design->id,
            'packaging_id' => $packagingId,
            'packaging_quantity' => $packagingQuantity,
            'production_time_minutes' => $validated['production_time_minutes'],
            'packaging_cost' => $packagingCost,
            'margin' => $margin,
            'materials_cost' => $materialsCost,
            'labor_cost' => $laborCost,
            'benefits_cost' => $benefitsCost,
            'indirect_cost' => $indirectCost,
            'total_cost' => $totalCost,
            'sale_price' => $salePrice,
            'valid_until' => Carbon::now()->addDays(5),
        ]);

        return response()->json($calculation->load(['design', 'packaging']), 201);
    }

    public function show(Request $request, Calculation $calculation)
    {
        abort_if($calculation->user_id !== $request->user()->id, 403);

        return response()->json($calculation->load('design.details.material'));
    }

    public function destroy(Request $request, Calculation $calculation)
    {
        abort_if($calculation->user_id !== $request->user()->id, 403);

        $calculation->delete();

        return response()->json(['message' => 'Calculation deleted successfully.']);
    }
}
