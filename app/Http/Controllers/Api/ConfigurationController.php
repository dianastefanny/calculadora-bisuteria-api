<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($request->user()->configuration);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'sometimes|string|max:10',
            'default_margin' => 'sometimes|numeric|min:0|max:99.99',
            'theme' => 'sometimes|string|max:20',
            'monthly_salary' => 'sometimes|numeric|min:0',
            'monthly_working_hours' => 'sometimes|numeric|min:0',
            // Opcional: si no se llena, los costos indirectos se siguen
            // repartiendo por minuto de mano de obra (ver CalculationController).
            'monthly_production' => 'sometimes|nullable|numeric|min:0',
        ]);

        $configuration = $request->user()->configuration;
        $configuration->update($validated);

        return response()->json($configuration);
    }
}
