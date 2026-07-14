<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Services\WithdrawService;
use Illuminate\Http\Request;

class WithdrawController extends Controller
{
    public function __construct(
        protected WithdrawService $withdrawService
    ) {}

    /**
     * Lihat saldo outlet.
     */
    public function balance(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $outlet = Outlet::findOrFail($request->outlet_id);
        $balance = $this->withdrawService->getBalance($outlet);

        return response()->json([
            'success' => true,
            'data' => $balance,
        ]);
    }

    /**
     * Riwayat mutasi saldo.
     */
    public function transactions(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $outlet = Outlet::findOrFail($request->outlet_id);
        $transactions = $this->withdrawService->getTransactions($outlet, $request->limit ?? 20);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Riwayat withdraw.
     */
    public function withdrawals(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $outlet = Outlet::findOrFail($request->outlet_id);
        $withdrawals = $this->withdrawService->getWithdrawals($outlet, $request->limit ?? 20);

        return response()->json([
            'success' => true,
            'data' => $withdrawals,
        ]);
    }

    /**
     * Proses withdraw.
     */
    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'amount' => 'required|integer|min:10000', // dalam Rupiah (dari frontend)
            'bank_name' => 'required|string|max:100',
            'bank_account' => 'required|string|max:50',
            'account_holder' => 'required|string|max:200',
        ]);

        $outlet = Outlet::findOrFail($validated['outlet_id']);
        // Konversi Rupiah → cents (konsisten dengan sistem POS lainnya)
        $amountInCents = $validated['amount'] * 100;

        try {
            $withdraw = $this->withdrawService->processWithdraw(
                $outlet,
                $request->user()->id,
                $amountInCents,
                [
                    'bank_name' => $validated['bank_name'],
                    'bank_account' => $validated['bank_account'],
                    'account_holder' => $validated['account_holder'],
                ],
            );

            return response()->json([
                'success' => true,
                'message' => 'Withdraw berhasil diproses!',
                'data' => $withdraw,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
