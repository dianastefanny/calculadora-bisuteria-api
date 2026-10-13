<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

/**
 * CRUD de las prestaciones legales del usuario (salud, pensión, ARL, etc.).
 *
 * La suma de los porcentajes activos se aplica sobre el salario mensual en
 * CalculationController cuando el usuario decide incluir prestaciones.
 * Cada registro solo es accesible por su dueño.
 */
class BenefitController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()
                ->benefits()
                ->with('benefitType')
                ->where('is_active', true)
                ->get()
        );
    }

    public function store(Request $request)
    {
        // El tipo debe existir y pertenecer al usuario: impide asociar la
        // prestación a un tipo de otra cuenta.
        $validated = $request->validate([
            'benefit_type_id' => [
                'required',
                Rule::exists('benefit_types', 'id')->where('user_id', $request->user()->id),
                ],
            'name' => 'required|string|max:150',
            'percentage' => 'required|numeric|min:0|max:100',
        ]);

        $benefit = $request->user()->benefits()->create($validated);

        return response()->json($benefit->load('benefitType'), 201);
    }

    public function show(Request $request, Benefit $benefit)
    {
        abort_if($benefit->user_id !== $request->user()->id, 403);

        return response()->json($benefit->load('benefitType'));
    }

    public function update(Request $request, Benefit $benefit)
    {
        abort_if($benefit->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'benefit_type_id' => [
                'sometimes',
                Rule::exists('benefit_types', 'id')->where('user_id', $request->user()->id),
                ],
            'name' => 'sometimes|string|max:150',
            'percentage' => 'sometimes|numeric|min:0|max:100',
        ]);

        $benefit->update($validated);

        return response()->json($benefit->load('benefitType'));
    }

    /**
     * Borrado lógico: la prestación se desactiva y deja de sumarse en los
     * cálculos nuevos, sin perder el registro.
     */
    public function destroy(Request $request, Benefit $benefit)
    {
        abort_if($benefit->user_id !== $request->user()->id, 403);

        $benefit->update(['is_active' => false]);

        return response()->json(['message' => 'Benefit deleted successfully.']);
    }
}
