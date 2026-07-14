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
            'data' => Discount::where('outlet_id', $request->outlet_id)
                ->with('targetProduct', 'targetCategory')
                ->orderBy('name')
                ->get(),
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
            'target_type' => 'nullable|string|in:product,category,transaction',
            'target_id' => 'nullable|integer|min:0',
            'min_purchase' => 'nullable|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'buy_x' => 'nullable|integer|min:1',
            'buy_y' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // target_id validation based on target_type
        $targetType = $validated['target_type'] ?? 'transaction';
        if ($targetType === 'product') {
            $request->validate(['target_id' => 'required|integer|exists:products,id']);
        } elseif ($targetType === 'category') {
            $request->validate(['target_id' => 'required|integer|exists:categories,id']);
        }

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
            'target_type' => 'nullable|string|in:product,category,transaction',
            'target_id' => 'nullable|integer|min:0',
            'min_purchase' => 'nullable|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'buy_x' => 'nullable|integer|min:1',
            'buy_y' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // target_id validation based on target_type
        $targetType = $request->input('target_type', $discount->target_type ?? 'transaction');
        if ($targetType === 'product') {
            $request->validate(['target_id' => 'required|integer|exists:products,id']);
        } elseif ($targetType === 'category') {
            $request->validate(['target_id' => 'required|integer|exists:categories,id']);
        }

        $discount->update($validated);

        return response()->json(['success' => true, 'data' => $discount->load('targetProduct', 'targetCategory')]);
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();
        return response()->json(['success' => true, 'message' => 'Discount deleted.']);
    }
}
