<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\PinLoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'data' => $user,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function loginPin(PinLoginRequest $request)
    {
        $result = $this->authService->loginByPin(
            $request->input('pin'),
            $request->input('outlet_id')
        );

        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function me()
    {
        $user = request()->user()->load('outlets');

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'password'   => 'sometimes|string|min:8',
            'photo'      => 'nullable|string|max:500',
            'pin'        => 'nullable|string|digits:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        $user->load('outlets');

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Profil berhasil diperbarui.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out.',
        ]);
    }
}
