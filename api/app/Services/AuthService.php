<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): User
    {
        $existing = User::where('email', $data['email'])->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'email' => ['Email already registered.'],
            ]);
        }

        $tenantId = Tenant::orderBy('id')->value('id');

        return User::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Account is disabled.'],
            ]);
        }

        $token = $user->createToken('pos-token')->plainTextToken;

        return ['token' => $token, 'user' => $user];
    }

    public function loginByPin(string $pin): array
    {
        $user = User::where('pin', $pin)->where('is_active', true)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'pin' => ['Invalid PIN.'],
            ]);
        }

        $token = $user->createToken('pos-pin-token')->plainTextToken;

        return ['token' => $token, 'user' => $user];
    }

    public function setPin(User $user, string $pin): void
    {
        $user->update(['pin' => $pin]);
    }
}
