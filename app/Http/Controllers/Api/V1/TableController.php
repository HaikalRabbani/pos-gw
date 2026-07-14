<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RestTable;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);
        return response()->json([
            'success' => true,
            'data' => RestTable::where('outlet_id', $request->outlet_id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'name' => 'required|string|max:50',
        ]);

        $validated['qr_token'] = bin2hex(random_bytes(32));

        return response()->json([
            'success' => true,
            'data' => RestTable::create($validated),
        ], 201);
    }

    public function update(Request $request, RestTable $table)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        $table->update($validated);

        return response()->json(['success' => true, 'data' => $table]);
    }

    public function destroy(RestTable $table)
    {
        $table->delete();
        return response()->json(['success' => true, 'message' => 'Table deleted.']);
    }

    public function regenerateQr(RestTable $table)
    {
        $table->update(['qr_token' => bin2hex(random_bytes(32))]);
        return response()->json(['success' => true, 'data' => $table]);
    }
}
