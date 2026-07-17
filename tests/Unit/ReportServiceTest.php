<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Outlet;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReportService $service;
    protected Outlet $outlet;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportService();
        $this->outlet = Outlet::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_summary_calculates_hpp_and_gross_margin_correctly(): void
    {
        $today = now()->format('Y-m-d');

        $order = Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'subtotal' => 500000,
            'discount_total' => 50000,
            'tax_total' => 49500,
            'grand_total' => 499500,
            'payment_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Nasi Goreng',
            'qty' => 5,
            'unit_price' => 100000,
            'unit_cost' => 60000, // HPP 300.000 buat 5 qty
            'total_price' => 500000,
        ]);

        $summary = $this->service->summary($this->outlet->id, $today, $today);

        // net_sales = subtotal - discount = 500.000 - 50.000 = 450.000
        // hpp = 60.000 x 5 = 300.000
        // gross_profit = 450.000 - 300.000 = 150.000
        // margin = 150.000 / 450.000 = 33.33%
        $this->assertEquals(450000, $summary['summary']['net_sales']);
        $this->assertEquals(300000, $summary['summary']['hpp']);
        $this->assertEquals(150000, $summary['summary']['gross_profit']);
        $this->assertEquals(33.33, $summary['summary']['gross_margin']);
    }

    public function test_summary_excludes_unpaid_orders(): void
    {
        $today = now()->format('Y-m-d');

        Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'draft',
            'subtotal' => 100000,
            'grand_total' => 100000,
            'payment_status' => 'pending', // belum dibayar
        ]);

        $summary = $this->service->summary($this->outlet->id, $today, $today);

        $this->assertEquals(0, $summary['summary']['total_transactions']);
        $this->assertEquals(0, $summary['summary']['gross_sales']);
    }

    public function test_summary_excludes_orders_outside_date_range(): void
    {
        $order = Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'subtotal' => 100000,
            'grand_total' => 100000,
            'payment_status' => 'paid',
        ]);
        // Paksa created_at ke luar range (30 hari lalu)
        $order->forceFill(['created_at' => now()->subDays(30)])->save();

        $today = now()->format('Y-m-d');
        $summary = $this->service->summary($this->outlet->id, $today, $today);

        $this->assertEquals(0, $summary['summary']['total_transactions']);
    }
}
