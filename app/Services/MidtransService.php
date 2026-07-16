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
        // Pake key outlet kalo ada, fallback ke key platform
        $outlet = $order->outlet;
        $serverKey = $outlet?->midtrans_server_key ?: config('midtrans.server_key');
        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = str_starts_with($serverKey, 'Mid-server');
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
        // Key akan di-set per-order setelah kita tau outlet-nya
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

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

        $order = Order::with('outlet')->find($internalId);
        if (!$order) return null;

        // Set Midtrans key berdasarkan outlet order
        $outlet = $order->outlet;
        $serverKey = $outlet?->midtrans_server_key ?: config('midtrans.server_key');
        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = str_starts_with($serverKey, 'Mid-server');

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

                // Auto-add balance outlet — HANYA kalo pake platform key (uang ke akun Xendit platform)
                // Kalo outlet punya Midtrans key sendiri, uang langsung ke akun mereka — gak perlu balance
                if ($order->outlet && !$outlet->midtrans_server_key) {
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
