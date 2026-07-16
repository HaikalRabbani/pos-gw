<?php

namespace Tests\Unit;

use App\Models\Discount;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;
    private Outlet $outlet;
    private User $user;
    private Product $product1;
    private Product $product2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderService::class);
        $this->outlet = Outlet::factory()->create();
        $this->user = User::factory()->create();
        $this->product1 = Product::create([
            'outlet_id' => $this->outlet->id,
            'category_id' => null,
            'name' => 'Nasi Goreng',
            'price' => 25000,
            'cost' => 10000,
            'is_active' => true,
        ]);
        $this->product2 = Product::create([
            'outlet_id' => $this->outlet->id,
            'category_id' => null,
            'name' => 'Es Teh Manis',
            'price' => 5000,
            'cost' => 1000,
            'is_active' => true,
        ]);
    }

    // ──────────────────────────────────────────────
    // CREATE DRAFT & ITEMS
    // ──────────────────────────────────────────────

    public function test_can_create_draft_order(): void
    {
        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('draft', $order->status);
        $this->assertEquals($this->outlet->id, $order->outlet_id);
    }

    public function test_can_add_item_to_draft(): void
    {
        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 2,
            'unit_price' => $this->product1->price,
        ]);

        $this->assertCount(1, $order->items);
        $this->assertEquals(2, $order->items->first()->qty);
        $this->assertEquals(50000, $order->items->first()->total_price); // 2 * 25000
        $this->assertEquals(10000, $order->items->first()->unit_cost);  // from product cost
    }

    public function test_can_remove_item_from_draft(): void
    {
        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => $this->product1->price,
        ]);
        $itemId = $order->items->first()->id;

        $order = $this->service->removeItem($order, $itemId);

        $this->assertCount(0, $order->items);
    }

    public function test_cannot_add_item_to_non_draft_order(): void
    {
        $this->expectException(ValidationException::class);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        // Confirm the order to move out of draft
        $order->update(['status' => 'confirmed']);

        $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => $this->product1->price,
        ]);
    }

    // ──────────────────────────────────────────────
    // STATUS TRANSITIONS
    // ──────────────────────────────────────────────

    public function test_can_transition_status_valid(): void
    {
        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $order = $this->service->updateStatus($order, 'confirmed', $this->user->id);
        $this->assertEquals('confirmed', $order->status);

        $order = $this->service->updateStatus($order, 'preparing', $this->user->id);
        $this->assertEquals('preparing', $order->status);

        $order = $this->service->updateStatus($order, 'done', $this->user->id);
        $this->assertEquals('done', $order->status);
    }

    public function test_cannot_transition_status_invalid(): void
    {
        $this->expectException(ValidationException::class);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        // Cannot go directly from draft to done
        $this->service->updateStatus($order, 'done', $this->user->id);
    }

    // ──────────────────────────────────────────────
    // PAY CASH
    // ──────────────────────────────────────────────

    public function test_can_pay_cash(): void
    {
        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => $this->product1->price,
        ]);

        $order = $this->service->payCash($order, $this->user->id);

        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('cash', $order->payment_method);
        $this->assertCount(1, $order->payments);
        $this->assertEquals($order->grand_total, $order->payments->first()->amount);
    }

    public function test_cannot_pay_already_paid_order(): void
    {
        $this->expectException(ValidationException::class);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => $this->product1->price,
        ]);
        $order = $this->service->payCash($order, $this->user->id);

        // Pay again should fail
        $this->service->payCash($order, $this->user->id);
    }

    // ──────────────────────────────────────────────
    // SPLIT ORDER
    // ──────────────────────────────────────────────

    public function test_can_split_order(): void
    {
        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 2,
            'unit_price' => $this->product1->price,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product2->id,
            'product_name' => $this->product2->name,
            'qty' => 1,
            'unit_price' => $this->product2->price,
        ]);

        $item1 = $order->items[0]; // Nasi Goreng x2
        $item2 = $order->items[1]; // Es Teh Manis x1

        $splits = [
            [
                'customer_name' => 'Meja A',
                'items' => [
                    ['order_item_id' => $item1->id, 'qty' => 1],
                    ['order_item_id' => $item2->id, 'qty' => 1],
                ],
            ],
            [
                'customer_name' => 'Meja B',
                'items' => [
                    ['order_item_id' => $item1->id, 'qty' => 1],
                ],
            ],
        ];

        $newOrders = $this->service->splitOrder($order, $this->user->id, $splits);

        $this->assertCount(2, $newOrders);

        // Check original is now voided
        $order->refresh();
        $this->assertEquals('voided', $order->status);

        // Check first split has 2 items (Nasi Goreng x1 + Es Teh x1)
        $this->assertCount(2, $newOrders[0]->items);
        $this->assertEquals('Meja A', $newOrders[0]->customer_name);
        $this->assertEquals(25000 + 5000, $newOrders[0]->items->sum('total_price'));

        // Check second split has 1 item (Nasi Goreng x1)
        $this->assertCount(1, $newOrders[1]->items);
        $this->assertEquals('Meja B', $newOrders[1]->customer_name);
        $this->assertEquals(25000, $newOrders[1]->items->first()->total_price);

        // Verify bill_group_id is set on all orders
        $this->assertNotNull($newOrders[0]->bill_group_id);
        $this->assertEquals($newOrders[0]->bill_group_id, $newOrders[1]->bill_group_id);
        $this->assertEquals($newOrders[0]->bill_group_id, $order->bill_group_id);
    }

    public function test_cannot_split_if_qty_mismatch(): void
    {
        $this->expectException(ValidationException::class);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 2,
            'unit_price' => $this->product1->price,
        ]);

        $item = $order->items->first();

        // Only split 1 of 2 — should fail
        $splits = [
            [
                'customer_name' => 'Meja A',
                'items' => [
                    ['order_item_id' => $item->id, 'qty' => 1],
                ],
            ],
        ];

        $this->service->splitOrder($order, $this->user->id, $splits);
    }

    public function test_cannot_split_paid_order(): void
    {
        $this->expectException(ValidationException::class);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => $this->product1->price,
        ]);
        $order = $this->service->payCash($order, $this->user->id);

        $item = $order->items->first();
        $splits = [
            [
                'customer_name' => 'Meja A',
                'items' => [['order_item_id' => $item->id, 'qty' => 1]],
            ],
        ];

        $this->service->splitOrder($order, $this->user->id, $splits);
    }

    // ──────────────────────────────────────────────
    // MERGE ORDERS
    // ──────────────────────────────────────────────

    public function test_can_merge_orders(): void
    {
        $order1 = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'customer_name' => 'Meja A',
        ]);
        $order1 = $this->service->addItem($order1, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => $this->product1->price,
        ]);

        $order2 = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'customer_name' => 'Meja B',
        ]);
        $order2 = $this->service->addItem($order2, [
            'product_id' => $this->product2->id,
            'product_name' => $this->product2->name,
            'qty' => 2,
            'unit_price' => $this->product2->price,
        ]);

        $merged = $this->service->mergeOrders(
            [$order1, $order2],
            $this->user->id,
            'Gabungan'
        );

        $this->assertEquals('draft', $merged->status);
        $this->assertEquals('Gabungan', $merged->customer_name);
        $this->assertCount(2, $merged->items); // 1 item from order1 + 1 from order2 (same product? no, different)

        // Check original orders are voided
        $order1->refresh();
        $order2->refresh();
        $this->assertEquals('voided', $order1->status);
        $this->assertEquals('voided', $order2->status);

        // Check merged items: Nasi Goreng x1 + Es Teh x2
        $mergedItems = $merged->items;
        $this->assertEquals(2, $mergedItems->count());
        $this->assertEquals(25000 + (5000 * 2), $merged->items->sum('total_price'));

        // Verify bill_group_id linkage
        $this->assertNotNull($merged->bill_group_id);
        $this->assertEquals($merged->bill_group_id, $order1->bill_group_id);
        $this->assertEquals($merged->bill_group_id, $order2->bill_group_id);
    }

    public function test_cannot_merge_less_than_2_orders(): void
    {
        $this->expectException(ValidationException::class);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $this->service->mergeOrders([$order], $this->user->id);
    }

    public function test_cannot_merge_different_outlets(): void
    {
        $this->expectException(ValidationException::class);

        $outlet2 = Outlet::factory()->create();

        $order1 = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order2 = $this->service->createDraft([
            'outlet_id' => $outlet2->id,
            'user_id' => $this->user->id,
        ]);

        $this->service->mergeOrders([$order1, $order2], $this->user->id);
    }

    public function test_cannot_merge_paid_order(): void
    {
        $this->expectException(ValidationException::class);

        $order1 = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order1 = $this->service->addItem($order1, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => $this->product1->price,
        ]);
        $order1 = $this->service->payCash($order1, $this->user->id);

        $order2 = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);

        $this->service->mergeOrders([$order1, $order2], $this->user->id);
    }

    // ──────────────────────────────────────────────
    // REFUND
    // ──────────────────────────────────────────────

    public function test_can_refund_partial(): void
    {
        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 2,
            'unit_price' => $this->product1->price,
        ]);
        $order = $this->service->payCash($order, $this->user->id);

        $item = $order->items->first();

        // Refund 1 of 2 items
        $order = $this->service->refund($order, $this->user->id, [
            ['order_item_id' => $item->id, 'qty' => 1],
        ], 'Batal 1 porsi');

        $this->assertEquals('partial', $order->refund_status);
        $this->assertEquals('Batal 1 porsi', $order->refund_note);
        $this->assertNotNull($order->refunded_by);

        // Check refunded_qty on item
        $item->refresh();
        $this->assertEquals(1, $item->refunded_qty);
        $this->assertEquals(1, $item->refundable_qty); // 2 - 1 = 1 remaining

        // Check payment refunded_amount
        $this->assertEquals(25000, $order->payments->first()->refunded_amount); // proporsional: 50000/2 * 1 = 25000
    }

    public function test_can_refund_full(): void
    {
        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => $this->product1->price,
        ]);
        $order = $this->service->payCash($order, $this->user->id);

        $item = $order->items->first();

        $order = $this->service->refund($order, $this->user->id, [
            ['order_item_id' => $item->id, 'qty' => 1],
        ]);

        $this->assertEquals('full', $order->refund_status);

        $item->refresh();
        $this->assertEquals(1, $item->refunded_qty);
        $this->assertEquals(0, $item->refundable_qty);
    }

    public function test_cannot_refund_more_than_quantity(): void
    {
        $this->expectException(ValidationException::class);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => $this->product1->price,
        ]);
        $order = $this->service->payCash($order, $this->user->id);

        $item = $order->items->first();

        $this->service->refund($order, $this->user->id, [
            ['order_item_id' => $item->id, 'qty' => 5], // Only 1 available
        ]);
    }

    public function test_cannot_refund_not_paid(): void
    {
        $this->expectException(ValidationException::class);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => $this->product1->price,
        ]);

        $item = $order->items->first();
        $this->service->refund($order, $this->user->id, [
            ['order_item_id' => $item->id, 'qty' => 1],
        ]);
    }

    // ──────────────────────────────────────────────
    // TAX CALCULATION (SEQUENTIAL)
    // ──────────────────────────────────────────────

    public function test_tax_calculation_sequential(): void
    {
        // Create taxes with sort_order
        Tax::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Service Charge',
            'rate' => 5,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        Tax::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'PPN',
            'rate' => 11,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => 10000,
        ]);

        $order->refresh();

        // Sequential: 10000 -> +5% (500) = 10500 -> +11% (1155) = 11655
        $this->assertEquals(10000, $order->subtotal);
        $this->assertEquals(0, $order->discount_total);
        // 500 + 1155 = 1655
        $this->assertEquals(1655, $order->tax_total);
        $this->assertEquals(11655, $order->grand_total);
    }

    // ──────────────────────────────────────────────
    // DISCOUNT CALCULATION
    // ──────────────────────────────────────────────

    public function test_discount_percentage(): void
    {
        Discount::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Diskon 10%',
            'type' => 'percent',
            'value' => 10,
            'target_type' => 'transaction',
            'is_active' => true,
        ]);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => 50000,
        ]);

        $order->refresh();

        // subtotal: 50000, discount: 10% = 5000, after discount: 45000
        $this->assertEquals(5000, $order->discount_total);
        $this->assertEquals(45000, $order->subtotal - $order->discount_total);
    }

    public function test_discount_nominal(): void
    {
        Discount::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Diskon Rp 5.000',
            'type' => 'nominal',
            'value' => 5000,
            'target_type' => 'transaction',
            'is_active' => true,
        ]);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => 50000,
        ]);

        $order->refresh();

        $this->assertEquals(5000, $order->discount_total);
    }

    public function test_discount_min_purchase(): void
    {
        Discount::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Diskon 10% min 100rb',
            'type' => 'percent',
            'value' => 10,
            'target_type' => 'transaction',
            'min_purchase' => 100000,
            'is_active' => true,
        ]);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => 50000, // Only 50000, min_purchase is 100000
        ]);

        $order->refresh();

        // Discount should NOT apply because subtotal (50000) < min_purchase (100000)
        $this->assertEquals(0, $order->discount_total);
    }

    public function test_discount_product_specific(): void
    {
        Discount::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Diskon Es Teh',
            'type' => 'percent',
            'value' => 20,
            'target_type' => 'product',
            'target_id' => [$this->product2->id],
            'is_active' => true,
        ]);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => 50000,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product2->id,
            'product_name' => $this->product2->name,
            'qty' => 2,
            'unit_price' => 5000,
        ]);

        $order->refresh();

        // Discount only applies to Es Teh (product2): 2 * 5000 = 10000 subtotal
        // 20% of 10000 = 2000
        $this->assertEquals(2000, $order->discount_total);
    }

    public function test_discount_buy_x_get_y(): void
    {
        Discount::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Beli 2 Gratis 1',
            'type' => 'percent', // but BOGO overrides this
            'value' => 0,
            'target_type' => 'product',
            'target_id' => [$this->product1->id],
            'buy_x' => 2,
            'buy_y' => 1,
            'is_active' => true,
        ]);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 3,
            'unit_price' => 10000,
        ]);

        $order->refresh();

        // Buy 2 Get 1: 3 items total, setSize = 3, 1 set, 1 free
        // Prices sorted: [10000, 10000, 10000], free = cheapest 1 = 10000
        $this->assertEquals(10000, $order->discount_total);
    }

    public function test_discount_max_cap(): void
    {
        Discount::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Diskon 50% max 10rb',
            'type' => 'percent',
            'value' => 50,
            'target_type' => 'transaction',
            'max_discount' => 10000,
            'is_active' => true,
        ]);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 1,
            'unit_price' => 100000,
        ]);

        $order->refresh();

        // 50% of 100000 = 50000, but max_discount is 10000
        $this->assertEquals(10000, $order->discount_total);
    }

    // ──────────────────────────────────────────────
    // INTEGRATION: SPLIT + TAX
    // ──────────────────────────────────────────────

    public function test_split_preserves_tax_calculation(): void
    {
        Tax::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'PPN',
            'rate' => 11,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $order = $this->service->createDraft([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
        ]);
        $order = $this->service->addItem($order, [
            'product_id' => $this->product1->id,
            'product_name' => $this->product1->name,
            'qty' => 2,
            'unit_price' => 10000, // subtotal: 20000
        ]);

        $item = $order->items->first();

        $splits = [
            [
                'customer_name' => 'A',
                'items' => [['order_item_id' => $item->id, 'qty' => 1]],
            ],
            [
                'customer_name' => 'B',
                'items' => [['order_item_id' => $item->id, 'qty' => 1]],
            ],
        ];

        $newOrders = $this->service->splitOrder($order, $this->user->id, $splits);

        // Each split: subtotal=10000, PPN 11%=1100, grand_total=11100
        $this->assertEquals(10000, $newOrders[0]->subtotal);
        $this->assertEquals(1100, $newOrders[0]->tax_total);
        $this->assertEquals(11100, $newOrders[0]->grand_total);

        $this->assertEquals(10000, $newOrders[1]->subtotal);
        $this->assertEquals(1100, $newOrders[1]->tax_total);
        $this->assertEquals(11100, $newOrders[1]->grand_total);
    }
}
