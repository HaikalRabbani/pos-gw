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
        $authUser = $request->user();
        $query = User::with('outlets')
            ->where('tenant_id', $authUser->tenant_id);

        // Manager: only see users assigned to their outlets
        $authRole = $this->getHighestRole($authUser);
        if ($authRole === 'manager') {
            $outletIds = $authUser->outlets->pluck('id');
            $query->whereHas('outlets', fn($q) => $q->whereIn('outlet_id', $outletIds));
        }

        // Optional: filter by specific outlet
        if ($request->filled('outlet_id')) {
            $query->whereHas('outlets', fn($q) => $q->where('outlet_id', $request->outlet_id));
        }

        $users = $query->latest()->paginate($request->per_page ?? 20);

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
        $authUser = request()->user();
        $authRole = $this->getHighestRole($authUser);

        // Manager: can only view users in their outlets
        if ($authRole === 'manager') {
            $outletIds = $authUser->outlets->pluck('id');
            $shared = $user->outlets()->whereIn('outlet_id', $outletIds)->exists();
            if (!$shared) {
                abort(403, 'You cannot view this user.');
            }
        }

        return response()->json([
            'success' => true,
            'data' => $user->load('outlets'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $authUser = $request->user();
        $authRole = $this->getHighestRole($authUser);

        // Manager: can only edit name of users in their outlets
        if ($authRole === 'manager') {
            $outletIds = $authUser->outlets->pluck('id');
            $shared = $user->outlets()->whereIn('outlet_id', $outletIds)->exists();
            if (!$shared) {
                abort(403, 'You cannot edit this user.');
            }
            // Manager can only update name
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
            ]);
            $user->update($validated);
            return response()->json(['success' => true, 'data' => $user]);
        }

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
        $authUser = request()->user();
        $authRole = $this->getHighestRole($authUser);

        // Manager: can only toggle users in their outlets
        if ($authRole === 'manager') {
            $outletIds = $authUser->outlets->pluck('id');
            $shared = $user->outlets()->whereIn('outlet_id', $outletIds)->exists();
            if (!$shared) {
                abort(403, 'You cannot modify this user.');
            }
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json(['success' => true, 'data' => $user]);
    }

    public function setPin(Request $request, User $user)
    {
        $authUser = $request->user();
        $authRole = $this->getHighestRole($authUser);

        // Manager: can only set PIN for users in their outlets
        if ($authRole === 'manager') {
            $outletIds = $authUser->outlets->pluck('id');
            $shared = $user->outlets()->whereIn('outlet_id', $outletIds)->exists();
            if (!$shared) {
                abort(403, 'You cannot modify this user.');
            }
        }

        $validated = $request->validate([
            'pin' => 'required|string|digits:6',
        ]);

        $user->update(['pin' => $validated['pin']]);

        return response()->json(['success' => true, 'message' => 'PIN set.']);
    }

    /**
     * Get the highest role the user has across all outlets.
     */
    private function getHighestRole($user): ?string
    {
        $roles = $user->outlets->pluck('pivot.role')->unique();
        $hierarchy = ['developer', 'admin', 'manager', 'cashier', 'kitchen'];

        foreach ($hierarchy as $role) {
            if ($roles->contains($role)) return $role;
        }

        return null;
    }
}
