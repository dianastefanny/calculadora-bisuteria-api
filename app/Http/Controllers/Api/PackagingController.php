<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Packaging;
use Illuminate\Http\Request;

class PackagingController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->packagings()->where('is_active', true)->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'unit_cost' => 'required|numeric|min:0',
            'stock' => 'nullable|numeric|min:0',
        ]);

        $packaging = $request->user()->packagings()->create($validated);

        return response()->json($packaging, 201);
    }

    public function show(Request $request, Packaging $packaging)
    {
        abort_if($packaging->user_id !== $request->user()->id, 403);

        return response()->json($packaging);
    }

    public function update(Request $request, Packaging $packaging)
    {
        abort_if($packaging->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'unit_cost' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|numeric|min:0',
        ]);

        $packaging->update($validated);

        return response()->json($packaging);
    }

    public function destroy(Request $request, Packaging $packaging)
    {
        abort_if($packaging->user_id !== $request->user()->id, 403);

        $packaging->update(['is_active' => false]);

        return response()->json(['message' => 'Packaging deleted successfully.']);
    }
}
