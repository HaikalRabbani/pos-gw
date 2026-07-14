<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(
        protected ShiftService $shiftService
    ) {}

    public function index(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $shifts = Shift::with('user')
            ->where('outlet_id', $request->outlet_id)
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $shifts->items(),
            'meta' => [
                'page' => $shifts->currentPage(),
                'per_page' => $shifts->perPage(),
                'total' => $shifts->total(),
            ],
        ]);
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'cash_begin' => 'nullable|integer|min:0',
        ]);

        $shift = $this->shiftService->startShift(
            $request->user()->id,
            $validated['outlet_id'],
            $validated['cash_begin'] ?? 0,
        );

        return response()->json(['success' => true, 'data' => $shift], 201);
    }

    public function end(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'cash_actual' => 'required|integer|min:0',
        ]);

        $shift = $this->shiftService->endShift($shift, $validated['cash_actual']);

        return response()->json(['success' => true, 'data' => $shift]);
    }
}
