<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CostType;
use Illuminate\Http\Request;

class CostTypeController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->costTypes()->where('is_active', true)->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $costType = $request->user()->costTypes()->create($validated);

        return response()->json($costType, 201);
    }

    public function show(Request $request, CostType $costType)
    {
        abort_if($costType->user_id !== $request->user()->id, 403);

        return response()->json($costType);
    }

    public function update(Request $request, CostType $costType)
    {
        abort_if($costType->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
        ]);

        $costType->update($validated);

        return response()->json($costType);
    }

    public function destroy(Request $request, CostType $costType)
    {
        abort_if($costType->user_id !== $request->user()->id, 403);

        $costType->update(['is_active' => false]);

        return response()->json(['message' => 'Cost type deleted successfully.']);
    }
}
