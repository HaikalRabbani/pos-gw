<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Tax;
use App\Models\Discount;
use Illuminate\Validation\ValidationException;

class OrderService
{
    const STATUSES = [
        'draft', 'confirmed', 'preparing', 'done', 'cancelled', 'voided',
    ];

    const TRANSITIONS = [
        'draft' => ['confirmed', 'voided'],
        'confirmed' => ['preparing', 'voided'],
        'preparing' => ['done', 'cancelled'],
        'done' => [],
        'cancelled' => [],
        'voided' => [],
    ];

    public function createDraft(array $data): Order
    {
        $order = Order::create([
            'outlet_id' => $data['outlet_id'],
            'table_id' => $data['table_id'] ?? null,
            'user_id' => $data['user_id'],
            'customer_name' => $data['customer_name'] ?? null,
            'status' => 'draft',
        ]);

        $this->log($order->id, null, 'draft', $data['user_id']);

        return $order->fresh()->load('items');
    }

    public function updateStatus(Order $order, string $newStatus, int $userId, ?string $note = null): Order
    {
        if (!$this->canTransition($order->status, $newStatus)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot transition from {$order->status} to {$newStatus}."],
            ]);
        }

        $oldStatus = $order->status;
        $order->update(['status' => $newStatus]);
        $this->log($order->id, $oldStatus, $newStatus, $userId, $note);

        OrderStatusUpdated::dispatch($order);

        return $order->fresh();
    }

    public function addItem(Order $order, array $data): Order
    {
        if ($order->status !== 'draft') {
            throw ValidationException::withMessages([
                'order' => ['Can only modify draft orders.'],
            ]);
        }

        $item = $order->items()->create([
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'] ?? null,
            'product_name' => $data['product_name'],
            'variant_name' => $data['variant_name'] ?? null,
            'qty' => $data['qty'],
            'unit_price' => $data['unit_price'],
            'total_price' => $data['unit_price'] * $data['qty'],
            'notes' => $data['notes'] ?? null,
        ]);

        $this->recalculate($order);

        return $order->fresh()->load('items');
    }

    public function removeItem(Order $order, int $itemId): Order
    {
        if ($order->status !== 'draft') {
            throw ValidationException::withMessages([
                'order' => ['Can only modify draft orders.'],
            ]);
        }

        $order->items()->where('id', $itemId)->delete();
        $this->recalculate($order);

        return $order->fresh()->load('items');
    }

    public function payCash(Order $order, int $userId): Order
    {
        if ($order->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'order' => ['Order already paid.'],
            ]);
        }

        $order->payments()->create([
            'method' => 'cash',
            'amount' => $order->grand_total,
            'paid_at' => now(),
        ]);

        $order->update([
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        $this->log($order->id, $order->status, $order->status, $userId, 'paid cash');

        return $order->fresh();
    }

    protected function recalculate(Order $order): void
    {
        $subtotal = $order->items()->sum('total_price');

        // Calculate taxes
        $taxes = Tax::where('outlet_id', $order->outlet_id)
            ->where('is_active', true)
            ->get();
        $taxTotal = 0;
        foreach ($taxes as $tax) {
            $taxTotal += (int) round($subtotal * $tax->rate / 100);
        }

        // Calculate discounts from pivot
        $discountTotal = $order->discounts()->sum('discount_amount');

        $grandTotal = $subtotal + $taxTotal - $discountTotal;

        $order->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'grand_total' => max($grandTotal, 0),
        ]);
    }

    protected function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? []);
    }

    protected function log(int $orderId, ?string $from, string $to, int $userId, ?string $note = null): void
    {
        OrderLog::create([
            'order_id' => $orderId,
            'from_status' => $from,
            'to_status' => $to,
            'user_id' => $userId,
            'note' => $note,
        ]);
    }
}
