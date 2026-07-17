<?php

namespace Tests\Unit;

use App\Models\Discount;
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

    protected OrderService $service;
    protected Outlet $outlet;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderService();
        $this->outlet = Outlet::factory()->create();
        $this->user = User::factory()->create();
    }

    protected function makeProduct(int $price, int $cost = 0): Product
    {
        return Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Test Product',
            'price' => $price,
            'cost' => $cost,
            'is_active' => true,
        ]);
    }

    public function test_recalculate_applies_single_tax_correctly(): void
    {
        Tax::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'PPN',
            'rate' => 11,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $product = $this->makeProduct(price: 1000000); // Rp 10.000

        $order = $this->service->createDraft(['outlet_id' => $this->outlet->id, 'user_id' => $this->user->id]);
        $order = $this->service->addItem($order, [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'qty' => 2,
            'unit_price' => 1000000,
        ]);

        $this->assertEquals(2000000, $order->subtotal);
        $this->assertEquals(220000, $order->tax_total); // 11% dari 2.000.000
        $this->assertEquals(2220000, $order->grand_total);
    }

    public function test_sequential_tax_applies_on_running_total_not_flat_subtotal(): void
    {
        // Service Charge dulu (5%), baru PPN (11%) dihitung dari total SETELAH service charge
        Tax::create(['outlet_id' => $this->outlet->id, 'name' => 'Service Charge', 'rate' => 5, 'sort_order' => 0, 'is_active' => true]);
        Tax::create(['outlet_id' => $this->outlet->id, 'name' => 'PPN', 'rate' => 11, 'sort_order' => 1, 'is_active' => true]);

        $product = $this->makeProduct(price: 1000000);
        $order = $this->service->createDraft(['outlet_id' => $this->outlet->id, 'user_id' => $this->user->id]);
        $order = $this->service->addItem($order, [
            'product_id' => $product->id, 'product_name' => $product->name, 'qty' => 1, 'unit_price' => 1000000,
        ]);

        // Service charge: 5% x 1.000.000 = 50.000, running total = 1.050.000
        // PPN: 11% x 1.050.000 = 115.500
        $this->assertEquals(165500, $order->tax_total); // 50.000 + 115.500
        $this->assertEquals(1165500, $order->grand_total);
    }

    public function test_product_target_discount_with_multi_product_array(): void
    {
        // Regresi check: sejak target_id di-cast 'array' (multi-produk),
        // OrderService lama pakai equality check ke scalar sehingga
        // diskon product/category gak pernah kena sama sekali.
        $productA = $this->makeProduct(price: 1000000);
        $productB = $this->makeProduct(price: 500000);
        $productC = $this->makeProduct(price: 200000); // di luar target, gak boleh kena diskon

        Discount::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Promo A+B',
            'type' => 'percent',
            'value' => 10,
            'target_type' => 'product',
            'target_id' => [$productA->id, $productB->id],
            'is_active' => true,
        ]);

        $order = $this->service->createDraft(['outlet_id' => $this->outlet->id, 'user_id' => $this->user->id]);
        $order = $this->service->addItem($order, ['product_id' => $productA->id, 'product_name' => 'A', 'qty' => 1, 'unit_price' => 1000000]);
        $order = $this->service->addItem($order, ['product_id' => $productB->id, 'product_name' => 'B', 'qty' => 1, 'unit_price' => 500000]);
        $order = $this->service->addItem($order, ['product_id' => $productC->id, 'product_name' => 'C', 'qty' => 1, 'unit_price' => 200000]);

        // Target A+B = 1.500.000, diskon 10% = 150.000. C tidak ikut kena diskon.
        $this->assertEquals(150000, $order->discount_total);
    }

    public function test_buy_x_get_y_discount(): void
    {
        $product = $this->makeProduct(price: 100000);

        Discount::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Beli 1 Gratis 1',
            'type' => 'nominal',
            'value' => 0,
            'target_type' => 'transaction',
            'buy_x' => 1,
            'buy_y' => 1,
            'is_active' => true,
        ]);

        $order = $this->service->createDraft(['outlet_id' => $this->outlet->id, 'user_id' => $this->user->id]);
        $order = $this->service->addItem($order, ['product_id' => $product->id, 'product_name' => $product->name, 'qty' => 4, 'unit_price' => 100000]);

        // qty 4, set size 2 (1 beli + 1 gratis) => 2 set => 2 item gratis @ 100.000 = 200.000
        $this->assertEquals(400000, $order->subtotal);
        $this->assertEquals(200000, $order->discount_total);
        $this->assertEquals(200000, $order->grand_total);
    }

    public function test_add_item_fails_on_non_draft_order(): void
    {
        $product = $this->makeProduct(price: 100000);
        $order = $this->service->createDraft(['outlet_id' => $this->outlet->id, 'user_id' => $this->user->id]);
        $order = $this->service->addItem($order, ['product_id' => $product->id, 'product_name' => $product->name, 'qty' => 1, 'unit_price' => 100000]);
        $this->service->updateStatus($order, 'confirmed', $this->user->id);

        $this->expectException(ValidationException::class);
        $this->service->addItem($order->fresh(), ['product_id' => $product->id, 'product_name' => $product->name, 'qty' => 1, 'unit_price' => 100000]);
    }

    public function test_split_order_distributes_items_and_voids_original(): void
    {
        $productA = $this->makeProduct(price: 100000);
        $productB = $this->makeProduct(price: 200000);

        $order = $this->service->createDraft(['outlet_id' => $this->outlet->id, 'user_id' => $this->user->id]);
        $order = $this->service->addItem($order, ['product_id' => $productA->id, 'product_name' => 'A', 'qty' => 2, 'unit_price' => 100000]);
        $order = $this->service->addItem($order, ['product_id' => $productB->id, 'product_name' => 'B', 'qty' => 1, 'unit_price' => 200000]);

        $itemA = $order->items->firstWhere('product_id', $productA->id);
        $itemB = $order->items->firstWhere('product_id', $productB->id);

        $splits = $this->service->splitOrder($order, $this->user->id, [
            ['customer_name' => 'Budi', 'items' => [['order_item_id' => $itemA->id, 'qty' => 2]]],
            ['customer_name' => 'Ani', 'items' => [['order_item_id' => $itemB->id, 'qty' => 1]]],
        ]);

        $this->assertCount(2, $splits);
        $this->assertEquals(200000, $splits[0]->subtotal); // 2 x 100.000
        $this->assertEquals(200000, $splits[1]->subtotal); // 1 x 200.000
        $this->assertEquals('voided', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->bill_group_id);
        $this->assertEquals($order->fresh()->bill_group_id, $splits[0]->bill_group_id);
    }

    public function test_split_order_fails_if_quantities_dont_match_original(): void
    {
        $product = $this->makeProduct(price: 100000);
        $order = $this->service->createDraft(['outlet_id' => $this->outlet->id, 'user_id' => $this->user->id]);
        $order = $this->service->addItem($order, ['product_id' => $product->id, 'product_name' => 'A', 'qty' => 2, 'unit_price' => 100000]);
        $item = $order->items->first();

        $this->expectException(ValidationException::class);
        $this->service->splitOrder($order, $this->user->id, [
            ['customer_name' => 'Budi', 'items' => [['order_item_id' => $item->id, 'qty' => 1]]], // seharusnya 2
        ]);
    }

    public function test_merge_orders_combines_items_and_voids_originals(): void
    {
        $productA = $this->makeProduct(price: 100000);
        $productB = $this->makeProduct(price: 200000);

        $order1 = $this->service->createDraft(['outlet_id' => $this->outlet->id, 'user_id' => $this->user->id]);
        $order1 = $this->service->addItem($order1, ['product_id' => $productA->id, 'product_name' => 'A', 'qty' => 1, 'unit_price' => 100000]);

        $order2 = $this->service->createDraft(['outlet_id' => $this->outlet->id, 'user_id' => $this->user->id]);
        $order2 = $this->service->addItem($order2, ['product_id' => $productB->id, 'product_name' => 'B', 'qty' => 1, 'unit_price' => 200000]);

        $merged = $this->service->mergeOrders([$order1, $order2], $this->user->id, 'Gabungan Meja 5');

        $this->assertCount(2, $merged->items);
        $this->assertEquals(300000, $merged->subtotal);
        $this->assertEquals('voided', $order1->fresh()->status);
        $this->assertEquals('voided', $order2->fresh()->status);
    }

    public function test_refund_partial_updates_status_and_refundable_qty(): void
    {
        $product = $this->makeProduct(price: 100000);
        $order = $this->service->createDraft(['outlet_id' => $this->outlet->id, 'user_id' => $this->user->id]);
        $order = $this->service->addItem($order, ['product_id' => $product->id, 'product_name' => 'A', 'qty' => 3, 'unit_price' => 100000]);
        $order = $this->service->payCash($order, $this->user->id);

        $item = $order->items->first();
        $order = $this->service->refund($order, $this->user->id, [
            ['order_item_id' => $item->id, 'qty' => 1],
        ], 'Item cacat');

        $this->assertEquals('partial', $order->refund_status);
        $freshItem = $order->items->firstWhere('id', $item->id);
        $this->assertEquals(1, $freshItem->refunded_qty);
        $this->assertEquals(2, $freshItem->refundable_qty);
    }

    public function test_refund_full_when_all_items_refunded(): void
    {
        $product = $this->makeProduct(price: 100000);
        $order = $this->service->createDraft(['outlet_id' => $this->outlet->id, 'user_id' => $this->user->id]);
        $order = $this->service->addItem($order, ['product_id' => $product->id, 'product_name' => 'A', 'qty' => 2, 'unit_price' => 100000]);
        $order = $this->service->payCash($order, $this->user->id);

        $item = $order->items->first();
        $order = $this->service->refund($order, $this->user->id, [
            ['order_item_id' => $item->id, 'qty' => 2],
        ]);

        $this->assertEquals('full', $order->refund_status);
    }

    public function test_refund_fails_when_exceeding_refundable_qty(): void
    {
        $product = $this->makeProduct(price: 100000);
        $order = $this->service->createDraft(['outlet_id' => $this->outlet->id, 'user_id' => $this->user->id]);
        $order = $this->service->addItem($order, ['product_id' => $product->id, 'product_name' => 'A', 'qty' => 2, 'unit_price' => 100000]);
        $order = $this->service->payCash($order, $this->user->id);
        $item = $order->items->first();

        $this->expectException(ValidationException::class);
        $this->service->refund($order, $this->user->id, [
            ['order_item_id' => $item->id, 'qty' => 3], // melebihi qty asli
        ]);
    }
}
