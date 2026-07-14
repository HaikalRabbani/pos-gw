<?php

namespace App\Services;

use App\Models\Order;

class MidtransService
{
    public function __construct(
        protected WithdrawService $withdrawService
    ) {}

    public function createTransaction(Order $order): array
    {
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = config('midtrans.is_production');
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $params = [
            'transaction_details' => [
                'order_id' => 'POS-' . $order->id . '-' . time(),
                'gross_amount' => $order->grand_total / 100, // convert cents to rupiah
            ],
            'customer_details' => [
                'first_name' => $order->customer_name ?? 'Customer',
            ],
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);

        return [
            'snap_token' => $snapToken,
            'order_id' => $params['transaction_details']['order_id'],
        ];
    }

    public function handleNotification(array $notification): ?Order
    {
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = config('midtrans.is_production');

        $orderId = $notification['order_id'] ?? null;
        $transactionStatus = $notification['transaction_status'] ?? '';
        $fraudStatus = $notification['fraud_status'] ?? '';

        // Extract our internal order ID
        if (!$orderId || !str_starts_with($orderId, 'POS-')) {
            return null;
        }

        $parts = explode('-', $orderId);
        $internalId = $parts[1] ?? null;
        if (!$internalId) return null;

        $order = Order::find($internalId);
        if (!$order) return null;

        if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
            if ($fraudStatus === 'accept' || $fraudStatus === '') {
                $payment = $order->payments()->create([
                    'method' => 'midtrans',
                    'amount' => $order->grand_total,
                    'midtrans_ref' => $notification['transaction_id'] ?? '',
                    'midtrans_status' => $transactionStatus,
                    'paid_at' => now(),
                ]);

                $order->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'midtrans',
                ]);

                // Auto-add balance untuk outlet (QRIS / non-tunai)
                if ($order->outlet) {
                    $this->withdrawService->addBalance(
                        $order->outlet,
                        $order->grand_total,
                        "Pembayaran QRIS Order #{$order->id}",
                        'order',
                        $order->id,
                    );
                }
            }
        }

        if ($transactionStatus === 'deny' || $transactionStatus === 'expire' || $transactionStatus === 'cancel') {
            $order->payments()->create([
                'method' => 'midtrans',
                'amount' => 0,
                'midtrans_ref' => $notification['transaction_id'] ?? '',
                'midtrans_status' => $transactionStatus,
            ]);
        }

        return $order;
    }
}
