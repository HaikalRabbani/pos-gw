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
        $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = Order::with('items', 'payments')
            ->where('outlet_id', $request->outlet_id);

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

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
            'data' => $order->load('items', 'payments', 'logs.user', 'discounts', 'refundedBy', 'table', 'user'),
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

    public function refund(Request $request, Order $order)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|integer|exists:order_items,id',
            'items.*.qty' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);

        $order = $this->orderService->refund(
            $order,
            $request->user()->id,
            $validated['items'],
            $validated['reason'] ?? null,
        );

        return response()->json(['success' => true, 'data' => $order]);
    }

    public function split(Request $request, Order $order)
    {
        $validated = $request->validate([
            'splits' => 'required|array|min:2',
            'splits.*.customer_name' => 'nullable|string|max:100',
            'splits.*.items' => 'required|array|min:1',
            'splits.*.items.*.order_item_id' => 'required|integer|exists:order_items,id',
            'splits.*.items.*.qty' => 'required|integer|min:1',
        ]);

        $newOrders = $this->orderService->splitOrder(
            $order,
            $request->user()->id,
            $validated['splits'],
        );

        return response()->json([
            'success' => true,
            'data' => $newOrders,
            'message' => 'Berhasil split menjadi ' . count($newOrders) . ' tagihan.',
        ]);
    }

    public function merge(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'order_ids' => 'required|array|min:2',
            'order_ids.*' => 'required|integer|exists:orders,id',
            'customer_name' => 'nullable|string|max:100',
        ]);

        $orders = Order::whereIn('id', $validated['order_ids'])->get();

        if ($orders->count() !== count($validated['order_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'Beberapa pesanan tidak ditemukan.',
            ], 404);
        }

        $mergedOrder = $this->orderService->mergeOrders(
            $orders->all(),
            $request->user()->id,
            $validated['customer_name'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => $mergedOrder,
            'message' => 'Berhasil merge ' . count($orders) . ' pesanan.',
        ]);
    }

    /**
     * Get order items grouped by station.
     * Used by Flutter app to route prints to different thermal printers
     * per station (e.g. Dapur, Bar, Grill).
     */
    public function printGroups(Order $order)
    {
        $order->load('items.product.station');

        $groups = $order->items
            ->groupBy(fn($item) => $item->product?->station_id ?? 0)
            ->map(function ($items, $stationId) {
                $station = $items->first()->product?->station;
                return [
                    'station_id'   => $stationId,
                    'station_name' => $station?->name ?? 'Tanpa Station',
                    'items'        => $items->map(fn($i) => [
                        'product_name' => $i->product_name,
                        'variant_name' => $i->variant_name,
                        'qty'          => $i->qty,
                        'unit_price'   => $i->unit_price,
                        'total_price'  => $i->total_price,
                        'notes'        => $i->notes,
                    ]),
                    'total_items'  => $items->sum('qty'),
                ];
            })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'order_id'      => $order->id,
                'table_name'    => $order->table?->name,
                'customer_name' => $order->customer_name,
                'created_at'    => $order->created_at,
                'groups'        => $groups,
            ],
        ]);
    }
}
