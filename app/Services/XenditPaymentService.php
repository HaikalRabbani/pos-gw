<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class XenditPaymentService
{
    public function __construct(
        protected WithdrawService $withdrawService
    ) {}

    /**
     * Create Xendit invoice untuk pembayaran QRIS/VA.
     * UANG MASUK LANGSUNG KE AKUN XENDIT PLATFORM.
     */
    public function createInvoice(Order $order): array
    {
        $apiKey = config('xendit.api_key');
        $amountInRupiah = $order->grand_total / 100; // convert cents to rupiah
        $externalId = 'POS-' . $order->id . '-' . time();

        $response = Http::withBasicAuth($apiKey, '')
            ->post('https://api.xendit.co/v2/invoices', [
                'external_id' => $externalId,
                'amount' => $amountInRupiah,
                'description' => "Pembayaran Order #{$order->id} - {$order->outlet?->name}",
                'customer' => [
                    'given_names' => $order->customer_name ?? 'Customer',
                ],
                'customer_notification_preference' => [
                    'invoice_paid' => ['email', 'whatsapp'],
                ],
                'success_redirect_url' => url('/orders/' . $order->id),
                'failure_redirect_url' => url('/orders/' . $order->id),
                'currency' => 'IDR',
            ]);

        if ($response->failed()) {
            $error = $response->json('message') ?? 'Xendit API error';
            throw new \Exception("Xendit Invoice: {$error}");
        }

        $body = $response->json();

        return [
            'invoice_id' => $body['id'] ?? null,
            'invoice_url' => $body['invoice_url'] ?? null,
            'external_id' => $externalId,
            'status' => $body['status'] ?? 'PENDING',
        ];
    }

    /**
     * Handle callback dari Xendit setelah pembayaran.
     */
    public function handleCallback(array $payload): ?Order
    {
        $externalId = $payload['external_id'] ?? null;
        $status = $payload['status'] ?? '';
        $xenditId = $payload['id'] ?? '';

        // Extract our internal order ID: POS-{id}-{timestamp}
        if (!$externalId || !str_starts_with($externalId, 'POS-')) {
            return null;
        }

        $parts = explode('-', $externalId);
        $internalId = $parts[1] ?? null;
        if (!$internalId) return null;

        $order = Order::with('outlet')->find($internalId);
        if (!$order) return null;

        if ($status === 'PAID' || $status === 'SETTLED') {
            $order->payments()->create([
                'method' => 'xendit_qris',
                'amount' => $order->grand_total,
                'midtrans_ref' => $xenditId,
                'midtrans_status' => $status,
                'paid_at' => now(),
            ]);

            $order->update([
                'payment_status' => 'paid',
                'payment_method' => 'xendit_qris',
            ]);

            // Auto-add balance untuk outlet (karena uang masuk ke akun Xendit platform)
            if ($order->outlet) {
                $this->withdrawService->addBalance(
                    $order->outlet,
                    $order->grand_total,
                    "Pembayaran Xendit Order #{$order->id}",
                    'order',
                    $order->id,
                );
            }
        }

        if (in_array($status, ['EXPIRED', 'FAILED'])) {
            $order->payments()->create([
                'method' => 'xendit_qris',
                'amount' => 0,
                'midtrans_ref' => $xenditId,
                'midtrans_status' => $status,
            ]);
        }

        return $order;
    }
}
