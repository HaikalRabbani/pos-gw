<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;

class StationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $stations = Station::where('outlet_id', $request->outlet_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $stations]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'name' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $station = Station::create($validated);

        return response()->json(['success' => true, 'data' => $station], 201);
    }

    public function update(Request $request, Station $station)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $station->update($validated);

        return response()->json(['success' => true, 'data' => $station]);
    }

    public function destroy(Station $station)
    {
        $station->delete();
        return response()->json(['success' => true, 'message' => 'Station deleted.']);
    }
}
