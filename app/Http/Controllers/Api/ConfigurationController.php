<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Configuración de costos y preferencias del usuario: salario, horas
 * productivas, producción mensual y margen por defecto (base del cálculo de
 * costos), además de la moneda y el tema de la app.
 *
 * Cada usuario tiene una única configuración, creada al registrarse.
 */
class ConfigurationController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($request->user()->configuration);
    }

    /**
     * Actualización parcial: solo se modifican los campos enviados.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'sometimes|string|max:10',
            'default_margin' => 'sometimes|numeric|min:0|max:99.99',
            'theme' => 'sometimes|string|max:20',
            'monthly_salary' => 'sometimes|numeric|min:0',
            'monthly_working_hours' => 'sometimes|numeric|min:0',
            // Admite null de forma explícita: sin producción mensual, los
            // costos indirectos se reparten por minuto de mano de obra
            // (ver CalculationController::store).
            'monthly_production' => 'sometimes|nullable|numeric|min:0',
        ]);

        $configuration = $request->user()->configuration;
        $configuration->update($validated);

        return response()->json($configuration);
    }
}
