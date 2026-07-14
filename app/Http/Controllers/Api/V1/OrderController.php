<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index(Request $request)
    {
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);

        $query = Order::with('items', 'payments')
            ->where('outlet_id', $request->outlet_id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->latest()->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'table_id' => 'nullable|exists:tables,id',
            'customer_name' => 'nullable|string|max:100',
        ]);

        $order = $this->orderService->createDraft([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'data' => $order], 201);
    }

    public function show(Order $order)
    {
        return response()->json([
            'success' => true,
            'data' => $order->load('items', 'payments', 'logs.user', 'discounts'),
        ]);
    }

    public function addItem(Request $request, Order $order)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'product_name' => 'required|string|max:200',
            'variant_name' => 'nullable|string|max:100',
            'qty' => 'required|integer|min:1',
            'unit_price' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $order = $this->orderService->addItem($order, $validated);

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function removeItem(Order $order, int $itemId)
    {
        $order = $this->orderService->removeItem($order, $itemId);

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:draft,confirmed,preparing,done,cancelled,voided',
            'note' => 'nullable|string',
        ]);

        $order = $this->orderService->updateStatus(
            $order,
            $validated['status'],
            $request->user()->id,
            $validated['note'] ?? null,
        );

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function payCash(Request $request, Order $order)
    {
        $order = $this->orderService->payCash($order, $request->user()->id);

        return response()->json(['success' => true, 'data' => $order]);
    }
}
