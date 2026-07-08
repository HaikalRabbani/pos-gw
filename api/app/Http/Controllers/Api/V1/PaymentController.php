<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected MidtransService $midtrans
    ) {}

    public function snapToken(Order $order)
    {
        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Order already paid.',
            ], 400);
        }

        $result = $this->midtrans->createTransaction($order);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function notification(Request $request)
    {
        $order = $this->midtrans->handleNotification($request->all());

        if (!$order) {
            return response()->json(['success' => false], 400);
        }

        return response()->json(['success' => true]);
    }
}
