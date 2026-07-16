<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShiftServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShiftService $service;
    private Outlet $outlet;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftService::class);
        $this->outlet = Outlet::factory()->create();
        $this->user = User::factory()->create();
    }

    // ──────────────────────────────────────────────
    // START SHIFT
    // ──────────────────────────────────────────────

    public function test_can_start_shift(): void
    {
        $shift = $this->service->startShift(
            userId: $this->user->id,
            outletId: $this->outlet->id,
            cashBegin: 100000
        );

        $this->assertInstanceOf(Shift::class, $shift);
        $this->assertEquals($this->user->id, $shift->user_id);
        $this->assertEquals($this->outlet->id, $shift->outlet_id);
        $this->assertEquals(100000, $shift->cash_begin);
        $this->assertNotNull($shift->start_at);
        $this->assertNull($shift->end_at);
    }

    public function test_cannot_start_shift_if_already_active(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->startShift(
            userId: $this->user->id,
            outletId: $this->outlet->id,
            cashBegin: 100000
        );

        // Second start should fail
        $this->service->startShift(
            userId: $this->user->id,
            outletId: $this->outlet->id,
            cashBegin: 50000
        );
    }

    public function test_can_start_shift_after_previous_ended(): void
    {
        $shift1 = $this->service->startShift(
            userId: $this->user->id,
            outletId: $this->outlet->id,
            cashBegin: 100000
        );

        // End the first shift
        $this->service->endShift($shift1, 150000);

        // Start a new shift — should succeed because previous is ended
        $shift2 = $this->service->startShift(
            userId: $this->user->id,
            outletId: $this->outlet->id,
            cashBegin: 200000
        );

        $this->assertNotNull($shift2);
        $this->assertEquals(200000, $shift2->cash_begin);
        $this->assertNull($shift2->end_at);
    }

    // ──────────────────────────────────────────────
    // END SHIFT & CASH RECONCILIATION
    // ──────────────────────────────────────────────

    public function test_can_end_shift(): void
    {
        $shift = $this->service->startShift(
            userId: $this->user->id,
            outletId: $this->outlet->id,
            cashBegin: 100000
        );

        $ended = $this->service->endShift($shift, 150000);

        $this->assertNotNull($ended->end_at);
        $this->assertEquals(100000, $ended->cash_begin);
        $this->assertEquals(150000, $ended->cash_actual);
    }

    public function test_cannot_end_already_ended_shift(): void
    {
        $this->expectException(ValidationException::class);

        $shift = $this->service->startShift(
            userId: $this->user->id,
            outletId: $this->outlet->id,
            cashBegin: 100000
        );

        $this->service->endShift($shift, 150000);

        // End again should fail
        $this->service->endShift($shift->fresh(), 200000);
    }

    public function test_cash_expected_equals_begin_plus_sales(): void
    {
        $shift = $this->service->startShift(
            userId: $this->user->id,
            outletId: $this->outlet->id,
            cashBegin: 100000
        );

        // Create paid cash orders during the shift
        $order1 = Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'grand_total' => 50000,
            'subtotal' => 50000,
        ]);
        // Ensure created_at is after shift start
        $order1->update(['created_at' => now()]);

        $order2 = Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'grand_total' => 75000,
            'subtotal' => 75000,
        ]);
        $order2->update(['created_at' => now()]);

        // Create a non-cash order (should NOT affect cash_expected)
        Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'payment_method' => 'qris',
            'grand_total' => 100000,
            'subtotal' => 100000,
        ]);

        $ended = $this->service->endShift($shift, 250000);

        // cash_expected = cash_begin (100000) + sum of cash orders (50000 + 75000) = 225000
        $this->assertEquals(225000, $ended->cash_expected);
        $this->assertEquals(250000, $ended->cash_actual);
        $this->assertEquals(25000, $ended->cash_diff); // 250000 - 225000 = 25000
    }

    public function test_cash_diff_negative_when_short(): void
    {
        $shift = $this->service->startShift(
            userId: $this->user->id,
            outletId: $this->outlet->id,
            cashBegin: 100000
        );

        Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'grand_total' => 50000,
            'subtotal' => 50000,
        ]);

        $ended = $this->service->endShift($shift, 120000);

        // cash_expected = 100000 + 50000 = 150000
        // cash_actual = 120000
        // cash_diff = 120000 - 150000 = -30000
        $this->assertEquals(150000, $ended->cash_expected);
        $this->assertEquals(120000, $ended->cash_actual);
        $this->assertEquals(-30000, $ended->cash_diff);
    }

    public function test_cash_expected_excludes_orders_outside_shift_period(): void
    {
        $shift = $this->service->startShift(
            userId: $this->user->id,
            outletId: $this->outlet->id,
            cashBegin: 100000
        );

        // Order created before shift start (should NOT be included)
        $order = new Order([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'done',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'grand_total' => 999999,
            'subtotal' => 999999,
        ]);
        $order->created_at = now()->subDay();
        $order->save();

        $ended = $this->service->endShift($shift, 100000);

        // cash_expected should only be cash_begin since no orders during shift
        $this->assertEquals(100000, $ended->cash_expected);
        $this->assertEquals(0, $ended->cash_diff);
    }

    public function test_cash_expected_excludes_unpaid_orders(): void
    {
        $shift = $this->service->startShift(
            userId: $this->user->id,
            outletId: $this->outlet->id,
            cashBegin: 100000
        );

        // Unpaid cash order (should NOT be included)
        Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'draft',
            'payment_status' => 'pending',
            'payment_method' => 'cash',
            'grand_total' => 50000,
            'subtotal' => 50000,
        ]);

        $ended = $this->service->endShift($shift, 100000);

        $this->assertEquals(100000, $ended->cash_expected);
    }
}
