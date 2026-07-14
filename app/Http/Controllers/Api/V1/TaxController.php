<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);
        return response()->json([
            'success' => true,
            'data' => Tax::where('outlet_id', $request->outlet_id)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'sort_order' => 'nullable|integer|min:0|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        return response()->json([
            'success' => true,
            'data' => Tax::create($validated),
        ], 201);
    }

    public function update(Request $request, Tax $tax)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'rate' => 'sometimes|numeric|min:0|max:100',
            'sort_order' => 'nullable|integer|min:0|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $tax->update($validated);

        return response()->json(['success' => true, 'data' => $tax]);
    }

    public function destroy(Tax $tax)
    {
        $tax->delete();
        return response()->json(['success' => true, 'message' => 'Tax deleted.']);
    }
}
