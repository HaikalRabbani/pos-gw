<?php

namespace App\Services;

use App\Mail\ResetPasswordMail;
use App\Mail\VerificationCodeMail;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => false,
        ]);

        // Generate & send verification code
        $this->sendVerificationCode($user);

        return $user;
    }

    public function sendVerificationCode(User $user): void
    {
        // Hapus kode lama untuk email ini
        VerificationCode::where('email', $user->email)->delete();

        // Generate kode 6 digit
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        VerificationCode::create([
            'email' => $user->email,
            'code' => $code,
            'expires_at' => now()->addMinutes(30),
        ]);

        // Kirim email
        Mail::to($user->email)->send(new VerificationCodeMail($code, $user->name));
    }

    public function verifyEmail(string $email, string $code): void
    {
        $verification = VerificationCode::where('email', $email)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            throw ValidationException::withMessages([
                'code' => ['Kode verifikasi tidak valid atau sudah kedaluwarsa.'],
            ]);
        }

        // Aktifkan user
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Email tidak ditemukan.'],
            ]);
        }

        $user->update([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $verification->delete();

        // Bikin outlet baru buat user kalo belum punya outlet
        if ($user->outlets()->count() === 0) {
            $newOutlet = Outlet::create([
                'tenant_id' => $user->tenant_id,
                'name' => $user->name . "'s Outlet",
                'address' => null,
                'phone' => null,
                'is_active' => true,
            ]);
            $user->outlets()->attach($newOutlet->id, ['role' => 'admin']);
        }

        // Auto login
        Auth::guard('web')->login($user);
        $user->load('outlets');
    }

    public function login(string $email, string $password): array
    {
        $tenantId = Tenant::orderBy('id')->value('id');
        $user = User::where('email', $email)->where('tenant_id', $tenantId)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun belum diaktivasi. Periksa email untuk kode aktivasi.'],
            ]);
        }

        Auth::guard('web')->login($user);
        $user->load('outlets');

        return ['user' => $user];
    }

    public function loginByPin(string $pin, ?int $outletId = null): array
    {
        $users = User::where('is_active', true)->get();
        $matchedUser = null;

        foreach ($users as $user) {
            if ($user->pin && Hash::check($pin, $user->pin)) {
                $matchedUser = $user;
                break;
            }
        }

        if (!$matchedUser) {
            throw ValidationException::withMessages([
                'pin' => ['PIN tidak valid.'],
            ]);
        }

        if ($outletId) {
            $activeShift = Shift::where('user_id', $matchedUser->id)
                ->where('outlet_id', $outletId)
                ->whereNull('end_at')
                ->first();

            if (!$activeShift) {
                throw ValidationException::withMessages([
                    'pin' => ['Tidak ada shift aktif di outlet ini. Mulai shift terlebih dahulu.'],
                ]);
            }
        }

        Auth::guard('web')->login($matchedUser);
        $matchedUser->load('outlets');

        return ['user' => $matchedUser];
    }

    public function forgotPassword(string $email): void
    {
        $tenantId = Tenant::orderBy('id')->value('id');
        $user = User::where('email', $email)->where('tenant_id', $tenantId)->first();

        if (!$user) {
            // Tetap success biar gak bocorin email yang terdaftar
            return;
        }

        // Generate token unik
        $token = Str::random(64);

        // Simpan ke DB
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // Buat reset URL — fallback ke APP_URL kalo APP_FRONTEND_URL gak diset
        $frontendUrl = config('app.frontend_url') ?? config('app.url');
        $resetUrl = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($email);

        // Kirim email
        Mail::to($email)->send(new ResetPasswordMail($resetUrl, $user->name));
    }

    public function resetPassword(string $email, string $token, string $password): void
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || !Hash::check($token, $record->token)) {
            throw ValidationException::withMessages([
                'token' => ['Link reset password tidak valid atau sudah kedaluwarsa.'],
            ]);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw ValidationException::withMessages([
                'token' => ['Link reset password sudah kedaluwarsa. Silakan minta ulang.'],
            ]);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Email tidak ditemukan.'],
            ]);
        }

        $user->update(['password' => Hash::make($password)]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    public function setPin(User $user, string $pin): void
    {
        $user->update(['pin' => $pin]);
    }
}
