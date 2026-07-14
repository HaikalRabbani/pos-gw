<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserOutletController extends Controller
{
    public function assignRole(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'outlet_id' => 'required|exists:outlets,id',
            'role' => 'required|string|in:admin,cashier,kitchen',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($request->has('remove') && $request->boolean('remove')) {
            $user->outlets()->detach($validated['outlet_id']);
            return response()->json(['success' => true, 'message' => 'Role removed.']);
        }

        $user->outlets()->syncWithoutDetaching([
            $validated['outlet_id'] => ['role' => $validated['role']],
        ]);

        return response()->json(['success' => true, 'message' => 'Role assigned.']);
    }
}
