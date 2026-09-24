<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use App\Models\IndirectCost;
use Illuminate\Http\Request;

class IndirectCostController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()
                ->indirectCosts()
                ->with('costType')
                ->where('is_active', true)
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cost_type_id' => [
                'required',
                Rule::exists('cost_types', 'id')->where('user_id', $request->user()->id),
            ],
            'name' => 'required|string|max:150',
            'monthly_amount' => 'required|numeric|min:0',
        ]);

        $indirectCost = $request->user()->indirectCosts()->create($validated);

        return response()->json($indirectCost->load('costType'), 201);
    }

    public function show(Request $request, IndirectCost $indirectCost)
    {
        abort_if($indirectCost->user_id !== $request->user()->id, 403);

        return response()->json($indirectCost->load('costType'));
    }

    public function update(Request $request, IndirectCost $indirectCost)
    {
        abort_if($indirectCost->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'cost_type_id' => [
                'sometimes',
                Rule::exists('cost_types', 'id')->where('user_id', $request->user()->id),
                ],
            'name' => 'sometimes|string|max:150',
            'monthly_amount' => 'sometimes|numeric|min:0',
        ]);

        $indirectCost->update($validated);

        return response()->json($indirectCost->load('costType'));
    }

    public function destroy(Request $request, IndirectCost $indirectCost)
    {
        abort_if($indirectCost->user_id !== $request->user()->id, 403);

        $indirectCost->update(['is_active' => false]);

        return response()->json(['message' => 'Indirect cost deleted successfully.']);
    }
}
