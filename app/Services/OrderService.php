<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Product;
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

        // Capture unit_cost from product for HPP calculation
        $product = Product::find($data['product_id']);
        $unitCost = $product ? $product->cost : 0;

        $item = $order->items()->create([
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'] ?? null,
            'product_name' => $data['product_name'],
            'variant_name' => $data['variant_name'] ?? null,
            'qty' => $data['qty'],
            'unit_price' => $data['unit_price'],
            'unit_cost' => $unitCost,
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
        $items = $order->items;

        // Hitung diskon kompleks (otomatis dari master diskon aktif)
        $discountTotal = $this->calculateDiscounts($order, $subtotal, $items);

        $afterDiscount = max($subtotal - $discountTotal, 0);

        // Pajak bertingkat (sequential): setiap pajak diterapkan ke running total
        $taxes = Tax::where('outlet_id', $order->outlet_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $runningTotal = $afterDiscount;
        $taxTotal = 0;
        foreach ($taxes as $tax) {
            $taxAmount = (int) round($runningTotal * $tax->rate / 100);
            $taxTotal += $taxAmount;
            $runningTotal += $taxAmount;
        }

        $grandTotal = max($runningTotal, 0);

        $order->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
        ]);
    }

    /**
     * Hitung diskon otomatis dari master diskon aktif.
     * Mempertimbangkan: target_type, min_purchase, max_discount, buy_x_get_y, masa aktif.
     */
    protected function calculateDiscounts(Order $order, int $subtotal, $items): int
    {
        $now = now();
        $totalDiscount = 0;

        $discounts = Discount::where('outlet_id', $order->outlet_id)
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            })
            ->get();

        foreach ($discounts as $discount) {
            // Skip jika min_purchase tidak terpenuhi
            if ($discount->min_purchase && $subtotal < $discount->min_purchase) {
                continue;
            }

            $applicableSubtotal = $this->getApplicableSubtotal($discount, $items, $subtotal);
            if ($applicableSubtotal <= 0) continue;

            $amount = $this->calculateDiscountAmount($discount, $applicableSubtotal, $items);

            // Cap oleh max_discount
            if ($discount->max_discount && $amount > $discount->max_discount) {
                $amount = $discount->max_discount;
            }

            $totalDiscount += $amount;
        }

        return $totalDiscount;
    }

    /**
     * Dapatkan subtotal yang terkena diskon berdasarkan target_type.
     */
    protected function getApplicableSubtotal($discount, $items, int $subtotal): int
    {
        if ($discount->target_type === 'product' && $discount->target_id) {
            return (int) $items->where('product_id', $discount->target_id)->sum('total_price');
        }

        if ($discount->target_type === 'category' && $discount->target_id) {
            $productIds = Product::where('category_id', $discount->target_id)->pluck('id');
            return (int) $items->whereIn('product_id', $productIds)->sum('total_price');
        }

        // transaction-level: berlaku ke seluruh subtotal
        return $subtotal;
    }

    /**
     * Hitung nilai nominal diskon berdasarkan tipe.
     */
    protected function calculateDiscountAmount($discount, int $applicableSubtotal, $items): int
    {
        // Buy X Get Y
        if ($discount->buy_x && $discount->buy_y) {
            return $this->calculateBuyXGetY($discount, $items);
        }

        if ($discount->type === 'percent') {
            return (int) round($applicableSubtotal * $discount->value / 100);
        }

        // nominal
        return $discount->value;
    }

    /**
     * Hitung diskon Buy X Get Y.
     * Ambil item termurah sebagai yang digratiskan.
     */
    protected function calculateBuyXGetY($discount, $items): int
    {
        $targetItems = $items;
        if ($discount->target_type === 'product' && $discount->target_id) {
            $targetItems = $items->where('product_id', $discount->target_id);
        } elseif ($discount->target_type === 'category' && $discount->target_id) {
            $productIds = Product::where('category_id', $discount->target_id)->pluck('id');
            $targetItems = $items->whereIn('product_id', $productIds);
        }

        $totalQty = (int) $targetItems->sum('qty');
        $setSize = $discount->buy_x + $discount->buy_y;
        if ($setSize <= 0 || $totalQty < $setSize) return 0;

        $sets = intdiv($totalQty, $setSize);
        $freeCount = $sets * $discount->buy_y;

        // Kumpulkan semua harga satuan item, lalu ambil termurah untuk digratiskan
        $prices = [];
        foreach ($targetItems as $item) {
            for ($i = 0; $i < $item->qty; $i++) {
                $prices[] = $item->unit_price;
            }
        }
        sort($prices);

        $freePrices = array_slice($prices, 0, $freeCount);
        return (int) array_sum($freePrices);
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
