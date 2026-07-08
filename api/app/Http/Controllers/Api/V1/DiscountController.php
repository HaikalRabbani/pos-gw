<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);
        return response()->json([
            'success' => true,
            'data' => Discount::where('outlet_id', $request->outlet_id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'name' => 'required|string|max:100',
            'type' => 'required|string|in:percent,nominal',
            'value' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        return response()->json([
            'success' => true,
            'data' => Discount::create($validated),
        ], 201);
    }

    public function update(Request $request, Discount $discount)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|string|in:percent,nominal',
            'value' => 'sometimes|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $discount->update($validated);

        return response()->json(['success' => true, 'data' => $discount]);
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();
        return response()->json(['success' => true, 'message' => 'Discount deleted.']);
    }
}
