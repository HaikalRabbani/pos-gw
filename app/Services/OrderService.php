<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Discount;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    const STATUSES = [
        'draft', 'confirmed', 'preparing', 'done', 'cancelled', 'voided',
    ];

    const TRANSITIONS = [
        'draft'     => ['confirmed', 'voided'],
        'confirmed' => ['preparing', 'voided'],
        'preparing' => ['done', 'cancelled'],
        'done'      => [],
        'cancelled' => [],
        'voided'    => [],
    ];

    public function createDraft(array $data): Order
    {
        $order = Order::create([
            'outlet_id'     => $data['outlet_id'],
            'table_id'      => $data['table_id'] ?? null,
            'user_id'       => $data['user_id'],
            'customer_name' => $data['customer_name'] ?? null,
            'status'        => 'draft',
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
        $product  = Product::find($data['product_id']);
        $unitCost = $product ? $product->cost : 0;

        $item = $order->items()->create([
            'product_id'   => $data['product_id'],
            'variant_id'   => $data['variant_id'] ?? null,
            'product_name' => $data['product_name'],
            'variant_name' => $data['variant_name'] ?? null,
            'qty'          => $data['qty'],
            'unit_price'   => $data['unit_price'],
            'unit_cost'    => $unitCost,
            'total_price'  => $data['unit_price'] * $data['qty'],
            'notes'        => $data['notes'] ?? null,
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

        return DB::transaction(function () use ($order, $userId) {
            $order->payments()->create([
                'method'  => 'cash',
                'amount'  => $order->grand_total,
                'paid_at' => now(),
            ]);

            $order->update([
                'payment_status' => 'paid',
                'payment_method' => 'cash',
            ]);

            $this->deductStock($order);

            $this->log($order->id, $order->status, $order->status, $userId, 'paid cash');

            return $order->fresh();
        });
    }

    /**
     * Deduct stock after payment based on outlet's stock_mode.
     */
    protected function deductStock(Order $order): void
    {
        $outlet = Outlet::find($order->outlet_id);
        if (!$outlet) return;

        if ($outlet->stock_mode === 'product') {
            // Kurangi stok per produk
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    Product::where('id', $item->product_id)
                        ->where('stock', '>=', $item->qty)
                        ->decrement('stock', $item->qty);
                }
            }
        } elseif ($outlet->stock_mode === 'ingredient') {
            // Kurangi stok per ingredient berdasarkan product_ingredients
            $productIds = $order->items->pluck('product_id')->filter()->unique();
            $productIngredients = \DB::table('product_ingredients')
                ->whereIn('product_id', $productIds)
                ->get()
                ->groupBy('product_id');

            foreach ($order->items as $item) {
                if (!$item->product_id) continue;
                $ingredients = $productIngredients->get($item->product_id, collect());
                foreach ($ingredients as $pi) {
                    Ingredient::where('id', $pi->ingredient_id)
                        ->where('stock', '>=', $item->qty)
                        ->decrement('stock', $item->qty);
                }
            }
        }
    }

    protected function recalculate(Order $order): void
    {
        $items    = $order->items()->get(); // single query, reuse
        $subtotal = $items->sum('total_price');

        // Hitung diskon kompleks (otomatis dari master diskon aktif)
        $discountTotal = $this->calculateDiscounts($order, $subtotal, $items);

        $afterDiscount = max($subtotal - $discountTotal, 0);

        // Pajak bertingkat (sequential): setiap pajak diterapkan ke running total
        $taxes = Tax::where('outlet_id', $order->outlet_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $runningTotal = $afterDiscount;
        $taxTotal     = 0;
        foreach ($taxes as $tax) {
            $taxAmount     = (int) round($runningTotal * $tax->rate / 100);
            $taxTotal     += $taxAmount;
            $runningTotal += $taxAmount;
        }

        $grandTotal = max($runningTotal, 0);

        $order->update([
            'subtotal'       => $subtotal,
            'tax_total'      => $taxTotal,
            'discount_total' => $discountTotal,
            'grand_total'    => $grandTotal,
        ]);
    }

    /**
     * Hitung diskon otomatis dari master diskon aktif.
     * Mempertimbangkan: target_type, min_purchase, max_discount, buy_x_get_y, masa aktif.
     */
    protected function calculateDiscounts(Order $order, int $subtotal, $items): int
    {
        $now           = now();
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
            $targetIds = (array) $discount->target_id;
            return (int) $items->whereIn('product_id', $targetIds)->sum('total_price');
        }

        if ($discount->target_type === 'category' && $discount->target_id) {
            $targetIds = (array) $discount->target_id;
            $productIds = Product::whereIn('category_id', $targetIds)->pluck('id');
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
            $targetIds   = (array) $discount->target_id;
            $targetItems = $items->whereIn('product_id', $targetIds);
        } elseif ($discount->target_type === 'category' && $discount->target_id) {
            $targetIds   = (array) $discount->target_id;
            $productIds  = Product::whereIn('category_id', $targetIds)->pluck('id');
            $targetItems = $items->whereIn('product_id', $productIds);
        }

        $totalQty = (int) $targetItems->sum('qty');
        $setSize  = $discount->buy_x + $discount->buy_y;
        if ($setSize <= 0 || $totalQty < $setSize) return 0;

        $sets      = intdiv($totalQty, $setSize);
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

    /**
     * Refund per-item.
     *
     * @param array $itemsData [{order_item_id: int, qty: int}, ...]
     */
    public function refund(Order $order, int $userId, array $itemsData, ?string $reason = null): Order
    {
        if ($order->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'order' => ['Order belum dibayar, tidak bisa refund.'],
            ]);
        }

        if ($order->refund_status === 'full') {
            throw ValidationException::withMessages([
                'order' => ['Order sudah di-refund penuh.'],
            ]);
        }

        return DB::transaction(function () use ($order, $userId, $itemsData, $reason) {
            $order->load('items');
            $itemMap           = $order->items->keyBy('id');
            $totalRefundAmount = 0;

            // PASS 1: validasi semua item dulu, belum ada yang diubah ke DB
            foreach ($itemsData as $data) {
                $item = $itemMap->get($data['order_item_id']);
                if (!$item) {
                    throw ValidationException::withMessages([
                        'items' => ["Item ID {$data['order_item_id']} tidak ditemukan di pesanan ini."],
                    ]);
                }

                $refundQty = (int) $data['qty'];
                if ($refundQty <= 0) continue;

                $refundableQty = $item->refundable_qty;
                if ($refundQty > $refundableQty) {
                    throw ValidationException::withMessages([
                        'items' => ["Item '{$item->product_name}' hanya bisa di-refund {$refundableQty} (dari {$item->qty})."],
                    ]);
                }

                // Hitung refund amount: proporsional dari total_price
                $refundAmount       = (int) round(($item->total_price / $item->qty) * $refundQty);
                $totalRefundAmount += $refundAmount;
            }

            if ($totalRefundAmount <= 0) {
                throw ValidationException::withMessages([
                    'items' => ['Tidak ada item yang di-refund.'],
                ]);
            }

            // Cek apakah refund melebihi total yang dibayar
            $totalRefundedSoFar  = (int) $order->payments()->sum('refunded_amount');
            $totalPaid           = (int) $order->payments()->sum('amount');
            $remainingRefundable = $totalPaid - $totalRefundedSoFar;

            if ($totalRefundAmount > $remainingRefundable) {
                throw ValidationException::withMessages([
                    'items' => ["Jumlah refund melebihi sisa refundable (Rp " . number_format($remainingRefundable, 0, ',', '.') . ")."],
                ]);
            }

            // PASS 2: semua validasi lulus, baru eksekusi perubahan data
            foreach ($itemsData as $data) {
                $item      = $itemMap->get($data['order_item_id']);
                $refundQty = (int) $data['qty'];
                if (!$item || $refundQty <= 0) continue;
                $item->increment('refunded_qty', $refundQty);
            }

            // Apply refund ke payment
            $remainingAmount = $totalRefundAmount;
            $payments        = $order->payments()->whereColumn('amount', '>', 'refunded_amount')->get();
            foreach ($payments as $payment) {
                if ($remainingAmount <= 0) break;
                $refundable = $payment->refundable_amount;
                $toRefund   = min($remainingAmount, $refundable);
                $payment->increment('refunded_amount', $toRefund);
                $remainingAmount -= $toRefund;
            }

            // Tentukan refund_status: full jika SEMUA item sudah refunded_qty = qty
            // Reload items untuk dapetin refunded_qty terbaru setelah increment
            $order->load('items');
            $allFullyRefunded = $order->items->every(fn($i) => $i->refundable_qty === 0);
            $refundStatus     = $allFullyRefunded ? 'full' : 'partial';

            $order->update([
                'refund_status' => $refundStatus,
                'refund_note'   => $reason,
                'refunded_at'   => now(),
                'refunded_by'   => $userId,
            ]);

            $itemDetails = collect($itemsData)->map(fn($d) => $itemMap->get($d['order_item_id'])?->product_name . ' x' . $d['qty'])->filter()->implode(', ');
            $this->log($order->id, $order->status, $order->status, $userId, 'refund: Rp ' . number_format($totalRefundAmount, 0, ',', '.') . ' — ' . $itemDetails . ($reason ? ' (' . $reason . ')' : ''));

            return $order->fresh()->load('items', 'payments', 'logs.user');
        });
    }

    /**
     * Split an order into multiple sub-orders (split bill).
     *
     * @param array $splits [{customer_name, items: [{order_item_id, qty}]}]
     */
    public function splitOrder(Order $order, int $userId, array $splits): array
    {
        if (!in_array($order->status, ['draft', 'confirmed'])) {
            throw ValidationException::withMessages([
                'order' => ['Hanya pesanan draft/confirmed yang bisa di-split.'],
            ]);
        }

        if ($order->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'order' => ['Pesanan sudah dibayar, tidak bisa di-split.'],
            ]);
        }

        $order->load('items');

        return DB::transaction(function () use ($order, $userId, $splits) {
            $billGroupId = (string) Str::uuid();
            $newOrders = [];

            // Build item map for validation
            $itemMap = $order->items->keyBy('id');

            // Validate: sum of split qty equals original qty for each item
            $totalSplitQty = [];
            foreach ($splits as $split) {
                foreach ($split['items'] as $itemData) {
                    $itemId = $itemData['order_item_id'];
                    $qty = (int) $itemData['qty'];
                    if (!isset($itemMap[$itemId])) {
                        throw ValidationException::withMessages([
                            'splits' => ["Item ID {$itemId} tidak ditemukan."],
                        ]);
                    }
                    $totalSplitQty[$itemId] = ($totalSplitQty[$itemId] ?? 0) + $qty;
                }
            }

            foreach ($itemMap as $itemId => $item) {
                $totalQty = $totalSplitQty[$itemId] ?? 0;
                if ($totalQty !== $item->qty) {
                    throw ValidationException::withMessages([
                        'splits' => ["Item '{$item->product_name}' harus di-split total {$item->qty}, tapi hanya {$totalQty}."],
                    ]);
                }
            }

            // Create new orders for each split
            foreach ($splits as $split) {
                $newOrder = Order::create([
                    'outlet_id'     => $order->outlet_id,
                    'table_id'      => $order->table_id,
                    'user_id'       => $userId,
                    'customer_name' => $split['customer_name'] ?? null,
                    'status'        => 'draft',
                    'bill_group_id' => $billGroupId,
                ]);

                // Create items for the new order
                foreach ($split['items'] as $itemData) {
                    $origItem = $itemMap[$itemData['order_item_id']];
                    $qty = (int) $itemData['qty'];
                    if ($qty <= 0) continue;

                    $newOrder->items()->create([
                        'product_id'   => $origItem->product_id,
                        'variant_id'   => $origItem->variant_id,
                        'product_name' => $origItem->product_name,
                        'variant_name' => $origItem->variant_name,
                        'qty'          => $qty,
                        'unit_price'   => $origItem->unit_price,
                        'unit_cost'    => $origItem->unit_cost,
                        'total_price'  => $origItem->unit_price * $qty,
                        'notes'        => $origItem->notes,
                    ]);
                }

                // Recalculate taxes for each split
                $this->recalculate($newOrder);
                $this->log($newOrder->id, null, 'draft', $userId, 'split from order #' . $order->id);

                $newOrders[] = $newOrder->fresh()->load('items');
            }

            // Void the original order with note
            $this->updateStatus($order, 'voided', $userId, 'Split bill — ' . count($splits) . ' tagihan (group: ' . $billGroupId . ')');
            $order->update(['bill_group_id' => $billGroupId]);

            return $newOrders;
        });
    }

    /**
     * Merge multiple orders into a single order (merge bill).
     */
    public function mergeOrders(array $orders, int $userId, ?string $customerName = null): Order
    {
        if (count($orders) < 2) {
            throw ValidationException::withMessages([
                'orders' => ['Minimal 2 pesanan untuk merge.'],
            ]);
        }

        $outletId = null;
        $tableId = null;

        foreach ($orders as $order) {
            if (!in_array($order->status, ['draft', 'confirmed'])) {
                throw ValidationException::withMessages([
                    'orders' => ["Pesanan #{$order->id} bukan draft/confirmed."],
                ]);
            }
            if ($order->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'orders' => ["Pesanan #{$order->id} sudah dibayar."],
                ]);
            }

            if ($outletId === null) {
                $outletId = $order->outlet_id;
                $tableId = $order->table_id;
            } elseif ($order->outlet_id !== $outletId) {
                throw ValidationException::withMessages([
                    'orders' => ['Semua pesanan harus dari outlet yang sama.'],
                ]);
            }

            $order->load('items');
        }

        $billGroupId = (string) Str::uuid();

        return DB::transaction(function () use ($orders, $userId, $customerName, $outletId, $tableId, $billGroupId) {
            // Create new merged order
            $mergedOrder = Order::create([
                'outlet_id'     => $outletId,
                'table_id'      => $tableId,
                'user_id'       => $userId,
                'customer_name' => $customerName ?? 'Merged Bill',
                'status'        => 'draft',
                'bill_group_id' => $billGroupId,
            ]);

            // Move all items from original orders to merged order
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    $mergedOrder->items()->create([
                        'product_id'   => $item->product_id,
                        'variant_id'   => $item->variant_id,
                        'product_name' => $item->product_name,
                        'variant_name' => $item->variant_name,
                        'qty'          => $item->qty,
                        'unit_price'   => $item->unit_price,
                        'unit_cost'    => $item->unit_cost,
                        'total_price'  => $item->total_price,
                        'notes'        => $item->notes,
                    ]);
                }

                // Void the original order
                $this->updateStatus($order, 'voided', $userId, 'Merged into order #' . $mergedOrder->id);
                $order->update(['bill_group_id' => $billGroupId]);
            }

            // Recalculate the merged order
            $this->recalculate($mergedOrder);
            $this->log($mergedOrder->id, null, 'draft', $userId, 'merged from ' . count($orders) . ' orders');

            return $mergedOrder->fresh()->load('items');
        });
    }

    protected function log(int $orderId, ?string $from, string $to, int $userId, ?string $note = null): void
    {
        OrderLog::create([
            'order_id'    => $orderId,
            'from_status' => $from,
            'to_status'   => $to,
            'user_id'     => $userId,
            'note'        => $note,
        ]);
    }
}
