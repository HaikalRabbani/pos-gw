<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ShiftType;
use Illuminate\Http\Request;

class ShiftTypeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);
        return response()->json([
            'success' => true,
            'data' => ShiftType::where('outlet_id', $request->outlet_id)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'name' => 'required|string|max:50',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        return response()->json([
            'success' => true,
            'data' => ShiftType::create($validated),
        ], 201);
    }

    public function update(Request $request, ShiftType $shiftType)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $shiftType->update($validated);

        return response()->json(['success' => true, 'data' => $shiftType]);
    }

    public function destroy(ShiftType $shiftType)
    {
        $shiftType->delete();
        return response()->json(['success' => true, 'message' => 'Shift type deleted.']);
    }
}
