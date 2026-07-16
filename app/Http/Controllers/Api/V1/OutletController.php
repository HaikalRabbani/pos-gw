<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    public function index()
    {
        $outlets = request()->user()->outlets;
        return response()->json(['success' => true, 'data' => $outlets]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;

        $outlet = Outlet::create($validated);

        // Assign creator as admin
        request()->user()->outlets()->attach($outlet->id, ['role' => 'admin']);

        return response()->json(['success' => true, 'data' => $outlet], 201);
    }

    public function show(Outlet $outlet)
    {
        return response()->json(['success' => true, 'data' => $outlet]);
    }

    public function update(Request $request, Outlet $outlet)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
            'stock_mode' => 'sometimes|in:product,ingredient',
            'midtrans_server_key' => 'nullable|string|max:255',
        ]);

        $outlet->update($validated);

        return response()->json(['success' => true, 'data' => $outlet]);
    }

    public function destroy(Outlet $outlet)
    {
        if ($outlet->orders()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete outlet with existing orders. Deactivate instead.',
            ], 400);
        }

        $outlet->delete();

        return response()->json(['success' => true, 'message' => 'Outlet deleted.']);
    }
}
