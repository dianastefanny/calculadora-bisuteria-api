<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    public function store(Request $request)
    {
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
                // Reemplaza toda la receta con la nueva lista
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

    public function uploadImage(Request $request, Design $design)
    {
        abort_if($design->user_id !== $request->user()->id, 403);

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $path = $request->file('image')->store("designs/{$design->user_id}", 'public');

        // Si ya tenía foto, se borra el archivo anterior para no acumular
        $oldPath = $design->image_path;

        $design->update(['image_path' => $path]);

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json($design->load('details.material'));
    }

    public function deleteImage(Request $request, Design $design)
    {
        abort_if($design->user_id !== $request->user()->id, 403);

        if ($design->image_path) {
            Storage::disk('public')->delete($design->image_path);
            $design->update(['image_path' => null]);
        }

        return response()->json($design->load('details.material'));
    }

    public function destroy(Request $request, Design $design)
    {
        abort_if($design->user_id !== $request->user()->id, 403);

        $design->update(['is_active' => false]);

        return response()->json(['message' => 'Design deleted successfully.']);
    }
}
