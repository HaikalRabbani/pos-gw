<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Outlet;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShiftServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ShiftService $service;
    protected Outlet $outlet;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShiftService();
        $this->outlet = Outlet::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_start_shift_creates_record_with_cash_begin(): void
    {
        $shift = $this->service->startShift($this->user->id, $this->outlet->id, 500000);

        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'user_id' => $this->user->id,
            'outlet_id' => $this->outlet->id,
            'cash_begin' => 500000,
        ]);
        $this->assertNull($shift->end_at);
    }

    public function test_start_shift_fails_if_user_already_has_active_shift(): void
    {
        $this->service->startShift($this->user->id, $this->outlet->id, 500000);

        $this->expectException(ValidationException::class);
        $this->service->startShift($this->user->id, $this->outlet->id, 200000);
    }

    public function test_end_shift_only_sums_paid_cash_orders_within_shift_window(): void
    {
        $shift = $this->service->startShift($this->user->id, $this->outlet->id, 500000);

        // Dihitung: cash + paid, dalam window shift
        $this->makeOrder(300000, 'cash', 'paid');
        $this->makeOrder(200000, 'cash', 'paid');

        // TIDAK dihitung: bukan cash
        $this->makeOrder(1000000, 'qris', 'paid');

        // TIDAK dihitung: belum paid
        $this->makeOrder(500000, 'cash', 'pending');

        $ended = $this->service->endShift($shift->fresh(), 800000);

        // expected = cash_begin (500.000) + 300.000 + 200.000 = 1.000.000
        $this->assertEquals(1000000, $ended->cash_expected);
        $this->assertEquals(800000, $ended->cash_actual);
        $this->assertEquals(-200000, $ended->cash_diff); // kurang 200.000
    }

    public function test_end_shift_fails_if_already_ended(): void
    {
        $shift = $this->service->startShift($this->user->id, $this->outlet->id, 0);
        $this->service->endShift($shift->fresh(), 0);

        $this->expectException(ValidationException::class);
        $this->service->endShift($shift->fresh(), 0);
    }

    protected function makeOrder(int $grandTotal, string $method, string $paymentStatus): Order
    {
        return Order::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'confirmed',
            'grand_total' => $grandTotal,
            'payment_method' => $method,
            'payment_status' => $paymentStatus,
        ]);
    }
}
