<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('outlets')
            ->where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => $user->load('outlets'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json(['success' => true, 'data' => $user]);
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        return response()->json(['success' => true, 'data' => $user]);
    }

    public function setPin(Request $request, User $user)
    {
        $validated = $request->validate([
            'pin' => 'required|string|digits:6',
        ]);

        $user->update(['pin' => $validated['pin']]);

        return response()->json(['success' => true, 'message' => 'PIN set.']);
    }
}
