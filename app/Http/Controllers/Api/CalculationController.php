<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Calculation;
use App\Models\Design;
use App\Models\Packaging;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cálculo de costos y precio de venta de las piezas, e historial de
 * cotizaciones del usuario.
 *
 * Toda la fórmula de costeo vive en el backend: la app solo envía los datos
 * de entrada y muestra el desglose resultante, de modo que un mismo cálculo
 * siempre da el mismo resultado sin importar el dispositivo.
 */
class CalculationController extends Controller
{
    /**
     * Lista el historial de cotizaciones, de la más reciente a la más antigua.
     *
     * Antes de consultar, purga los cálculos con más de 30 días. La limpieza
     * se hace aquí, a demanda, porque en desarrollo local no hay un
     * programador de tareas (cron) en ejecución; en producción convendría
     * trasladarla a un comando programado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $user->calculations()
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $calculations = $user->calculations()
            ->with('design.details.material')
            ->latest()
            ->get();

        return response()->json($calculations);
    }

    /**
     * Calcula el costo total y el precio de venta de una pieza (o de un lote
     * de piezas iguales) y guarda el resultado como cotización.
     *
     * Componentes del costo:
     *  1. Materiales: subtotal de la receta del diseño.
     *  2. Mano de obra: costo por minuto (salario / minutos productivos).
     *  3. Prestaciones: porcentaje del salario, prorrateado por minuto.
     *  4. Costos indirectos: por pieza o por minuto, según la configuración.
     *  5. Empaque: costo unitario del empaque elegido.
     *
     * El precio de venta se obtiene sobre el margen de ganancia (no como
     * recargo sobre el costo) y, si hay descuento, se calcula aparte el
     * precio final. La cotización queda vigente durante 5 días.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'design_id' => 'required|exists:designs,id',
            'production_time_minutes' => 'required|integer|min:1',
            'quantity' => 'nullable|integer|min:1',
            'packaging_id' => 'nullable|exists:packagings,id',
            'packaging_quantity' => 'nullable|numeric|min:0',
            'margin' => 'nullable|numeric|min:0|max:99.99',
            'discount_percentage' => 'nullable|numeric|min:0|max:99.99',
            'include_indirect_costs' => 'sometimes|boolean',
            'include_benefits' => 'sometimes|boolean',
        ]);

        $includeIndirectCosts = $validated['include_indirect_costs'] ?? true;
        $includeBenefits = $validated['include_benefits'] ?? true;

        // El tiempo y los materiales se registran por pieza; la cantidad se
        // aplica una sola vez aquí y el resto de la fórmula trabaja sobre el
        // lote completo.
        $quantity = $validated['quantity'] ?? 1;
        $totalMinutes = $validated['production_time_minutes'] * $quantity;

        $user = $request->user();

        $design = Design::findOrFail($validated['design_id']);
        abort_if($design->user_id !== $user->id, 403);

        $configuration = $user->configuration;
        abort_if(! $configuration, 422, 'You must set up your configuration before calculating.');

        // 1. Materiales
        $materialsCost = (float) $design->details()->sum('subtotal') * $quantity;

        // 2. Mano de obra
        $laborCost = round($configuration->cost_per_minute * $totalMinutes, 2);

        // 3. Prestaciones: suma de los porcentajes activos aplicada al salario
        // mensual y repartida entre los minutos productivos del mes.
        $totalBenefitsPercentage = $includeBenefits
            ? (float) $user->benefits()->where('is_active', true)->sum('percentage')
            : 0.0;
        $monthlyBenefitsCost = (float) $configuration->monthly_salary * ($totalBenefitsPercentage / 100);
        $benefitsCostPerMinute = $configuration->monthly_productive_minutes > 0
            ? $monthlyBenefitsCost / $configuration->monthly_productive_minutes
            : 0;
        $benefitsCost = round($benefitsCostPerMinute * $totalMinutes, 2);

        // 4. Costos indirectos, con dos criterios de reparto:
        //  - Con producción mensual configurada: cada pieza absorbe la misma
        //    porción fija del gasto mensual (costeo por unidades producidas).
        //  - Sin ella: se reparte por minuto de mano de obra (costeo por
        //    absorción), más preciso en producción artesanal, donde cada
        //    pieza toma un tiempo distinto.
        $totalMonthlyIndirect = $includeIndirectCosts
            ? (float) $user->indirectCosts()->where('is_active', true)->sum('monthly_amount')
            : 0.0;

        if ($configuration->monthly_production > 0) {
            $indirectCost = round(($totalMonthlyIndirect / $configuration->monthly_production) * $quantity, 2);
        } else {
            $indirectCostPerMinute = $configuration->monthly_productive_minutes > 0
                ? $totalMonthlyIndirect / $configuration->monthly_productive_minutes
                : 0;
            $indirectCost = round($indirectCostPerMinute * $totalMinutes, 2);
        }

        // 5. Empaque. No se multiplica por la cantidad de piezas: un pedido de
        // varias piezas iguales normalmente se entrega en un solo empaque.
        $packagingId = $validated['packaging_id'] ?? null;
        $packagingQuantity = (float) ($validated['packaging_quantity'] ?? 1);
        $packagingCost = 0;

        if ($packagingId) {
            $packaging = Packaging::findOrFail($packagingId);
            abort_if($packaging->user_id !== $user->id, 403);

            $packagingCost = round((float) $packaging->unit_cost * $packagingQuantity, 2);
        }

        $totalCost = round($materialsCost + $laborCost + $benefitsCost + $indirectCost + $packagingCost, 2);

        // Precio sobre margen: con un margen del 30 %, la ganancia es el 30 %
        // del precio de venta (costo / 0,70), no el 30 % del costo.
        $margin = (float) ($validated['margin'] ?? $configuration->default_margin);
        $salePrice = round($totalCost / (1 - $margin / 100), 2);

        // sale_price se conserva sin descuento como referencia; final_price es
        // lo que efectivamente se cobra al cliente.
        $discountPercentage = $validated['discount_percentage'] ?? null;
        $finalPrice = $discountPercentage
            ? round($salePrice * (1 - $discountPercentage / 100), 2)
            : $salePrice;

        $calculation = $user->calculations()->create([
            'design_id' => $design->id,
            'packaging_id' => $packagingId,
            'packaging_quantity' => $packagingQuantity,
            'production_time_minutes' => $validated['production_time_minutes'],
            'quantity' => $quantity,
            'packaging_cost' => $packagingCost,
            'margin' => $margin,
            'discount_percentage' => $discountPercentage,
            'materials_cost' => $materialsCost,
            'labor_cost' => $laborCost,
            'benefits_cost' => $benefitsCost,
            'indirect_cost' => $indirectCost,
            'total_cost' => $totalCost,
            'sale_price' => $salePrice,
            'final_price' => $finalPrice,
            'valid_until' => Carbon::now()->addDays(5),
        ]);

        return response()->json($calculation->load(['design', 'packaging']), 201);
    }

    /**
     * Devuelve una cotización con la receta de su diseño.
     */
    public function show(Request $request, Calculation $calculation)
    {
        abort_if($calculation->user_id !== $request->user()->id, 403);

        return response()->json($calculation->load('design.details.material'));
    }

