<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;
    private Outlet $outlet;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReportService::class);
        $this->outlet = Outlet::factory()->create();
        $this->user = User::factory()->create();
    }

    // ──────────────────────────────────────────────
    // SUMMARY
    // ──────────────────────────────────────────────

    public function test_summary_returns_empty_for_no_data(): void
    {
        $result = $this->service->summary(
            outletId: $this->outlet->id,
            startDate: now()->subDay()->toDateString(),
            endDate: now()->addDay()->toDateString(),
        );

        $this->assertArrayHasKey('summary', $result);
        $this->assertEquals(0, $result['summary']['total_transactions']);
        $this->assertEquals(0, $result['summary']['gross_sales']);
        $this->assertEquals(0, $result['summary']['net_sales']);
        $this->assertEquals(0, $result['summary']['hpp']);
        $this->assertEquals(0, $result['summary']['gross_profit']);
        $this->assertEquals(0, $result['summary']['gross_margin']);
        $this->assertEquals(0, $result['summary']['avg_order_value']);
    }

    public function test_summary_calculates_hpp_and_gross_profit(): void
    {
        $product = Product::create(['outlet_id' => $this->outlet->id, 'name' => 'Nasi Goreng', 'price' => 25000, 'cost' => 10000, 'is_active' => true]);

        // Create a paid order with items that have cost
        $order = Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => 50000,
            'discount_total' => 5000,
            'tax_total' => 5500,
            'grand_total' => 50500,
        ]);

        // Item with unit_cost = 10000, qty = 2 => HPP = 20000
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Nasi Goreng',
            'qty' => 2,
            'unit_price' => 25000,
            'unit_cost' => 10000,
            'total_price' => 50000,
        ]);

        $result = $this->service->summary(
            outletId: $this->outlet->id,
            startDate: now()->subDay()->toDateString(),
            endDate: now()->addDay()->toDateString(),
        );

        $summary = $result['summary'];

        $this->assertEquals(1, $summary['total_transactions']);
        $this->assertEquals(50000, $summary['subtotal']);
        $this->assertEquals(5000, $summary['total_discount']);
        $this->assertEquals(5500, $summary['total_tax']);
        $this->assertEquals(50500, $summary['gross_sales']);
        $this->assertEquals(45000, $summary['net_sales']); // subtotal - discount = 50000 - 5000
        $this->assertEquals(20000, $summary['hpp']);       // 10000 * 2
        $this->assertEquals(25000, $summary['gross_profit']); // net_sales - hpp = 45000 - 20000
        $this->assertEquals(55.56, $summary['gross_margin']); // (25000/45000)*100 = 55.56
        $this->assertEquals(50500, $summary['avg_order_value']);
    }

    public function test_summary_filters_by_date_range(): void
    {
        // Create order from 2 days ago (outside range), force created_at
        $order = new Order([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => 50000,
            'grand_total' => 50000,
        ]);
        $order->created_at = now()->subDays(2);
        $order->save();

        $result = $this->service->summary(
            outletId: $this->outlet->id,
            startDate: now()->subDay()->toDateString(), // 1 day ago
            endDate: now()->addDay()->toDateString(),    // 1 day from now
        );

        // The order from 2 days ago should NOT be included
        $this->assertEquals(0, $result['summary']['total_transactions']);
    }

    public function test_summary_filters_by_outlet(): void
    {
        $outlet2 = Outlet::factory()->create();

        // Create order in outlet2
        Order::create([
            'outlet_id' => $outlet2->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'subtotal' => 50000,
            'grand_total' => 50000,
        ]);

        $result = $this->service->summary(
            outletId: $this->outlet->id, // different outlet
            startDate: now()->subDay()->toDateString(),
            endDate: now()->addDay()->toDateString(),
        );

        $this->assertEquals(0, $result['summary']['total_transactions']);
    }

    public function test_summary_excludes_unpaid_orders(): void
    {
        // Unpaid order
        Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'draft',
            'payment_status' => 'pending',
            'subtotal' => 50000,
            'grand_total' => 50000,
        ]);

        $result = $this->service->summary(
            outletId: $this->outlet->id,
            startDate: now()->subDay()->toDateString(),
            endDate: now()->addDay()->toDateString(),
        );

        $this->assertEquals(0, $result['summary']['total_transactions']);
    }

    public function test_summary_multiple_orders(): void
    {
        $product1 = Product::create(['outlet_id' => $this->outlet->id, 'name' => 'Product A', 'price' => 30000, 'cost' => 10000, 'is_active' => true]);
        $product2 = Product::create(['outlet_id' => $this->outlet->id, 'name' => 'Product B', 'price' => 10000, 'cost' => 4000, 'is_active' => true]);

        // Order 1
        $o1 = Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'subtotal' => 30000,
            'discount_total' => 3000,
            'tax_total' => 2970,
            'grand_total' => 29970,
        ]);
        OrderItem::create([
            'order_id' => $o1->id,
            'product_id' => $product1->id,
            'product_name' => 'Product A',
            'qty' => 1,
            'unit_price' => 30000,
            'unit_cost' => 10000,
            'total_price' => 30000,
        ]);

        // Order 2
        $o2 = Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'subtotal' => 20000,
            'discount_total' => 0,
            'tax_total' => 2200,
            'grand_total' => 22200,
        ]);
        OrderItem::create([
            'order_id' => $o2->id,
            'product_id' => $product2->id,
            'product_name' => 'Product B',
            'qty' => 2,
            'unit_price' => 10000,
            'unit_cost' => 4000,
            'total_price' => 20000,
        ]);

        $result = $this->service->summary(
            outletId: $this->outlet->id,
            startDate: now()->subDay()->toDateString(),
            endDate: now()->addDay()->toDateString(),
        );

        $summary = $result['summary'];

        $this->assertEquals(2, $summary['total_transactions']);
        $this->assertEquals(50000, $summary['subtotal']);          // 30000 + 20000
        $this->assertEquals(3000, $summary['total_discount']);     // 3000 + 0
        $this->assertEquals(5170, $summary['total_tax']);          // 2970 + 2200
        $this->assertEquals(52170, $summary['gross_sales']);       // 29970 + 22200
        $this->assertEquals(47000, $summary['net_sales']);          // 50000 - 3000
        $this->assertEquals(18000, $summary['hpp']);               // 10000 + (4000*2)
        $this->assertEquals(29000, $summary['gross_profit']);      // 47000 - 18000
        $this->assertEquals(61.70, $summary['gross_margin']);      // (29000/47000)*100
        $this->assertEquals(26085, $summary['avg_order_value']);    // 52170/2
    }

    // ──────────────────────────────────────────────
    // DAILY SALES
    // ──────────────────────────────────────────────

    public function test_daily_sales_returns_empty_for_no_data(): void
    {
        $result = $this->service->dailySales(
            outletId: $this->outlet->id,
            startDate: now()->subDay()->toDateString(),
            endDate: now()->addDay()->toDateString(),
        );

        $this->assertEquals([], $result);
    }

    public function test_daily_sales_groups_by_date(): void
    {
        // Create 2 orders today
        Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'subtotal' => 25000,
            'discount_total' => 2500,
            'tax_total' => 2475,
            'grand_total' => 24975,
        ]);
        Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'subtotal' => 15000,
            'discount_total' => 0,
            'tax_total' => 1650,
            'grand_total' => 16650,
        ]);

        $result = $this->service->dailySales(
            outletId: $this->outlet->id,
            startDate: now()->subDay()->toDateString(),
            endDate: now()->addDay()->toDateString(),
        );

        $this->assertCount(1, $result); // semua hari ini
        $day = $result[0];
        $this->assertEquals(now()->toDateString(), $day['date']);
        $this->assertEquals(2, $day['total_orders']);
        $this->assertEquals(40000, $day['subtotal']);
        $this->assertEquals(41625, $day['gross_sales']); // 24975 + 16650
        $this->assertEquals(2500, $day['total_discount']);
        $this->assertEquals(4125, $day['total_tax']);
        $this->assertEquals(37500, $day['net_sales']);    // 40000 - 2500
    }

    // ──────────────────────────────────────────────
    // TOP PRODUCTS
    // ──────────────────────────────────────────────

    public function test_top_products_returns_empty_for_no_data(): void
    {
        $result = $this->service->topProducts(
            outletId: $this->outlet->id,
            startDate: now()->subDay()->toDateString(),
            endDate: now()->addDay()->toDateString(),
        );

        $this->assertEquals([], $result);
    }

    public function test_top_products_ranks_by_revenue(): void
    {
        $product1 = Product::create(['outlet_id' => $this->outlet->id, 'name' => 'Nasi Goreng', 'price' => 10000, 'cost' => 4000, 'is_active' => true]);
        $product2 = Product::create(['outlet_id' => $this->outlet->id, 'name' => 'Es Teh', 'price' => 5000, 'cost' => 1000, 'is_active' => true]);

        $order = Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'subtotal' => 35000,
            'grand_total' => 35000,
        ]);

        // Product A: 2 items, total price 20000, cost 8000
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'product_name' => 'Nasi Goreng',
            'qty' => 2,
            'unit_price' => 10000,
            'unit_cost' => 4000,
            'total_price' => 20000,
        ]);

        // Product B: 3 items, total price 15000, cost 3000
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'product_name' => 'Es Teh',
            'qty' => 3,
            'unit_price' => 5000,
            'unit_cost' => 1000,
            'total_price' => 15000,
        ]);

        $result = $this->service->topProducts(
            outletId: $this->outlet->id,
            startDate: now()->subDay()->toDateString(),
            endDate: now()->addDay()->toDateString(),
        );

        $this->assertCount(2, $result);

        // Product A (Nasi Goreng) should be first (higher revenue)
        $this->assertEquals('Nasi Goreng', $result[0]['product_name']);
        $this->assertEquals(2, $result[0]['total_qty']);
        $this->assertEquals(20000, $result[0]['total_revenue']);
        $this->assertEquals(8000, $result[0]['total_hpp']);   // 4000 * 2
        $this->assertEquals(12000, $result[0]['gross_profit']); // 20000 - 8000
        $this->assertEquals(60.0, $result[0]['gross_margin']);  // 12000/20000*100

        // Product B (Es Teh)
        $this->assertEquals('Es Teh', $result[1]['product_name']);
        $this->assertEquals(3, $result[1]['total_qty']);
        $this->assertEquals(15000, $result[1]['total_revenue']);
        $this->assertEquals(3000, $result[1]['total_hpp']);
        $this->assertEquals(12000, $result[1]['gross_profit']);
        $this->assertEquals(80.0, $result[1]['gross_margin']);
    }

    public function test_top_products_excludes_unpaid_orders(): void
    {
        $product = Product::create(['outlet_id' => $this->outlet->id, 'name' => 'Nasi Goreng', 'price' => 25000, 'cost' => 10000, 'is_active' => true]);

        // Unpaid order with items
        $order = Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'draft',
            'payment_status' => 'pending',
            'subtotal' => 50000,
            'grand_total' => 50000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Nasi Goreng',
            'qty' => 2,
            'unit_price' => 25000,
            'unit_cost' => 10000,
            'total_price' => 50000,
        ]);

        $result = $this->service->topProducts(
            outletId: $this->outlet->id,
            startDate: now()->subDay()->toDateString(),
            endDate: now()->addDay()->toDateString(),
        );

        $this->assertEquals([], $result);
    }

    public function test_top_products_respects_limit(): void
    {
        $pA = Product::create(['outlet_id' => $this->outlet->id, 'name' => 'Product A', 'price' => 10000, 'cost' => 5000, 'is_active' => true]);
        $pB = Product::create(['outlet_id' => $this->outlet->id, 'name' => 'Product B', 'price' => 20000, 'cost' => 10000, 'is_active' => true]);
        $pC = Product::create(['outlet_id' => $this->outlet->id, 'name' => 'Product C', 'price' => 30000, 'cost' => 15000, 'is_active' => true]);

        $order = Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'subtotal' => 100000,
            'grand_total' => 100000,
        ]);

        $items = [
            ['product' => $pA, 'name' => 'Product A', 'price' => 10000, 'cost' => 5000],
            ['product' => $pB, 'name' => 'Product B', 'price' => 20000, 'cost' => 10000],
            ['product' => $pC, 'name' => 'Product C', 'price' => 30000, 'cost' => 15000],
        ];
        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product']->id,
                'product_name' => $item['name'],
                'qty' => 1,
                'unit_price' => $item['price'],
                'unit_cost' => $item['cost'],
                'total_price' => $item['price'],
            ]);
        }

        $result = $this->service->topProducts(
            outletId: $this->outlet->id,
            startDate: now()->subDay()->toDateString(),
            endDate: now()->addDay()->toDateString(),
            limit: 2,
        );

        $this->assertCount(2, $result);
        $this->assertEquals('Product C', $result[0]['product_name']);
        $this->assertEquals('Product B', $result[1]['product_name']);
    }
}
