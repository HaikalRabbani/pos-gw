<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): User
    {
        $tenantId = Tenant::orderBy('id')->value('id');

        $existing = User::where('email', $data['email'])
            ->where('tenant_id', $tenantId)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'email' => ['Email already registered in this tenant.'],
            ]);
        }

        return User::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function login(string $email, string $password): array
    {
        $tenantId = Tenant::orderBy('id')->value('id');
        $user = User::where('email', $email)->where('tenant_id', $tenantId)->first();

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
        $user->load('outlets');

        return ['token' => $token, 'user' => $user];
    }

    public function loginByPin(string $pin, ?int $outletId = null): array
    {
        $user = User::where('pin', $pin)->where('is_active', true)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'pin' => ['Invalid PIN.'],
            ]);
        }

        // Validasi shift aktif di outlet tertentu
        if ($outletId) {
            $activeShift = Shift::where('user_id', $user->id)
                ->where('outlet_id', $outletId)
                ->whereNull('end_at')
                ->first();

            if (!$activeShift) {
                throw ValidationException::withMessages([
                    'pin' => ['No active shift at this outlet. Start a shift first.'],
                ]);
            }
        }

        $token = $user->createToken('pos-pin-token')->plainTextToken;
        $user->load('outlets');

        return ['token' => $token, 'user' => $user];
    }

    public function setPin(User $user, string $pin): void
    {
        $user->update(['pin' => $pin]);
    }
}
