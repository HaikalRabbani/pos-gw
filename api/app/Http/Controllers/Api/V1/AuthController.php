<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\PinLoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Services\AuthService;

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

    public function logout()
    {
        request()->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out.',
        ]);
    }
}
