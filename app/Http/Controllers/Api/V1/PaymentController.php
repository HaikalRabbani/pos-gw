<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MidtransService;
use App\Services\XenditPaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected MidtransService $midtrans,
        protected XenditPaymentService $xendit
    ) {}

    /**
     * Buat invoice payment.
     * - Kalo outlet punya Midtrans key sendiri → pake Midtrans
     * - Kalo gak → pake Xendit (uang masuk ke akun Xendit platform)
     */
    public function snapToken(Order $order)
    {
        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Order already paid.',
            ], 400);
        }

        // Cek apakah outlet punya Midtrans key sendiri
        $outlet = $order->outlet;

        if ($outlet && $outlet->midtrans_server_key) {
            // Own key → pake Midtrans (uang langsung ke akun owner)
            $result = $this->midtrans->createTransaction($order);
            return response()->json([
                'success' => true,
                'data' => $result,
                'payment_method' => 'midtrans',
            ]);
        }

        // Platform → pake Xendit (uang ke akun Xendit platform)
        $result = $this->xendit->createInvoice($order);
        return response()->json([
            'success' => true,
            'data' => $result,
            'payment_method' => 'xendit',
        ]);
    }

    /**
     * Handle payment callback dari Xendit.
     * Verifikasi webhook token untuk keamanan.
     */
    public function xenditCallback(Request $request)
    {
        // Verifikasi webhook token dari header Xendit
        $webhookToken = config('xendit.webhook_token');
        if ($webhookToken) {
            $callbackToken = $request->header('x-callback-token');
            if (!$callbackToken || $callbackToken !== $webhookToken) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        $order = $this->xendit->handleCallback($request->all());

        if (!$order) {
            return response()->json(['success' => false], 400);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle payment notification dari Midtrans (own-key).
     */
    public function midtransNotification(Request $request)
    {
        $order = $this->midtrans->handleNotification($request->all());

        if (!$order) {
            return response()->json(['success' => false], 400);
        }

        return response()->json(['success' => true]);
    }
}
