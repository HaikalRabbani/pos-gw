<?php

namespace App\Services;

use App\Models\BalanceTransaction;
use App\Models\Outlet;
use App\Models\OutletBalance;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class WithdrawService
{
    /**
     * Tambah balance outlet setelah pembayaran QRIS settle.
     */
    public function addBalance(Outlet $outlet, int $amount, string $description, ?string $refType = null, ?int $refId = null): OutletBalance
    {
        $balance = OutletBalance::firstOrCreate(
            ['outlet_id' => $outlet->id],
            ['balance' => 0, 'total_withdrawn' => 0],
        );

        $balance->increment('balance', $amount);

        BalanceTransaction::create([
            'outlet_id' => $outlet->id,
            'type' => 'qris_payment',
            'amount' => $amount,
            'description' => $description,
            'reference_type' => $refType,
            'reference_id' => $refId,
        ]);

        return $balance->fresh();
    }

    /**
     * Proses withdraw: validasi saldo, bikin request, panggil Xendit Payout.
     */
    public function processWithdraw(Outlet $outlet, int $userId, int $amount, array $bankInfo): WithdrawalRequest
    {
        $balance = OutletBalance::where('outlet_id', $outlet->id)->first();

        if (!$balance || $balance->balance < $amount) {
            throw ValidationException::withMessages([
                'amount' => ['Saldo tidak mencukupi.'],
            ]);
        }

        if ($amount < 1000000) { // minimal withdraw Rp 10.000 (1.000.000 cents)
            throw ValidationException::withMessages([
                'amount' => ['Minimal withdraw Rp 10.000.'],
            ]);
        }

        // Buat request withdraw
        $withdraw = WithdrawalRequest::create([
            'outlet_id' => $outlet->id,
            'user_id' => $userId,
            'amount' => $amount,
            'bank_name' => $bankInfo['bank_name'],
            'bank_account' => $bankInfo['bank_account'],
            'account_holder' => $bankInfo['account_holder'],
            'status' => 'processing',
        ]);

        try {
            // Panggil Xendit Payout API
            $xenditRef = $this->sendToXendit($withdraw);

            $withdraw->update([
                'xendit_ref' => $xenditRef,
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            // Kurangi balance
            $balance->decrement('balance', $amount);
            $balance->increment('total_withdrawn', $amount);

            // Catat transaksi
            BalanceTransaction::create([
                'outlet_id' => $outlet->id,
                'type' => 'withdrawal',
                'amount' => -$amount,
                'description' => "Withdraw ke {$bankInfo['bank_name']} {$bankInfo['bank_account']} a.n. {$bankInfo['account_holder']}",
                'reference_type' => 'withdrawal_request',
                'reference_id' => $withdraw->id,
            ]);

            return $withdraw->fresh();

        } catch (\Exception $e) {
            $withdraw->update([
                'status' => 'failed',
                'note' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Ambil saldo outlet.
     */
    public function getBalance(Outlet $outlet): OutletBalance
    {
        return OutletBalance::firstOrCreate(
            ['outlet_id' => $outlet->id],
            ['balance' => 0, 'total_withdrawn' => 0],
        );
    }

    /**
     * Riwayat transaksi balance.
     */
    public function getTransactions(Outlet $outlet, int $limit = 20)
    {
        return BalanceTransaction::where('outlet_id', $outlet->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Riwayat withdraw.
     */
    public function getWithdrawals(Outlet $outlet, int $limit = 20)
    {
        return WithdrawalRequest::where('outlet_id', $outlet->id)
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Kirim payout ke Xendit.
     */
    protected function sendToXendit(WithdrawalRequest $withdraw): string
    {
        $apiKey = config('xendit.api_key');
        if (!$apiKey) {
            // Sandbox mode — simulasi berhasil
            return 'SIM-' . strtoupper(\Str::random(12));
        }

        $isProduction = config('xendit.is_production', false);
        $baseUrl = $isProduction
            ? 'https://api.xendit.co'
            : 'https://api.xendit.co'; // Xendit uses same URL for sandbox/prod, key yg bedain

        $amountInRupiah = $withdraw->amount / 100; // convert cents to rupiah

        $response = Http::withBasicAuth($apiKey, '')
            ->post("{$baseUrl}/disbursements", [
                'external_id' => 'WITHDRAW-' . $withdraw->id . '-' . time(),
                'bank_code' => $withdraw->bank_name,
                'account_holder_name' => $withdraw->account_holder,
                'account_number' => $withdraw->bank_account,
                'description' => "Withdraw saldo outlet #{$withdraw->outlet_id}",
                'amount' => $amountInRupiah,
            ]);

        if ($response->failed()) {
            $error = $response->json('message') ?? 'Xendit API error';
            throw new \Exception("Xendit: {$error}");
        }

        return $response->json('id') ?? 'XENDIT-' . strtoupper(\Str::random(12));
    }
}
