<?php

namespace App\Http\Controllers\Api;

use App\Enums\MaterialUnit;
use Illuminate\Validation\Rules\Enum;
use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $materials = $request->user()
            ->materials()
            ->with('category')
            ->where('is_active', true)
            ->get();

        return response()->json($materials);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_category_id' => [
                'required',
                Rule::exists('material_categories', 'id')->where('user_id', $request->user()->id),
            ],
            'name' => 'required|string|max:150',
            'unit' => ['required', new Enum(MaterialUnit::class)],
            'unit_cost' => 'required|numeric|min:0',
            'stock' => 'nullable|numeric|min:0',
        ]);

        $material = $request->user()->materials()->create($validated);

        return response()->json($material, 201);
    }

    public function show(Request $request, Material $material)
    {
        abort_if($material->user_id !== $request->user()->id, 403);

        return response()->json($material->load('category'));
    }

   public function update(Request $request, Material $material)
    {
        abort_if($material->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'material_category_id' => [
                'sometimes',
                Rule::exists('material_categories', 'id')->where('user_id', $request->user()->id),
            ],
            'name' => 'sometimes|string|max:150',
            'unit' => ['sometimes', new Enum(MaterialUnit::class)],
            'unit_cost' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|numeric|min:0',
        ]);

        $material->update($validated);

        return response()->json($material);
    }

    public function destroy(Request $request, Material $material)
    {
        abort_if($material->user_id !== $request->user()->id, 403);
        // Eliminación lógica según regla de negocio del documento
        $material->update(['is_active' => false]);

        return response()->json(['message' => 'Material deleted successfully.']);
    }
}
