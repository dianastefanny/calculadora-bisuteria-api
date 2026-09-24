<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;

class MaterialCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = $request->user()
            ->materialCategories()
            ->where('is_active', true)
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $category = $request->user()->materialCategories()->create($validated);

        return response()->json($category, 201);
    }

    public function show(Request $request, MaterialCategory $materialCategory)
    {
        abort_if($materialCategory->user_id !== $request->user()->id, 403);

        return response()->json($materialCategory);
    }

    public function update(Request $request, MaterialCategory $materialCategory)
    {
        abort_if($materialCategory->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
        ]);

        $materialCategory->update($validated);

        return response()->json($materialCategory);
    }

    public function destroy(Request $request, MaterialCategory $materialCategory)
    {
        abort_if($materialCategory->user_id !== $request->user()->id, 403);

        $materialCategory->update(['is_active' => false]);

        return response()->json(['message' => 'Category deleted successfully.']);
    }
}
