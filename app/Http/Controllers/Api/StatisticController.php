<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Indicadores del resumen que muestra la pantalla de Inicio.
 */
class StatisticController extends Controller
{
    /**
     * Cuenta solo los registros activos (los eliminados lógicamente no se
     * incluyen) y la fecha del último cálculo, o null si aún no hay ninguno.
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        $summary = [
            'total_materials' => $user->materials()->where('is_active', true)->count(),
            'total_material_categories' => $user->materialCategories()->where('is_active', true)->count(),
            'total_designs' => $user->designs()->where('is_active', true)->count(),
            'total_packagings' => $user->packagings()->where('is_active', true)->count(),
            'total_indirect_costs' => $user->indirectCosts()->where('is_active', true)->count(),
            'total_benefits' => $user->benefits()->where('is_active', true)->count(),
            'total_calculations' => $user->calculations()->count(),
            'last_calculation_at' => $user->calculations()->latest()->value('created_at'),
        ];

        return response()->json($summary);
    }
}
