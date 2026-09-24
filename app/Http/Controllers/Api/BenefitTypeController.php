<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BenefitType;
use Illuminate\Http\Request;

class BenefitTypeController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->benefitTypes()->where('is_active', true)->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $benefitType = $request->user()->benefitTypes()->create($validated);

        return response()->json($benefitType, 201);
    }

    public function show(Request $request, BenefitType $benefitType)
    {
        abort_if($benefitType->user_id !== $request->user()->id, 403);

        return response()->json($benefitType);
    }

    public function update(Request $request, BenefitType $benefitType)
    {
        abort_if($benefitType->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
        ]);

        $benefitType->update($validated);

        return response()->json($benefitType);
    }

    public function destroy(Request $request, BenefitType $benefitType)
    {
        abort_if($benefitType->user_id !== $request->user()->id, 403);

        $benefitType->update(['is_active' => false]);

        return response()->json(['message' => 'Benefit type deleted successfully.']);
    }
}
