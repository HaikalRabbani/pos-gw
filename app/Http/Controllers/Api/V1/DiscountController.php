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
        $discounts = Discount::where('outlet_id', $request->outlet_id)
            ->orderBy('name')
            ->get();

        // Manual append untuk menghindari N+1 di model accessor ($appends)
        $discounts->each->append(['target_products', 'target_categories']);

        return response()->json([
            'success' => true,
            'data' => $discounts,
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
            'target_id' => 'nullable|array',
            'target_id.*' => 'nullable|integer|min:0',
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
            $request->validate([
                'target_id' => 'required|array|min:1',
                'target_id.*' => 'required|integer|exists:products,id',
            ]);
        } elseif ($targetType === 'category') {
            $request->validate([
                'target_id' => 'required|array|min:1',
                'target_id.*' => 'required|integer|exists:categories,id',
            ]);
        } else {
            $validated['target_id'] = null;
        }

        $discount = Discount::create($validated);
        $discount->append(['target_products', 'target_categories']);

        return response()->json([
            'success' => true,
            'data' => $discount,
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
            'target_id' => 'nullable|array',
            'target_id.*' => 'nullable|integer|min:0',
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
            $request->validate([
                'target_id' => 'required|array|min:1',
                'target_id.*' => 'required|integer|exists:products,id',
            ]);
        } elseif ($targetType === 'category') {
            $request->validate([
                'target_id' => 'required|array|min:1',
                'target_id.*' => 'required|integer|exists:categories,id',
            ]);
        } else {
            $validated['target_id'] = null;
        }

        $discount->update($validated);
        $discount->append(['target_products', 'target_categories']);

        return response()->json(['success' => true, 'data' => $discount]);
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();
        return response()->json(['success' => true, 'message' => 'Discount deleted.']);
    }
}
