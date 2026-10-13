<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Diseños de piezas: la "receta" con los materiales y cantidades que usa
 * cada pieza, más su foto opcional.
 *
 * El subtotal de cada material se congela al guardar el diseño (cantidad ×
 * costo unitario vigente). Si después cambia el precio de un material, el
 * diseño debe volver a guardarse para tomar el costo nuevo.
 */
class DesignController extends Controller
{
    public function index(Request $request)
    {
        $designs = $request->user()
            ->designs()
            ->with('details.material')
            ->where('is_active', true)
            ->get();

        return response()->json($designs);
    }

    /**
     * Crea el diseño con su receta en una sola transacción: si algún
     * material no existe o no pertenece al usuario, no se guarda nada.
     */
    public function store(Request $request)
    {
        // "distinct" impide repetir un mismo material dentro de la receta.
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'reference' => 'nullable|string|max:15',
            'materials' => 'required|array|min:1',
            'materials.*.material_id' => 'required|exists:materials,id|distinct',
            'materials.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $design = DB::transaction(function () use ($validated, $request) {
            $design = $request->user()->designs()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'reference' => $validated['reference'] ?? null,
            ]);

            foreach ($validated['materials'] as $item) {
                $material = Material::findOrFail($item['material_id']);

                abort_if($material->user_id !== $request->user()->id, 403);

                $design->details()->create([
                    'material_id' => $material->id,
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['quantity'] * $material->unit_cost,
                ]);
            }

            return $design;
        });

        return response()->json($design->load('details.material'), 201);
    }

    public function show(Request $request, Design $design)
    {
        abort_if($design->user_id !== $request->user()->id, 403);

        return response()->json($design->load('details.material'));
    }

    /**
     * Actualiza los datos del diseño y, si se envía la lista de materiales,
     * reemplaza la receta completa recalculando los subtotales con los
     * costos actuales. Todo ocurre en una transacción.
     */
    public function update(Request $request, Design $design)
    {
        abort_if($design->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'description' => 'nullable|string',
            'reference' => 'nullable|string|max:15',
            'materials' => 'sometimes|array|min:1',
            'materials.*.material_id' => 'required_with:materials|exists:materials,id',
            'materials.*.quantity' => 'required_with:materials|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($validated, $design, $request) {
            $design->update([
                'name' => $validated['name'] ?? $design->name,
                'description' => $validated['description'] ?? $design->description,
                'reference' => $validated['reference'] ?? $design->reference,
            ]);

            if (isset($validated['materials'])) {
                $design->details()->delete();

                foreach ($validated['materials'] as $item) {
                    $material = Material::findOrFail($item['material_id']);

                    abort_if($material->user_id !== $request->user()->id, 403);

                    $design->details()->create([
                        'material_id' => $material->id,
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['quantity'] * $material->unit_cost,
                    ]);
                }
            }
        });

        return response()->json($design->load('details.material'));
    }

    /**
     * Sube o reemplaza la foto del diseño (JPG, PNG o WebP de hasta 2 MB).
     *
     * El archivo se guarda en el disco público, en una carpeta por usuario.
     * El archivo anterior se elimina solo después de registrar el nuevo, para
     * que el diseño nunca quede apuntando a una foto inexistente.
     */
    public function uploadImage(Request $request, Design $design)
    {
        abort_if($design->user_id !== $request->user()->id, 403);

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $path = $request->file('image')->store("designs/{$design->user_id}", 'public');

        $oldPath = $design->image_path;

        $design->update(['image_path' => $path]);

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json($design->load('details.material'));
    }

    /**
     * Quita la foto del diseño y elimina el archivo del disco.
     */
    public function deleteImage(Request $request, Design $design)
    {
        abort_if($design->user_id !== $request->user()->id, 403);

        if ($design->image_path) {
            Storage::disk('public')->delete($design->image_path);
            $design->update(['image_path' => null]);
        }

        return response()->json($design->load('details.material'));
    }

    /**
     * Borrado lógico: el diseño deja de listarse, pero se conserva para que
     * las cotizaciones del historial que lo usan sigan mostrando sus datos.
     */
    public function destroy(Request $request, Design $design)
    {
        abort_if($design->user_id !== $request->user()->id, 403);

        $design->update(['is_active' => false]);

        return response()->json(['message' => 'Design deleted successfully.']);
    }
}