    /**
     * Elimina una cotización del historial (borrado definitivo).
     */
    public function destroy(Request $request, Calculation $calculation)
    {
        abort_if($calculation->user_id !== $request->user()->id, 403);

        $calculation->delete();

        return response()->json(['message' => 'Calculation deleted successfully.']);
    }

    /**
     * Registra la venta de una cotización y descuenta del inventario los
     * materiales y el empaque utilizados.
     *
     * La operación es todo o nada: primero se comprueba que haya stock
     * suficiente de cada insumo y, si falta alguno, se rechaza con la lista
     * de faltantes sin modificar nada. Solo entonces se descuenta dentro de
     * una transacción. Una cotización no puede marcarse como vendida dos veces.
     */
    public function markSold(Request $request, Calculation $calculation)
    {
        abort_if($calculation->user_id !== $request->user()->id, 403);
        abort_if($calculation->is_sold, 422, 'This calculation was already marked as sold.');

        $calculation->load('design.details.material', 'packaging');

        $insufficient = [];

        foreach ($calculation->design->details as $detail) {
            $needed = $detail->quantity * $calculation->quantity;
            if ($detail->material->stock < $needed) {
                $insufficient[] = $detail->material->name;
            }
        }

        // El empaque no escala con la cantidad de piezas (mismo criterio que store()).
        if ($calculation->packaging) {
            if ($calculation->packaging->stock < $calculation->packaging_quantity) {
                $insufficient[] = $calculation->packaging->name;
            }
        }

        abort_if(
            count($insufficient) > 0,
            422,
            'No hay suficiente stock de: '.implode(', ', $insufficient).'.'
        );

        DB::transaction(function () use ($calculation) {
            foreach ($calculation->design->details as $detail) {
                $detail->material->decrement('stock', $detail->quantity * $calculation->quantity);
            }

            if ($calculation->packaging) {
                $calculation->packaging->decrement('stock', $calculation->packaging_quantity);
            }

            $calculation->update(['is_sold' => true]);
        });

        return response()->json($calculation->load(['design.details.material', 'packaging']));
    }
}
