<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

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

    public function destroy(Request $request, Benefit $benefit)
    {
        abort_if($benefit->user_id !== $request->user()->id, 403);

        $benefit->update(['is_active' => false]);

        return response()->json(['message' => 'Benefit deleted successfully.']);
    }
}
